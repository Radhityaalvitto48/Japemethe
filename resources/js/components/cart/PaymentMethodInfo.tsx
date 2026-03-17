import { ShieldCheck } from 'lucide-react';

export default function PaymentMethodInfo() {
    return (
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
    );
}
