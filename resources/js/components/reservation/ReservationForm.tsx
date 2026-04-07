import { ArrowLeft, CalendarDays, Clock, Phone, User, Armchair, Sofa } from 'lucide-react';

export interface TableItem {
    id: number;
    table_number: string;
    seating_type: 'lesehan' | 'chair';
    is_active: boolean;
}

interface ReservationFormProps {
    customerName: string;
    customerPhone: string;
    seatingType: 'lesehan' | 'kursi' | '';
    selectedTable: number | '';
    reservationDate: string;
    reservationTime: string;
    filteredTables: TableItem[];
    errors: Record<string, string>;
    isSubmitting: boolean;
    today: string;
    onCustomerNameChange: (v: string) => void;
    onCustomerPhoneChange: (v: string) => void;
    onSeatingTypeChange: (v: 'lesehan' | 'kursi') => void;
    onSelectedTableChange: (v: number | '') => void;
    onReservationDateChange: (v: string) => void;
    onReservationTimeChange: (v: string) => void;
    onSubmit: (e: React.FormEvent) => void;
    onBack: () => void;
}

export default function ReservationForm({
    customerName,
    customerPhone,
    seatingType,
    selectedTable,
    reservationDate,
    reservationTime,
    filteredTables,
    errors,
    isSubmitting,
    today,
    onCustomerNameChange,
    onCustomerPhoneChange,
    onSeatingTypeChange,
    onSelectedTableChange,
    onReservationDateChange,
    onReservationTimeChange,
    onSubmit,
    onBack,
}: ReservationFormProps) {
    return (
        <div className="mx-auto max-w-lg">
            {/* Header - matches CartHeader */}
            <header className="sticky top-0 z-50 bg-white shadow-sm">
                <div className="flex items-center gap-3 px-4 py-3">
                    <button
                        onClick={onBack}
                        className="flex h-9 w-9 items-center justify-center rounded-full hover:bg-gray-100 transition-colors"
                    >
                        <ArrowLeft className="h-5 w-5 text-gray-700" />
                    </button>
                    <div>
                        <h1 className="text-lg font-bold text-gray-800">Reservasi Meja</h1>
                        <p className="text-xs text-gray-400">Pilih meja & jadwal kunjungan</p>
                    </div>
                </div>
            </header>

            {/* Form */}
            <form onSubmit={onSubmit} className="pb-36">
                {errors.general && (
                    <div className="mx-4 mt-4 rounded-xl bg-red-50 border border-red-100 px-4 py-3 text-sm text-red-600">
                        {errors.general}
                    </div>
                )}

                {/* Data Pemesan */}
                <section className="px-4 pt-4">
                    <h2 className="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">
                        Data Pemesan
                    </h2>
                    <div className="rounded-xl border border-gray-100 bg-white p-4 shadow-sm space-y-3">
                        {/* Customer Name */}
                        <div className="relative">
                            <User className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                            <input
                                type="text"
                                value={customerName}
                                onChange={(e) => onCustomerNameChange(e.target.value)}
                                placeholder="Nama lengkap"
                                className={`w-full rounded-xl border py-2.5 pl-10 pr-4 text-sm text-gray-700 placeholder:text-gray-300 focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-100 ${errors.customer_name ? 'border-red-300' : 'border-gray-200'}`}
                            />
                            {errors.customer_name && (
                                <p className="mt-1 text-xs text-red-500">{errors.customer_name}</p>
                            )}
                        </div>

                        {/* Customer Phone */}
                        <div className="relative">
                            <Phone className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                            <input
                                type="tel"
                                value={customerPhone}
                                onChange={(e) => onCustomerPhoneChange(e.target.value)}
                                placeholder="Nomor telepon (08xxxxxxxxxx)"
                                className={`w-full rounded-xl border py-2.5 pl-10 pr-4 text-sm text-gray-700 placeholder:text-gray-300 focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-100 ${errors.customer_phone ? 'border-red-300' : 'border-gray-200'}`}
                            />
                            {errors.customer_phone && (
                                <p className="mt-1 text-xs text-red-500">{errors.customer_phone}</p>
                            )}
                        </div>
                    </div>
                </section>

                {/* Tipe Tempat Duduk */}
                <section className="px-4 mt-6">
                    <h2 className="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">
                        Tipe Tempat Duduk
                    </h2>
                    <div className="grid grid-cols-2 gap-3">
                        <button
                            type="button"
                            onClick={() => onSeatingTypeChange('lesehan')}
                            className={`flex flex-col items-center gap-2 rounded-xl border bg-white px-4 py-4 shadow-sm transition-all ${
                                seatingType === 'lesehan'
                                    ? 'border-orange-500 ring-2 ring-orange-100'
                                    : 'border-gray-100 hover:border-gray-200'
                            }`}
                        >
                            <Sofa className={`h-7 w-7 ${seatingType === 'lesehan' ? 'text-orange-500' : 'text-gray-400'}`} />
                            <span className={`text-sm font-semibold ${seatingType === 'lesehan' ? 'text-orange-500' : 'text-gray-500'}`}>
                                Lesehan
                            </span>
                        </button>
                        <button
                            type="button"
                            onClick={() => onSeatingTypeChange('kursi')}
                            className={`flex flex-col items-center gap-2 rounded-xl border bg-white px-4 py-4 shadow-sm transition-all ${
                                seatingType === 'kursi'
                                    ? 'border-orange-500 ring-2 ring-orange-100'
                                    : 'border-gray-100 hover:border-gray-200'
                            }`}
                        >
                            <Armchair className={`h-7 w-7 ${seatingType === 'kursi' ? 'text-orange-500' : 'text-gray-400'}`} />
                            <span className={`text-sm font-semibold ${seatingType === 'kursi' ? 'text-orange-500' : 'text-gray-500'}`}>
                                Kursi
                            </span>
                        </button>
                    </div>
                    {errors.seating_type && (
                        <p className="mt-1 text-xs text-red-500">{errors.seating_type}</p>
                    )}
                </section>

                {/* Pilih Meja */}
                <section className="px-4 mt-6">
                    <h2 className="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">
                        Pilih Meja
                    </h2>
                    {!seatingType ? (
                        <div className="rounded-xl border border-dashed border-gray-200 bg-white px-4 py-6 text-center shadow-sm">
                            <p className="text-sm text-gray-400">Pilih tipe tempat duduk terlebih dahulu</p>
                        </div>
                    ) : filteredTables.length === 0 ? (
                        <div className="rounded-xl border border-dashed border-orange-200 bg-white px-4 py-6 text-center shadow-sm">
                            <p className="text-sm text-gray-400">Tidak ada meja tersedia untuk tipe ini</p>
                        </div>
                    ) : (
                        <div className="grid grid-cols-3 gap-2">
                            {filteredTables.map((table) => (
                                <button
                                    key={table.id}
                                    type="button"
                                    onClick={() => onSelectedTableChange(table.id)}
                                    className={`rounded-xl border px-3 py-3 text-center text-sm font-semibold shadow-sm transition-all ${
                                        selectedTable === table.id
                                            ? 'border-orange-500 bg-orange-500 text-white shadow-md shadow-orange-500/25'
                                            : 'border-gray-100 bg-white text-gray-700 hover:border-gray-200'
                                    }`}
                                >
                                    {table.table_number}
                                </button>
                            ))}
                        </div>
                    )}
                    {errors.id_table && (
                        <p className="mt-1 text-xs text-red-500">{errors.id_table}</p>
                    )}
                </section>

                {/* Jadwal */}
                <section className="px-4 mt-6">
                    <h2 className="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">
                        Jadwal Reservasi
                    </h2>
                    <div className="rounded-xl border border-gray-100 bg-white p-4 shadow-sm space-y-3">
                        {/* Date */}
                        <div className="relative">
                            <CalendarDays className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                            <input
                                type="date"
                                value={reservationDate}
                                onChange={(e) => onReservationDateChange(e.target.value)}
                                min={today}
                                className={`w-full rounded-xl border py-2.5 pl-10 pr-4 text-sm text-gray-700 focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-100 ${errors.reservation_date ? 'border-red-300' : 'border-gray-200'}`}
                            />
                            {errors.reservation_date && (
                                <p className="mt-1 text-xs text-red-500">{errors.reservation_date}</p>
                            )}
                        </div>

                        {/* Time */}
                        <div className="relative">
                            <Clock className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                            <input
                                type="time"
                                value={reservationTime}
                                onChange={(e) => onReservationTimeChange(e.target.value)}
                                className={`w-full rounded-xl border py-2.5 pl-10 pr-4 text-sm text-gray-700 focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-100 ${errors.reservation_time ? 'border-red-300' : 'border-gray-200'}`}
                            />
                            {errors.reservation_time && (
                                <p className="mt-1 text-xs text-red-500">{errors.reservation_time}</p>
                            )}
                        </div>
                    </div>
                </section>
            </form>

            {/* Fixed bottom button - matches Cart */}
            <div className="fixed bottom-0 left-1/2 z-40 w-full max-w-lg -translate-x-1/2 bg-white border-t border-gray-100 px-4 py-4">
                <button
                    onClick={(e) => { e.preventDefault(); onSubmit(e); }}
                    disabled={isSubmitting}
                    className="w-full rounded-full bg-orange-500 py-3.5 text-sm font-bold text-white shadow-md shadow-orange-500/25 transition-all hover:bg-orange-600 hover:shadow-lg active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    {isSubmitting ? 'Memproses...' : 'Buat Reservasi'}
                </button>
            </div>
        </div>
    );
}
