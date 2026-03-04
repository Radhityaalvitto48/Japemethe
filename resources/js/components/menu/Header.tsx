import { ChevronLeft, ShoppingCart } from 'lucide-react';

interface HeaderProps {
    onBackClick?: () => void;
    cartCount?: number;
}

export default function Header({ onBackClick, cartCount = 0 }: HeaderProps) {
    return (
        <header className="sticky top-0 z-50 bg-white shadow-sm">
            <div className="flex items-center justify-between px-4 py-3">
                <h1 className="text-lg font-bold tracking-wide text-gray-800">JAPEMETHE</h1>
                <button className="relative rounded-full p-2 hover:bg-gray-100">
                    <ShoppingCart className="h-6 w-6 text-gray-700" />
                    <span className="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-orange-500 text-xs font-bold text-white">
                        {cartCount}
                    </span>
                </button>
            </div>
        </header>
    );
}
