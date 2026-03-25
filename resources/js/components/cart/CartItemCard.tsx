import { Minus, Plus, Trash2 } from 'lucide-react';

export interface CartItem {
    menuId: number;
    name: string;
    price: number;
    quantity: number;
    image: string;
    note?: string;
}

interface CartItemCardProps {
    item: CartItem;
    onIncrement: (menuId: number) => void;
    onDecrement: (menuId: number) => void;
    onRemove: (menuId: number) => void;
}

export default function CartItemCard({ item, onIncrement, onDecrement, onRemove }: CartItemCardProps) {
    return (
        <div className="flex gap-3 rounded-xl border border-gray-100 bg-white p-3 shadow-sm">
            <img
                src={item.image}
                alt={item.name}
                className="h-18 w-18 shrink-0 rounded-lg object-cover"
                onError={(e) => {
                    (e.target as HTMLImageElement).src = '/images/placeholder.jpg';
                }}
            />

            <div className="flex flex-1 flex-col justify-between min-w-0">
                <div>
                    <h3 className="text-sm font-semibold text-gray-800 line-clamp-1">
                        {item.name}
                    </h3>
                    <p className="text-xs text-gray-400 mt-0.5">
                        Rp {(item.price * 1).toLocaleString('id-ID')} / item
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

            <div className="flex flex-col items-end justify-between shrink-0">
                <button
                    onClick={() => onRemove(item.menuId)}
                    className="flex h-7 w-7 items-center justify-center rounded-full text-gray-300 hover:text-red-500 hover:bg-red-50 transition-colors"
                >
                    <Trash2 className="h-4 w-4" />
                </button>

                <div className="flex items-center gap-2">
                    <button
                        onClick={() => onDecrement(item.menuId)}
                        className="flex h-7 w-7 items-center justify-center rounded-full border border-orange-300 text-orange-500 transition-all hover:bg-orange-50 active:scale-90"
                    >
                        <Minus className="h-3.5 w-3.5" />
                    </button>
                    <span className="w-5 text-center text-sm font-bold text-gray-800">
                        {item.quantity}
                    </span>
                    <button
                        onClick={() => onIncrement(item.menuId)}
                        className="flex h-7 w-7 items-center justify-center rounded-full bg-orange-500 text-white transition-all hover:bg-orange-600 active:scale-90"
                    >
                        <Plus className="h-3.5 w-3.5" />
                    </button>
                </div>
            </div>
        </div>
    );
}
