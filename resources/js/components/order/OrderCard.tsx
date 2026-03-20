import { Clock, CheckCircle, XCircle, ChefHat } from 'lucide-react';

export interface OrderItem {
    id: number;
    menu_id: number;
    quantity: number;
    unit_price: number;
    subtotal: number;
    note: string | null;
    menu: {
        id: number;
        name: string;
    };
}

export interface Payment {
    id: number;
    payment_method: string;
    status_payment: 'pending' | 'completed' | 'failed';
    grass_amount: number;
}

export interface Order {
    id: number;
    order_number: string;
    total_items: number;
    total_price: number;
    status_order: 'pending' | 'in_progress' | 'completed' | 'cancelled';
    customer_phone: string | null;
    customer_email: string | null;
    ordered_at: string;
    created_at: string;
    table: {
        id: number;
        table_number: string;
    } | null;
    order_details: OrderItem[];
    payment: Payment | null;
    qr_payload?: string | null;
    qr_code_data_uri?: string | null;
}

const statusConfig = {
    pending: {
        label: 'Belum Dibayar',
        color: 'text-yellow-600 bg-yellow-50 border-yellow-200',
        icon: Clock,
    },
    in_progress: {
        label: 'Sedang Diproses',
        color: 'text-blue-600 bg-blue-50 border-blue-200',
        icon: ChefHat,
    },
    completed: {
        label: 'Selesai',
        color: 'text-green-600 bg-green-50 border-green-200',
        icon: CheckCircle,
    },
    cancelled: {
        label: 'Dibatalkan',
        color: 'text-red-600 bg-red-50 border-red-200',
        icon: XCircle,
    },
};

const paymentStatusConfig = {
    pending: { label: 'Belum Bayar', color: 'text-yellow-600' },
    completed: { label: 'Lunas', color: 'text-green-600' },
    failed: { label: 'Gagal', color: 'text-red-600' },
};

function formatDate(dateStr: string) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

interface OrderCardProps {
    order: Order;
}

export default function OrderCard({ order }: OrderCardProps) {
    const status = statusConfig[order.status_order] || statusConfig.pending;
    const StatusIcon = status.icon;
    const paymentStatus = order.payment
        ? paymentStatusConfig[order.payment.status_payment]
        : null;

    return (
        <div className="rounded-xl border border-gray-100 bg-white shadow-sm overflow-hidden">
            {/* Order Header */}
            <div className="flex items-center justify-between px-4 pt-4 pb-2">
                <div>
                    <p className="text-sm font-bold text-gray-800">
                        #{order.order_number}
                    </p>
                    <p className="text-xs text-gray-400 mt-0.5">
                        {formatDate(order.ordered_at || order.created_at)}
                        {order.table && ` · Meja ${order.table.table_number}`}
                    </p>
                </div>
                <div className={`flex items-center gap-1.5 rounded-full border px-3 py-1 ${status.color}`}>
                    <StatusIcon className="h-3.5 w-3.5" />
                    <span className="text-xs font-semibold">{status.label}</span>
                </div>
            </div>

            {/* Order Items */}
            <div className="px-4 py-2 space-y-1.5">
                {order.order_details.map((item) => (
                    <div key={item.id} className="flex justify-between text-sm">
                        <span className="text-gray-600">
                            {item.quantity}x {item.menu?.name || 'Menu'}
                        </span>
                        <span className="text-gray-500">
                            Rp {item.subtotal.toLocaleString('id-ID')}
                        </span>
                    </div>
                ))}
            </div>

            {order.status_order === 'pending' && order.qr_code_data_uri && (
                <div className="border-t border-gray-50 px-4 py-4">
                    <div className="rounded-2xl border border-orange-200 bg-orange-50 p-4">
                        <p className="text-xs font-bold uppercase tracking-wide text-orange-700">
                            Perhatian
                        </p>
                        <p className="mt-1 text-xs leading-relaxed text-orange-700">
                            1) Tunjukkan QR ini ke kasir. 2) Kasir akan scan, verifikasi pesanan, lalu proses pembayaran tunai atau digital. 3) Status pesanan otomatis berubah setelah pembayaran berhasil.
                        </p>

                        <div className="mt-4 flex justify-center">
                            <div className="rounded-2xl border border-orange-100 bg-white p-3 shadow-sm">
                                <img
                                    src={order.qr_code_data_uri}
                                    alt={`QR pesanan ${order.order_number}`}
                                    className="h-48 w-48 rounded-xl"
                                />
                            </div>
                        </div>

                        <p className="mt-3 text-center text-sm font-semibold text-gray-700">
                            Order #{order.order_number}
                        </p>
                        <p className="mt-1 text-center text-xs text-gray-500">
                            Simpan layar ini sampai proses pembayaran selesai.
                        </p>
                    </div>
                </div>
            )}

            {/* Order Footer */}
            <div className="border-t border-gray-50 px-4 py-3 flex items-center justify-between">
                <div className="flex items-center gap-2">
                    <span className="text-xs text-gray-400">Pembayaran:</span>
                    {paymentStatus ? (
                        <span className={`text-xs font-semibold ${paymentStatus.color}`}>
                            {paymentStatus.label}
                        </span>
                    ) : (
                        <span className="text-xs text-gray-400">-</span>
                    )}
                </div>
                <div className="text-right">
                    <span className="text-sm font-bold text-orange-500">
                        Rp {order.total_price.toLocaleString('id-ID')}
                    </span>
                </div>
            </div>
        </div>
    );
}
