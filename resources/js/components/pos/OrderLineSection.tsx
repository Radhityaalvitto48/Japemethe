type PaymentFilter = 'all' | 'unpaid' | 'paid';

type PosOrder = {
    id: number;
    order_number: string;
    table_number: string | null;
    items: Array<{ menu_name: string | null }>;
    total_price: number;
    payment: {
        status_payment: 'pending' | 'completed' | 'failed';
    } | null;
};

type OrderLineSectionProps = {
    paymentFilter: PaymentFilter;
    onChangeFilter: (filter: PaymentFilter) => void;
    totalActiveOrders: number;
    unpaidOrders: number;
    paidOrders: number;
    orders: PosOrder[];
    selectedOrderId: number | null;
    scannedOrderId: number | null;
    onSelectOrder: (orderId: number) => void;
    formatRupiah: (value: number) => string;
};

export default function OrderLineSection({
    paymentFilter,
    onChangeFilter,
    totalActiveOrders,
    unpaidOrders,
    paidOrders,
    orders,
    selectedOrderId,
    scannedOrderId,
    onSelectOrder,
    formatRupiah,
}: OrderLineSectionProps): React.JSX.Element {
    return (
        <>
            <div>
                <h3 className="text-xl font-semibold text-gray-900">Order Line</h3>
                <div className="mt-3 flex flex-wrap gap-2 text-xs font-semibold">
                    <button
                        type="button"
                        onClick={() => onChangeFilter('all')}
                        className={`rounded-full border px-3 py-1 ${paymentFilter === 'all' ? 'border-gray-400 bg-gray-100 text-gray-700' : 'border-gray-200 bg-gray-50 text-gray-600'}`}
                    >All {totalActiveOrders}</button>
                    <button
                        type="button"
                        onClick={() => onChangeFilter('unpaid')}
                        className={`rounded-full border px-3 py-1 ${paymentFilter === 'unpaid' ? 'border-amber-300 bg-amber-100 text-amber-700' : 'border-amber-200 bg-amber-50 text-amber-700'}`}
                    >Belum Bayar {unpaidOrders}</button>
                    <button
                        type="button"
                        onClick={() => onChangeFilter('paid')}
                        className={`rounded-full border px-3 py-1 ${paymentFilter === 'paid' ? 'border-emerald-300 bg-emerald-100 text-emerald-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'}`}
                    >Lunas {paidOrders}</button>
                </div>
            </div>

            <div className="grid gap-2 md:grid-cols-2 xl:grid-cols-3">
                {orders.slice(0, 9).map((order) => (
                    <button
                        key={order.id}
                        id={`order-card-${order.id}`}
                        type="button"
                        onClick={() => onSelectOrder(order.id)}
                        className={`rounded-xl border p-3 text-left transition ${
                            selectedOrderId === order.id
                                ? 'border-amber-300 bg-amber-50'
                                : scannedOrderId === order.id
                                  ? 'border-sky-300 bg-sky-50 ring-1 ring-sky-200'
                                  : 'border-gray-200 hover:border-amber-200 hover:bg-amber-50/40'
                        }`}
                    >
                        <div className="flex items-center justify-between">
                            <p className="text-sm font-semibold text-gray-900">Order #{order.order_number}</p>
                            <p className="text-xs text-gray-500">Meja {order.table_number ?? '-'}</p>
                        </div>
                        {scannedOrderId === order.id && (
                            <p className="mt-1 text-[11px] font-semibold text-sky-700">Hasil scan terbaru</p>
                        )}
                        <p className="mt-2 text-sm text-gray-700">{order.items[0]?.menu_name ?? 'Pesanan baru'}</p>
                        <div className="mt-2 flex items-center justify-between">
                            <span className={`text-xs font-semibold uppercase ${order.payment?.status_payment === 'completed' ? 'text-emerald-700' : 'text-amber-700'}`}>
                                {order.payment?.status_payment === 'completed' ? 'LUNAS' : 'BELUM BAYAR'}
                            </span>
                            <span className="text-xs font-semibold text-gray-800">{formatRupiah(order.total_price)}</span>
                        </div>
                    </button>
                ))}
            </div>

            {orders.length === 0 && (
                <p className="rounded-lg border border-dashed border-gray-300 p-3 text-xs text-gray-500">
                    Tidak ada order pada filter ini.
                </p>
            )}
        </>
    );
}
