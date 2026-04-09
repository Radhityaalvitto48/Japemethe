import { ArrowLeft } from 'lucide-react';
import { router } from '@inertiajs/react';
import { getMenuPath } from '@/utils/menuNavigation';

interface CartHeaderProps {
    totalItems: number;
}

export default function CartHeader({ totalItems }: CartHeaderProps) {
    return (
        <header className="sticky top-0 z-50 bg-white shadow-sm">
            <div className="flex items-center gap-3 px-4 py-3 lg:mx-auto lg:max-w-3xl lg:px-8">
                <button
                    onClick={() => router.get(getMenuPath())}
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
    );
}
