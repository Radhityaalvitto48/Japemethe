interface CartSummaryProps {
    totalItems: number;
    totalPrice: number;
    promoApplied: boolean;
    promoDiscount: number;
    finalTotal: number;
}

export default function CartSummary({
    totalItems,
    totalPrice,
    promoApplied,
    promoDiscount,
    finalTotal,
}: CartSummaryProps) {
    return (
        <section className="px-4 mt-6">
            <h2 className="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">
                Ringkasan
            </h2>
            <div className="rounded-xl border border-gray-100 bg-white p-4 shadow-sm space-y-2">
                <div className="flex justify-between text-sm">
                    <span className="text-gray-500">Subtotal ({totalItems} item)</span>
                    <span className="text-gray-700 font-medium">
                        Rp {totalPrice.toLocaleString('id-ID')}
                    </span>
                </div>
                {promoApplied && promoDiscount > 0 && (
                    <div className="flex justify-between text-sm">
                        <span className="text-green-600">Diskon promo</span>
                        <span className="text-green-600 font-medium">
                            -Rp {promoDiscount.toLocaleString('id-ID')}
                        </span>
                    </div>
                )}
                <div className="border-t border-gray-100 pt-2 mt-2 flex justify-between">
                    <span className="text-sm font-semibold text-gray-800">Total</span>
                    <span className="text-lg font-bold text-orange-500">
                        Rp {finalTotal.toLocaleString('id-ID')}
                    </span>
                </div>
            </div>
        </section>
    );
}
