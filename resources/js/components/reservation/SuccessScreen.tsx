import { router } from '@inertiajs/react';
import { CheckCircle2 } from 'lucide-react';

interface SuccessScreenProps {
    reservation: any;
    onNewReservation: () => void;
}

export default function SuccessScreen({ reservation, onNewReservation }: SuccessScreenProps) {
    const getTableDisplay = () => {
        if (!reservation?.table) return '-';
        return reservation.table.table_number;
    };

    const formatDate = (dateStr: string) => {
        return new Date(dateStr).toLocaleDateString('id-ID', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });
    };

    return (
        <div className="flex min-h-screen flex-col items-center justify-center bg-white px-6">
            <div className="w-full max-w-sm text-center">
                <div className="mx-auto mb-5 flex h-20 w-20 items-center justify-center rounded-full bg-green-50">
                    <CheckCircle2 className="h-10 w-10 text-green-500" />
                </div>

                <h2 className="text-lg font-bold text-gray-800">Reservasi Berhasil!</h2>
                <p className="mt-2 text-sm text-gray-400">
                    Reservasi Anda telah tercatat. Silakan datang sesuai jadwal.
                </p>

                {/* Reservation details card */}
                <div className="mt-6 rounded-xl border border-gray-100 bg-white p-4 shadow-sm text-left space-y-2">
                    <div className="flex justify-between text-sm">
                        <span className="text-gray-500">Nama</span>
                        <span className="font-medium text-gray-700">{reservation?.customer_name}</span>
                    </div>
                    <div className="border-t border-gray-100" />
                    <div className="flex justify-between text-sm">
                        <span className="text-gray-500">Telepon</span>
                        <span className="font-medium text-gray-700">{reservation?.customer_phone}</span>
                    </div>
                    <div className="border-t border-gray-100" />
                    <div className="flex justify-between text-sm">
                        <span className="text-gray-500">Meja</span>
                        <span className="font-bold text-orange-500">{getTableDisplay()}</span>
                    </div>
                    <div className="border-t border-gray-100" />
                    <div className="flex justify-between text-sm">
                        <span className="text-gray-500">Tanggal</span>
                        <span className="font-medium text-gray-700">
                            {reservation?.reservation_date ? formatDate(reservation.reservation_date) : '-'}
                        </span>
                    </div>
                    <div className="border-t border-gray-100" />
                    <div className="flex justify-between text-sm">
                        <span className="text-gray-500">Waktu</span>
                        <span className="font-medium text-gray-700">{reservation?.reservation_time || '-'}</span>
                    </div>
                    <div className="border-t border-gray-100" />
                    <div className="flex justify-between text-sm">
                        <span className="text-gray-500">Status</span>
                        <span className="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-semibold text-yellow-700">
                            Pending
                        </span>
                    </div>
                </div>

                {/* Actions */}
                <div className="mt-8 space-y-3">
                    <button
                        onClick={() => router.get('/')}
                        className="w-full rounded-full border-2 border-dashed border-orange-200 py-3 text-sm font-medium text-orange-500 transition-all hover:border-orange-400 hover:bg-orange-50/50 active:scale-[0.98]"
                    >
                        Kembali ke Menu
                    </button>
                </div>
            </div>
        </div>
    );
}
