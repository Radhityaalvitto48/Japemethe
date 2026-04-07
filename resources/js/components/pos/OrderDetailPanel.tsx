type PaymentMode = 'cash' | 'qris';

type PosOrderItem = {
    id: number;
    menu_name: string | null;
    quantity: number;
    subtotal: number;
    note: string | null;
};

type PosOrder = {
    id: number;
    order_number: string;
    table_number: string | null;
    total_price: number;
    discount_amount: number;
    promo: {
        code: string;
    } | null;
    items: PosOrderItem[];
};

type OrderDetailPanelProps = {
    selectedOrder: PosOrder | null;
    selectedPaymentStatus: string;
    selectedOrderSubtotal: number;
    selectedOrderTax: number;
    selectedOrderFinal: number;
    selectedOrderItems: PosOrderItem[];
    promoCode: string;
    onPromoCodeChange: (value: string) => void;
    onApplyPromo: () => void;
    onRemovePromo: () => void;
    paymentMode: PaymentMode;
    onPaymentModeChange: (mode: PaymentMode) => void;
    cashReceived: string;
    onCashReceivedChange: (value: string) => void;
    cashReceivedValue: number;
    changeAmount: number;
    onPayAction: () => void;
    onUpdateOrderItemQuantity: (detailId: number, quantity: number) => void;
    formatRupiah: (value: number) => string;
};

export default function OrderDetailPanel({
    selectedOrder,
    selectedPaymentStatus,
    selectedOrderSubtotal,
    selectedOrderTax,
    selectedOrderFinal,
    selectedOrderItems,
    promoCode,
    onPromoCodeChange,
    onApplyPromo,
    onRemovePromo,
    paymentMode,
    onPaymentModeChange,
    cashReceived,
    onCashReceivedChange,
    cashReceivedValue,
    changeAmount,
    onPayAction,
    onUpdateOrderItemQuantity,
    formatRupiah,
}: OrderDetailPanelProps): React.JSX.Element {
    return (
        <section className="space-y-3 rounded-2xl border border-gray-200 bg-white p-4 lg:absolute lg:top-0 lg:right-0 lg:bottom-0 lg:w-[32%] lg:overflow-y-auto">
            <div className="flex items-start justify-between">
                <div>
                    <p className="text-lg font-semibold text-gray-900">{selectedOrder ? `Order #${selectedOrder.order_number}` : 'Order Detail'}</p>
                    <p className="text-xs text-gray-500">
                        {selectedOrder ? `Meja ${selectedOrder.table_number ?? '-'} • ${selectedPaymentStatus}` : 'Pilih order dari daftar'}
                    </p>
                </div>
            </div>

            {!selectedOrder && (
                <p className="rounded-lg border border-dashed border-gray-300 p-4 text-sm text-gray-500">
                    Pelanggan ke kasir, scan barcode, validasi bayar, selesai.
                </p>
            )}

            {selectedOrder && (
                <>
                    <div className="rounded-xl border border-gray-200 p-3">
                        <p className="text-sm font-semibold text-gray-800">Ordered Items</p>
                        <div className="mt-2 space-y-2 text-sm">
                            {selectedOrderItems.map((item) => (
                                <div key={item.id} className="rounded-lg bg-gray-50 p-2">
                                    <div className="flex items-center justify-between">
                                        <span className="text-gray-700">{item.menu_name ?? '-'}</span>
                                        <span className="font-medium text-gray-900">{formatRupiah(item.subtotal)}</span>
                                    </div>
                                    {item.note && (
                                        <p className="mt-1 text-xs text-gray-500">Catatan: {item.note}</p>
                                    )}
                                    <div className="mt-2 flex items-center justify-between">
                                        <div className="flex items-center gap-2">
                                            <button
                                                type="button"
                                                onClick={() => onUpdateOrderItemQuantity(item.id, item.quantity - 1)}
                                                className="rounded border border-gray-300 px-2 py-0.5 text-xs"
                                            >
                                                -
                                            </button>
                                            <span className="text-xs font-semibold text-gray-700">{item.quantity}</span>
                                            <button
                                                type="button"
                                                onClick={() => onUpdateOrderItemQuantity(item.id, item.quantity + 1)}
                                                className="rounded border border-gray-300 px-2 py-0.5 text-xs"
                                            >
                                                +
                                            </button>
                                        </div>
                                        <button
                                            type="button"
                                            onClick={() => onUpdateOrderItemQuantity(item.id, 0)}
                                            className="rounded-md bg-rose-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-rose-700"
                                        >
                                            Hapus
                                        </button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>

                    <div className="rounded-xl border border-gray-200 p-3">
                        <p className="text-sm font-semibold text-gray-800">Payment Summary</p>
                        <div className="mt-2 space-y-1 text-sm">
                            <div className="flex items-center justify-between text-gray-600">
                                <span>Subtotal</span>
                                <span>{formatRupiah(selectedOrderSubtotal)}</span>
                            </div>
                            <div className="flex items-center justify-between text-gray-600">
                                <span>Tax (11%)</span>
                                <span>{formatRupiah(selectedOrderTax)}</span>
                            </div>
                            <div className="flex items-center justify-between text-gray-600">
                                <span>Discount</span>
                                <span>-{formatRupiah(selectedOrder.discount_amount)}</span>
                            </div>
                            <div className="mt-2 flex items-center justify-between border-t border-gray-200 pt-2 text-base font-semibold text-gray-900">
                                <span>Total</span>
                                <span>{formatRupiah(selectedOrderFinal)}</span>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-xl border border-gray-200 p-3">
                        <p className="text-sm font-semibold text-gray-800">Promo</p>
                        <div className="mt-2 flex gap-2">
                            <input
                                value={promoCode}
                                onChange={(event) => onPromoCodeChange(event.target.value)}
                                placeholder="Kode promo"
                                className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                            />
                            <button
                                type="button"
                                onClick={onApplyPromo}
                                className="rounded-lg bg-amber-500 px-3 py-2 text-xs font-semibold text-white"
                            >
                                Apply
                            </button>
                        </div>
                        {selectedOrder.promo && (
                            <div className="mt-2 flex items-center justify-between rounded-lg bg-amber-50 px-2 py-2 text-xs text-amber-700">
                                <span>{selectedOrder.promo.code}</span>
                                <button type="button" onClick={onRemovePromo} className="font-semibold">
                                    Hapus
                                </button>
                            </div>
                        )}
                    </div>

                    <div className="rounded-xl border border-gray-200 p-3">
                        <p className="text-sm font-semibold text-gray-800">Payment Method</p>
                        <div className="mt-2 grid grid-cols-2 gap-2 text-xs font-semibold">
                            <button
                                type="button"
                                onClick={() => onPaymentModeChange('cash')}
                                className={`rounded-lg border px-3 py-2 ${paymentMode === 'cash' ? 'border-amber-300 bg-amber-50 text-amber-700' : 'border-gray-300 text-gray-700'}`}
                            >
                                Cash
                            </button>
                            <button
                                type="button"
                                onClick={() => onPaymentModeChange('qris')}
                                className={`rounded-lg border px-3 py-2 ${paymentMode === 'qris' ? 'border-amber-300 bg-amber-50 text-amber-700' : 'border-gray-300 text-gray-700'}`}
                            >
                                QRIS
                            </button>
                        </div>

                        {paymentMode === 'cash' && (
                            <>
                                <input
                                    value={cashReceived}
                                    onChange={(event) => onCashReceivedChange(event.target.value)}
                                    placeholder="Nominal tunai diterima (IDR)"
                                    className="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                                />
                                <div className="mt-2 rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-700">
                                    <div className="flex items-center justify-between">
                                        <span>Diterima</span>
                                        <span className="font-semibold">{formatRupiah(cashReceivedValue)}</span>
                                    </div>
                                    <div className="mt-1 flex items-center justify-between">
                                        <span>Kembalian</span>
                                        <span className="font-semibold text-emerald-700">{formatRupiah(changeAmount)}</span>
                                    </div>
                                </div>
                            </>
                        )}

                        <button
                            type="button"
                            onClick={onPayAction}
                            className="mt-3 w-full rounded-lg bg-amber-500 px-3 py-2.5 text-sm font-semibold text-white hover:bg-amber-600"
                        >
                            {paymentMode === 'cash' ? 'Konfirmasi Bayar Cash' : 'Konfirmasi Bayar QRIS'}
                        </button>

                        {paymentMode === 'qris' && (
                            <p className="mt-2 text-xs text-gray-500">
                                Kasir validasi manual setelah pelanggan menunjukkan bukti bayar QRIS.
                            </p>
                        )}
                    </div>
                </>
            )}
        </section>
    );
}
