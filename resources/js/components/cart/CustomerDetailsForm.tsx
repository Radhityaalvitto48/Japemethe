import { Phone, Mail, Tag } from 'lucide-react';

interface CustomerDetailsFormProps {
    phone: string;
    email: string;
    promoCode: string;
    promoError: string;
    promoApplied: boolean;
    promoDiscount: number;
    onPhoneChange: (value: string) => void;
    onEmailChange: (value: string) => void;
    onPromoChange: (value: string) => void;
    onValidatePromo: () => void;
}

export default function CustomerDetailsForm({
    phone,
    email,
    promoCode,
    promoError,
    promoApplied,
    promoDiscount,
    onPhoneChange,
    onEmailChange,
    onPromoChange,
    onValidatePromo,
}: CustomerDetailsFormProps) {
    return (
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
                        onChange={(e) => onPhoneChange(e.target.value)}
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
                        onChange={(e) => onEmailChange(e.target.value)}
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
                            onChange={(e) => onPromoChange(e.target.value)}
                            placeholder="Kode Promo (opsional)"
                            className="w-full rounded-xl border border-gray-200 py-2.5 pl-10 pr-4 text-sm text-gray-700 placeholder:text-gray-300 focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-100 uppercase"
                        />
                    </div>
                    {promoCode.trim() && !promoApplied && (
                        <button
                            onClick={onValidatePromo}
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
    );
}
