import { ShoppingBag } from 'lucide-react';
import { router } from '@inertiajs/react';

export default function CartEmptyState() {
    return (
        <div className="flex flex-col items-center justify-center px-6 py-20">
            <ShoppingBag className="h-20 w-20 text-gray-200 mb-4" />
            <h2 className="text-lg font-semibold text-gray-500 mb-1">Keranjang Kosong</h2>
            <p className="text-sm text-gray-400 text-center mb-6">
                Belum ada menu yang ditambahkan ke keranjang.
            </p>
            <button
                onClick={() => router.get('/')}
                className="rounded-full bg-orange-500 px-6 py-2.5 text-sm font-semibold text-white shadow-md shadow-orange-500/25 hover:bg-orange-600 transition-all active:scale-[0.98]"
            >
                Lihat Menu
            </button>
        </div>
    );
}
