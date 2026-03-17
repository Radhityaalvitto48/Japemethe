import { Head, router } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import { WelcomeScreen, ReservationForm, SuccessScreen, type TableItem } from '@/components/reservation';

type Step = 'welcome' | 'form' | 'success';

export default function ReservationPage() {
    const [step, setStep] = useState<Step>('welcome');
    const [tables, setTables] = useState<TableItem[]>([]);
    const [filteredTables, setFilteredTables] = useState<TableItem[]>([]);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [createdReservation, setCreatedReservation] = useState<any>(null);

    // Form state
    const [customerName, setCustomerName] = useState('');
    const [customerPhone, setCustomerPhone] = useState('');
    const [seatingType, setSeatingType] = useState<'lesehan' | 'kursi' | ''>('');
    const [selectedTable, setSelectedTable] = useState<number | ''>('');
    const [reservationDate, setReservationDate] = useState('');
    const [reservationTime, setReservationTime] = useState('');

    // Fetch tables
    useEffect(() => {
        const fetchTables = async () => {
            try {
                const res = await fetch('/api/tables');
                const data = await res.json();
                if (data.success) {
                    setTables(data.data.filter((t: TableItem) => t.is_active));
                }
            } catch {
                // silently fail
            }
        };
        fetchTables();
    }, []);

    // Filter tables by seating type
    useEffect(() => {
        if (!seatingType) {
            setFilteredTables([]);
            setSelectedTable('');
            return;
        }
        const dbType = seatingType === 'kursi' ? 'chair' : 'lesehan';
        setFilteredTables(tables.filter(t => t.seating_type === dbType));
        setSelectedTable('');
    }, [seatingType, tables]);

    // Get today's date for min date
    const today = new Date().toISOString().split('T')[0];

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (isSubmitting) return;

        // Client-side validation
        const newErrors: Record<string, string> = {};
        if (!customerName.trim()) newErrors.customer_name = 'Nama harus diisi';
        if (!customerPhone.trim()) newErrors.customer_phone = 'Nomor telepon harus diisi';
        if (!seatingType) newErrors.seating_type = 'Tipe tempat duduk harus dipilih';
        if (!selectedTable) newErrors.id_table = 'Meja harus dipilih';
        if (!reservationDate) newErrors.reservation_date = 'Tanggal harus diisi';
        if (!reservationTime) newErrors.reservation_time = 'Waktu harus diisi';

        if (Object.keys(newErrors).length > 0) {
            setErrors(newErrors);
            return;
        }

        setErrors({});
        setIsSubmitting(true);

        try {
            const res = await fetch('/api/reservations', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    customer_name: customerName.trim(),
                    customer_phone: customerPhone.trim(),
                    seating_type: seatingType,
                    id_table: selectedTable,
                    reservation_date: reservationDate,
                    reservation_time: reservationTime,
                }),
            });

            const data = await res.json();

            if (data.success) {
                setCreatedReservation(data.data);
                setStep('success');
            } else if (data.errors) {
                const serverErrors: Record<string, string> = {};
                Object.entries(data.errors).forEach(([key, msgs]) => {
                    serverErrors[key] = Array.isArray(msgs) ? msgs[0] : String(msgs);
                });
                setErrors(serverErrors);
            } else {
                setErrors({ general: data.message || 'Gagal membuat reservasi' });
            }
        } catch {
            setErrors({ general: 'Terjadi kesalahan, coba lagi.' });
        } finally {
            setIsSubmitting(false);
        }
    };

    const resetForm = () => {
        setCustomerName('');
        setCustomerPhone('');
        setSeatingType('');
        setSelectedTable('');
        setReservationDate('');
        setReservationTime('');
        setErrors({});
        setCreatedReservation(null);
        setStep('welcome');
    };

    return (
        <>
            <Head title="Reservasi - Japemethe" />

            <div className="min-h-screen bg-gray-100">
            <div className="relative mx-auto min-h-screen max-w-lg bg-white shadow-xl">
                {step === 'welcome' && <WelcomeScreen onStart={() => setStep('form')} />}
                {step === 'form' && (
                    <ReservationForm
                        customerName={customerName}
                        customerPhone={customerPhone}
                        seatingType={seatingType}
                        selectedTable={selectedTable}
                        reservationDate={reservationDate}
                        reservationTime={reservationTime}
                        filteredTables={filteredTables}
                        errors={errors}
                        isSubmitting={isSubmitting}
                        today={today}
                        onCustomerNameChange={setCustomerName}
                        onCustomerPhoneChange={setCustomerPhone}
                        onSeatingTypeChange={setSeatingType}
                        onSelectedTableChange={setSelectedTable}
                        onReservationDateChange={setReservationDate}
                        onReservationTimeChange={setReservationTime}
                        onSubmit={handleSubmit}
                        onBack={() => setStep('welcome')}
                    />
                )}
                {step === 'success' && (
                    <SuccessScreen
                        reservation={createdReservation}
                        onNewReservation={resetForm}
                    />
                )}
            </div>
            </div>
        </>
    );
}
