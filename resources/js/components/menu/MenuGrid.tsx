import { Minus, Plus } from 'lucide-react';
import { type CartItem } from './CartBar';

export interface MenuImage {
    id: number;
    menu_id: number;
    image: string[];
}

export interface MenuCategory {
    id: number;
    name: string;
    image: string;
    display: boolean;
    status_category: string;
}

export interface Menu {
    id: number;
    menu_category_id: number;
    name: string;
    slug: string;
    description: string;
    price: number;
    stock: number;
    is_recommended: boolean;
    status_menu: string;
    menu_category: MenuCategory;
    menu_images: MenuImage[];
}

interface MenuGridProps {
    menus: Menu[];
    cart: CartItem[];
    onMenuClick?: (menu: Menu) => void;
    onAddToCart?: (menu: Menu) => void;
    onIncrement?: (menuId: number) => void;
    onDecrement?: (menuId: number) => void;
}

// Get image URL
export const getImageUrl = (path: string): string => {
    if (!path) return '/images/placeholder.jpg';
    if (path.startsWith('http')) return path;
    return `/storage/${path}`;
};

// Get first image from menu
export const getMenuImage = (menu: Menu): string => {
    if (menu.menu_images && menu.menu_images.length > 0) {
        const images = menu.menu_images[0].image;
        if (Array.isArray(images) && images.length > 0) {
            return getImageUrl(images[0]);
        }
    }
    return '/images/placeholder.jpg';
};

// Get all images from menu
export const getAllMenuImages = (menu: Menu): string[] => {
    const images: string[] = [];
    if (menu.menu_images && menu.menu_images.length > 0) {
        menu.menu_images.forEach((mi) => {
            if (Array.isArray(mi.image)) {
                mi.image.forEach((img) => images.push(getImageUrl(img)));
            }
        });
    }
    return images.length > 0 ? images : ['/images/placeholder.jpg'];
};

export default function MenuGrid({ menus, cart, onMenuClick, onAddToCart, onIncrement, onDecrement }: MenuGridProps) {
    const getCartQty = (menuId: number): number => {
        return cart.find((item) => item.menuId === menuId)?.quantity ?? 0;
    };

    if (menus.length === 0) {
        return (
            <section className="px-4 pb-32">
                <div className="flex flex-col items-center justify-center py-16 text-center">
                    <div className="mb-4 text-6xl">🍽️</div>
                    <h3 className="mb-2 text-base font-semibold text-gray-700">Menu tidak ditemukan</h3>
                    <p className="text-sm text-gray-500">Coba kata kunci lain</p>
                </div>
            </section>
        );
    }

    return (
        <section className="px-4 pb-32">
            <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                {menus.map((menu) => {
                    const qty = getCartQty(menu.id);

                    return (
                        <div
                            key={menu.id}
                            onClick={() => onMenuClick?.(menu)}
                            className="group relative cursor-pointer overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md"
                        >
                            {/* Image */}
                            <div className="relative aspect-square overflow-hidden bg-gray-100">
                                <img
                                    src={getMenuImage(menu)}
                                    alt={menu.name}
                                    className="h-full w-full object-cover transition-transform group-hover:scale-105"
                                />
                                {menu.stock === 0 && (
                                    <div className="absolute inset-0 flex items-center justify-center bg-black/40">
                                        <span className="rounded-full bg-white px-3 py-1 text-xs font-semibold text-gray-700">
                                            Habis
                                        </span>
                                    </div>
                                )}
                                {/* Cart badge on image */}
                                {qty > 0 && (
                                    <div className="absolute top-2 right-2 flex h-6 w-6 items-center justify-center rounded-full bg-orange-500 text-xs font-bold text-white shadow">
                                        {qty}
                                    </div>
                                )}
                            </div>

                            {/* Content */}
                            <div className="p-3">
                                <h3 className="line-clamp-1 text-sm font-semibold text-gray-800">{menu.name}</h3>
                                <p className="mt-2 text-sm font-bold text-orange-500">
                                    Rp {(menu.price * 1).toLocaleString('id-ID')}
                                </p>

                                <div className="mt-3">
                                    {menu.stock === 0 ? (
                                        <button
                                            disabled
                                            className="w-full rounded-lg border border-gray-300 bg-gray-50 py-2 text-sm font-medium text-gray-400 cursor-not-allowed"
                                        >
                                            Habis
                                        </button>
                                    ) : qty === 0 ? (
                                        /* Add button */
                                        <button
                                            onClick={(e) => {
                                                e.stopPropagation();
                                                onAddToCart?.(menu);
                                            }}
                                            className="w-full rounded-lg border border-orange-500 bg-white py-2 text-sm font-semibold text-orange-500 transition-all hover:bg-orange-50 active:scale-[0.98]"
                                            aria-label={`Tambah ${menu.name} ke keranjang`}
                                        >
                                            Add
                                        </button>
                                    ) : (
                                        /* Quantity selector */
                                        <div
                                            className="flex w-full items-center justify-between rounded-lg border border-orange-500 bg-white px-3 py-2"
                                            onClick={(e) => e.stopPropagation()}
                                        >
                                            <button
                                                onClick={() => onDecrement?.(menu.id)}
                                                className="flex h-6 w-6 items-center justify-center rounded-full border border-orange-300 text-orange-500 transition-all hover:bg-orange-50 active:scale-90"
                                            >
                                                <Minus className="h-3 w-3" />
                                            </button>
                                            <span className="text-sm font-bold text-orange-500">
                                                {qty}
                                            </span>
                                            <button
                                                onClick={() => onIncrement?.(menu.id)}
                                                className="flex h-6 w-6 items-center justify-center rounded-full bg-orange-500 text-white transition-all hover:bg-orange-600 active:scale-90"
                                            >
                                                <Plus className="h-3 w-3" />
                                            </button>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    );
                })}
            </div>
        </section>
    );
}
