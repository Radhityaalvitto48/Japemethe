import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import {
    ArrowLeft,
    Minus,
    Plus,
    Trash2,
    Phone,
    Mail,
    Tag,
    ShieldCheck,
    ShoppingBag,
} from 'lucide-react';
import { CartProvider, useCart } from '@/contexts/CartContext';
import { TableProvider, useTable } from '@/contexts';

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

    const handleBack = () => {
        router.get('/');
    };

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
                const promo = data.data;
                let discount = 0;
                if (promo.type === 'percentage') {
                    discount = (totalPrice * promo.value) / 100;
                } else {
                    discount = promo.value;
                }
                setPromoDiscount(Math.min(discount, totalPrice));
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

            if (data.success) {
                clearCart();
                alert('Pesanan berhasil dibuat!');
                router.get('/');
            } else {
                alert(data.message || 'Gagal membuat pesanan');
            }
        } catch {
            alert('Terjadi kesalahan, coba lagi.');
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <>
            <Head title="Keranjang - Japemethe" />

            <div className="min-h-screen bg-gray-50/80">
                {/* Header */}
                <header className="sticky top-0 z-50 bg-white shadow-sm">
                    <div className="flex items-center gap-3 px-4 py-3">
                        <button
                            onClick={handleBack}
                            className="flex h-9 w-9 items-center justify-center rounded-full hover:bg-gray-100 transition-colors"
                        >
                            <ArrowLeft className="h-5 w-5 text-gray-700" />
                        </button>
                        <div>
                            <h1 className="text-lg font-bold text-gray-800">Keranjang</h1>
                            <p className="text-xs text-gray-400">{totalItems} item</p>
                        </div>
                    </div>
                </header>

                {cart.length === 0 ? (
                    /* Empty Cart */
                    <div className="flex flex-col items-center justify-center px-6 py-20">
                        <ShoppingBag className="h-20 w-20 text-gray-200 mb-4" />
                        <h2 className="text-lg font-semibold text-gray-500 mb-1">Keranjang Kosong</h2>
                        <p className="text-sm text-gray-400 text-center mb-6">
                            Belum ada menu yang ditambahkan ke keranjang.
                        </p>
                        <button
                            onClick={handleBack}
                            className="rounded-full bg-orange-500 px-6 py-2.5 text-sm font-semibold text-white shadow-md shadow-orange-500/25 hover:bg-orange-600 transition-all active:scale-[0.98]"
                        >
                            Lihat Menu
                        </button>
                    </div>
                ) : (
                    <div className="pb-36">
                        {/* Cart Items */}
                        <section className="px-4 pt-4">
                            <h2 className="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">
                                Pesanan Kamu
                            </h2>
                            <div className="space-y-3">
                                {cart.map((item) => (
                                    <div
                                        key={item.menuId}
                                        className="flex gap-3 rounded-xl border border-gray-100 bg-white p-3 shadow-sm"
                                    >
                                        {/* Image */}
                                        <img
                                            src={item.image}
                                            alt={item.name}
                                            className="h-18 w-18 shrink-0 rounded-lg object-cover"
                                            onError={(e) => {
                                                (e.target as HTMLImageElement).src = '/images/placeholder.jpg';
                                            }}
                                        />

                                        {/* Info */}
                                        <div className="flex flex-1 flex-col justify-between min-w-0">
                                            <div>
                                                <h3 className="text-sm font-semibold text-gray-800 line-clamp-1">
                                                    {item.name}
                                                </h3>
                                                <p className="text-xs text-gray-400 mt-0.5">
                                                    Rp {item.price.toLocaleString('id-ID')} / item
                                                </p>
                                                <p className="text-sm font-bold text-orange-500 mt-1">
                                                    Rp {(item.price * item.quantity).toLocaleString('id-ID')}
                                                </p>
                                            </div>

                                            {item.note && (
                                                <p className="text-xs text-gray-400 mt-1 line-clamp-1 italic">
                                                    📝 {item.note}
                                                </p>
                                            )}
                                        </div>

                                        {/* Quantity & Remove */}
                                        <div className="flex flex-col items-end justify-between shrink-0">
                                            <button
                                                onClick={() => remove(item.menuId)}
                                                className="flex h-7 w-7 items-center justify-center rounded-full text-gray-300 hover:text-red-500 hover:bg-red-50 transition-colors"
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </button>

                                            <div className="flex items-center gap-2">
                                                <button
                                                    onClick={() => decrement(item.menuId)}
                                                    className="flex h-7 w-7 items-center justify-center rounded-full border border-orange-300 text-orange-500 transition-all hover:bg-orange-50 active:scale-90"
                                                >
                                                    <Minus className="h-3.5 w-3.5" />
                                                </button>
                                                <span className="w-5 text-center text-sm font-bold text-gray-800">
                                                    {item.quantity}
                                                </span>
                                                <button
                                                    onClick={() => increment(item.menuId)}
                                                    className="flex h-7 w-7 items-center justify-center rounded-full bg-orange-500 text-white transition-all hover:bg-orange-600 active:scale-90"
                                                >
                                                    <Plus className="h-3.5 w-3.5" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>

                            {/* Add more items */}
                            <button
                                onClick={handleBack}
                                className="mt-3 flex w-full items-center justify-center gap-2 rounded-xl border-2 border-dashed border-orange-200 py-3 text-sm font-medium text-orange-500 hover:border-orange-400 hover:bg-orange-50/50 transition-all"
                            >
                                <Plus className="h-4 w-4" />
                                Tambah Menu Lain
                            </button>
                        </section>

                        {/* Customer Details */}
                        <section className="px-4 mt-6">
                            <h2 className="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">
                                Detail Pemesan
                            </h2>
                            <div className="rounded-xl border border-gray-100 bg-white p-4 shadow-sm space-y-3">
                                {/* Phone */}
                                <div className="relative">
                                    <Phone className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                                    <input
                                        type="tel"
                                        value={phone}
                                        onChange={(e) => setPhone(e.target.value)}
                                        placeholder="Nomor HP (opsional)"
                                        className="w-full rounded-xl border border-gray-200 py-2.5 pl-10 pr-4 text-sm text-gray-700 placeholder:text-gray-300 focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-100"
                                    />
                                </div>

                                {/* Email */}
                                <div className="relative">
                                    <Mail className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                                    <input
                                        type="email"
                                        value={email}
                                        onChange={(e) => setEmail(e.target.value)}
                                        placeholder="Email (opsional)"
                                        className="w-full rounded-xl border border-gray-200 py-2.5 pl-10 pr-4 text-sm text-gray-700 placeholder:text-gray-300 focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-100"
                                    />
                                </div>

                                {/* Promo Code */}
                                <div className="relative flex gap-2">
                                    <div className="relative flex-1">
                                        <Tag className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                                        <input
                                            type="text"
                                            value={promoCode}
                                            onChange={(e) => handlePromoChange(e.target.value)}
                                            placeholder="Kode Promo (opsional)"
                                            className="w-full rounded-xl border border-gray-200 py-2.5 pl-10 pr-4 text-sm text-gray-700 placeholder:text-gray-300 focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-100 uppercase"
                                        />
                                    </div>
                                    {promoCode.trim() && !promoApplied && (
                                        <button
                                            onClick={handleValidatePromo}
                                            className="shrink-0 rounded-xl bg-orange-500 px-4 text-sm font-semibold text-white hover:bg-orange-600 transition-colors active:scale-[0.97]"
                                        >
                                            Pakai
                                        </button>
                                    )}
                                </div>
                                {promoError && (
                                    <p className="text-xs text-red-500 mt-1">{promoError}</p>
                                )}
                                {promoApplied && (
                                    <p className="text-xs text-green-600 mt-1 font-medium">
                                        ✅ Promo berhasil diterapkan! Diskon Rp {promoDiscount.toLocaleString('id-ID')}
                                    </p>
                                )}
                            </div>
                        </section>

                        {/* Payment Method */}
                        <section className="px-4 mt-6">
                            <h2 className="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">
                                Metode Pembayaran
                            </h2>
                            <div className="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                                <div className="flex items-center gap-3">
                                    <div className="flex h-10 w-10 items-center justify-center rounded-full bg-orange-100">
                                        <ShieldCheck className="h-5 w-5 text-orange-600" />
                                    </div>
                                    <div>
                                        <p className="text-sm font-semibold text-gray-800">
                                            Pembayaran via Midtrans
                                        </p>
                                        <p className="text-xs text-gray-400">
                                            Pilihan metode pembayaran akan muncul setelah konfirmasi pesanan
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </section>

                        {/* Summary */}
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
                    </div>
                )}

                {/* Fixed bottom order button */}
                {cart.length > 0 && (
                    <div className="fixed right-0 bottom-0 left-0 z-40 bg-white border-t border-gray-100 px-4 py-4">
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
        </>
    );
}
