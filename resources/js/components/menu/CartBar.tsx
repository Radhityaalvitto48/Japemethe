import { ShoppingCart } from 'lucide-react';

export interface CartItem {
    menuId: number;
    name: string;
    price: number;
    quantity: number;
    image: string;
    note: string;
}

interface CartBarProps {
    items: CartItem[];
    onCartClick: () => void;
}

export default function CartBar({ items, onCartClick }: CartBarProps) {
    if (items.length === 0) return null;

    const totalItems = items.reduce((sum, item) => sum + item.quantity, 0);
    const totalPrice = items.reduce((sum, item) => sum + item.price * item.quantity, 0);

    return (
        <div className="fixed right-0 bottom-16 left-0 z-40 px-4 pb-2">
            <button
                onClick={onCartClick}
                className="flex w-full items-center justify-between rounded-2xl bg-orange-500 px-5 py-3.5 shadow-lg shadow-orange-500/30 transition-all hover:bg-orange-600 hover:shadow-xl hover:shadow-orange-500/40 active:scale-[0.98]"
            >
                {/* Left: Cart icon + item count */}
                <div className="flex items-center gap-3">
                    <div className="relative">
                        <ShoppingCart className="h-5 w-5 text-white" />
                        <span className="absolute -top-2 -right-2 flex h-4 w-4 items-center justify-center rounded-full bg-white text-[10px] font-bold text-orange-500">
                            {totalItems}
                        </span>
                    </div>
                    <span className="text-sm font-semibold text-white">
                        {totalItems} item
                    </span>
                </div>

                {/* Right: Total price */}
                <span className="text-sm font-bold text-white">
                    Rp {totalPrice.toLocaleString('id-ID')}
                </span>
            </button>
        </div>
    );
}
