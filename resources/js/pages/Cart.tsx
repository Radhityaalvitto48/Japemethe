import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Plus } from 'lucide-react';
import { CartProvider, useCart } from '@/contexts/CartContext';
import { TableProvider, useTable } from '@/contexts';
import {
    CartHeader,
    CartEmptyState,
    CartItemCard,
    CustomerDetailsForm,
    PaymentMethodInfo,
    CartSummary,
} from '@/components/cart';

export default function CartPage() {
    return (
        <TableProvider>
            <CartProvider>
                <CartPageContent />
            </CartProvider>
        </TableProvider>
    );
}

function CartPageContent() {
    const { cart, increment, decrement, remove, totalPrice, totalItems, clearCart } = useCart();
    const { currentTable } = useTable();

    const [phone, setPhone] = useState('');
    const [email, setEmail] = useState('');
    const [promoCode, setPromoCode] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [promoError, setPromoError] = useState('');
    const [promoDiscount, setPromoDiscount] = useState(0);
    const [promoApplied, setPromoApplied] = useState(false);

    const handlePromoChange = (value: string) => {
        setPromoCode(value.toUpperCase());
        setPromoError('');
        if (promoApplied) {
            setPromoApplied(false);
            setPromoDiscount(0);
        }
    };

    const handleValidatePromo = async () => {
        if (!promoCode.trim()) return;

        try {
            const res = await fetch('/api/promos/validate', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ code: promoCode, total_price: totalPrice }),
            });
            const data = await res.json();

            if (data.success && data.data) {
                setPromoDiscount(Math.min(data.data.discount_amount, totalPrice));
                setPromoApplied(true);
                setPromoError('');
            } else {
                setPromoError(data.message || 'Kode promo tidak valid');
                setPromoDiscount(0);
                setPromoApplied(false);
            }
        } catch {
            setPromoError('Gagal memvalidasi promo');
        }
    };

    const finalTotal = Math.max(0, totalPrice - promoDiscount);

    const handleOrder = async () => {
        if (cart.length === 0) return;
        if (!currentTable) {
            alert('Silakan scan QR meja terlebih dahulu.');
            return;
        }
        if (isSubmitting) return;

        setIsSubmitting(true);

        try {
            const res = await fetch('/api/orders', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    table_id: currentTable.id,
                    customer_phone: phone || null,
                    customer_email: email || null,
                    promo_code: promoApplied ? promoCode : null,
                    items: cart.map((item) => ({
                        menu_id: item.menuId,
                        quantity: item.quantity,
                        note: item.note || null,
                    })),
                }),
            });

            const data = await res.json();

            if (!data.success || !data.snap_token) {
                alert(data.message || 'Gagal membuat pesanan');
                setIsSubmitting(false);
                return;
            }

            const paymentId = data.data?.payment?.id;
            const orderId = data.data?.id;

            if (orderId) {
                const stored = JSON.parse(sessionStorage.getItem('order_ids') || '[]');
                if (!stored.includes(orderId)) {
                    stored.push(orderId);
                    sessionStorage.setItem('order_ids', JSON.stringify(stored));
                }
            }

            window.snap.pay(data.snap_token, {
                onSuccess: async () => {
                    if (paymentId) {
                        await fetch(`/api/payments/${paymentId}/status`, {
                            method: 'PUT',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ status_payment: 'completed' }),
                        }).catch(() => {});
                    }
                    clearCart();
                    router.get('/orders');
                },
                onPending: () => {
                    clearCart();
                    router.get('/orders');
                },
                onError: async () => {
                    if (paymentId) {
                        await fetch(`/api/payments/${paymentId}/status`, {
                            method: 'PUT',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ status_payment: 'failed' }),
                        }).catch(() => {});
                    }
                    alert('Pembayaran gagal. Silakan coba lagi.');
                    setIsSubmitting(false);
                },
                onClose: () => {
                    setIsSubmitting(false);
                },
            });
        } catch {
            alert('Terjadi kesalahan, coba lagi.');
            setIsSubmitting(false);
        }
    };

    return (
        <>
            <Head title="Keranjang - Japemethe" />

            <div className="min-h-screen bg-gray-100">
            <div className="relative mx-auto min-h-screen max-w-lg bg-gray-50/80 shadow-xl lg:max-w-3xl">
                <CartHeader totalItems={totalItems} />

                {cart.length === 0 ? (
                    <CartEmptyState />
                ) : (
                    <div className="pb-36">
                        {/* Cart Items */}
                        <section className="px-4 pt-4">
                            <h2 className="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">
                                Pesanan Kamu
                            </h2>
                            <div className="space-y-3">
                                {cart.map((item) => (
                                    <CartItemCard
                                        key={item.menuId}
                                        item={item}
                                        onIncrement={increment}
                                        onDecrement={decrement}
                                        onRemove={remove}
                                    />
                                ))}
                            </div>

                            <button
                                onClick={() => router.get('/')}
                                className="mt-3 flex w-full items-center justify-center gap-2 rounded-xl border-2 border-dashed border-orange-200 py-3 text-sm font-medium text-orange-500 hover:border-orange-400 hover:bg-orange-50/50 transition-all"
                            >
                                <Plus className="h-4 w-4" />
                                Tambah Menu Lain
                            </button>
                        </section>

                        <CustomerDetailsForm
                            phone={phone}
                            email={email}
                            promoCode={promoCode}
                            promoError={promoError}
                            promoApplied={promoApplied}
                            promoDiscount={promoDiscount}
                            onPhoneChange={setPhone}
                            onEmailChange={setEmail}
                            onPromoChange={handlePromoChange}
                            onValidatePromo={handleValidatePromo}
                        />

                        <PaymentMethodInfo />

                        <CartSummary
                            totalItems={totalItems}
                            totalPrice={totalPrice}
                            promoApplied={promoApplied}
                            promoDiscount={promoDiscount}
                            finalTotal={finalTotal}
                        />
                    </div>
                )}

                {/* Fixed bottom order button */}
                {cart.length > 0 && (
                    <div className="fixed bottom-0 left-1/2 z-40 w-full max-w-lg -translate-x-1/2 bg-white border-t border-gray-100 px-4 py-4 lg:max-w-3xl">
                        <button
                            onClick={handleOrder}
                            disabled={isSubmitting}
                            className="w-full rounded-full bg-orange-500 py-3.5 text-sm font-bold text-white shadow-md shadow-orange-500/25 transition-all hover:bg-orange-600 hover:shadow-lg active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {isSubmitting ? 'Memproses...' : `Konfirmasi Pesanan — Rp ${finalTotal.toLocaleString('id-ID')}`}
                        </button>
                    </div>
                )}
            </div>
            </div>
        </>
    );
}
