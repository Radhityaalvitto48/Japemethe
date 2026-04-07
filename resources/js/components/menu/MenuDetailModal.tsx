import { Carousel, CarouselSlide } from '@/components/ui/Carousel';
import { Minus, Plus, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { getAllMenuImages, type Menu } from './MenuGrid';

interface MenuDetailModalProps {
    menu: Menu | null;
    isOpen: boolean;
    onClose: () => void;
    onAddToCart: (menu: Menu, quantity: number) => void;
    initialQuantity?: number;
}

export default function MenuDetailModal({ menu, isOpen, onClose, onAddToCart, initialQuantity = 0 }: MenuDetailModalProps) {
    const [quantity, setQuantity] = useState(1);
    const [resolvedMenu, setResolvedMenu] = useState<Menu | null>(menu);
    const [isFetchingDetail, setIsFetchingDetail] = useState(false);
    const detailCacheRef = useRef<Map<number, Menu>>(new Map());

    useEffect(() => {
        if (!menu) {
            setResolvedMenu(null);
            return;
        }

        const cached = detailCacheRef.current.get(menu.id);
        setResolvedMenu(cached ?? menu);
    }, [menu]);

    useEffect(() => {
        if (!isOpen || !menu?.id) {
            return;
        }

        const cached = detailCacheRef.current.get(menu.id);
        if (cached) {
            setResolvedMenu(cached);
            return;
        }

        const hasCompleteDetail = !!menu.description && !!menu.menu_images?.length;
        if (hasCompleteDetail) {
            return;
        }

        const controller = new AbortController();

        const fetchMenuDetail = async () => {
            setIsFetchingDetail(true);

            try {
                const response = await fetch(`/api/menus/${menu.id}`, {
                    signal: controller.signal,
                    headers: {
                        Accept: 'application/json',
                    },
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const payload: { success: boolean; data: Menu } = await response.json();
                if (payload.success && payload.data) {
                    detailCacheRef.current.set(menu.id, payload.data);
                    setResolvedMenu(payload.data);
                }
            } catch (error) {
                if (error instanceof Error && error.name === 'AbortError') {
                    return;
                }
            } finally {
                setIsFetchingDetail(false);
            }
        };

        void fetchMenuDetail();

        return () => {
            controller.abort();
        };
    }, [isOpen, menu]);

    const displayMenu = resolvedMenu ?? menu;

    useEffect(() => {
        if (isOpen && menu) {
            setQuantity(initialQuantity > 0 ? initialQuantity : 1);
        }
    }, [isOpen, menu?.id, initialQuantity]);

    // Lock body scroll when modal is open
    useEffect(() => {
        if (isOpen) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
        return () => {
            document.body.style.overflow = '';
        };
    }, [isOpen]);

    if (!isOpen || !displayMenu) return null;

    const images = getAllMenuImages(displayMenu);
    const canSlide = images.length > 1;
    const isOutOfStock = displayMenu.stock === 0;
    const maxQty = Math.min(displayMenu.stock, 99);

    const handleIncrement = () => {
        if (quantity < maxQty) setQuantity((q) => q + 1);
    };

    const handleDecrement = () => {
        if (quantity > 1) setQuantity((q) => q - 1);
    };

    const handleAddToCart = () => {
        onAddToCart(displayMenu, quantity);
        onClose();
    };

    return (
        <div className="fixed inset-0 z-100 flex items-end sm:items-center justify-center">
            {/* Backdrop */}
            <div
                className="absolute inset-0 bg-black/50 backdrop-blur-sm"
                onClick={onClose}
            />

            {/* Modal Content */}
            <div className="relative z-10 w-full max-w-lg max-h-[90vh] overflow-y-auto bg-white rounded-t-2xl sm:rounded-2xl animate-slide-up">
                {/* Close Button */}
                <button
                    onClick={onClose}
                    className="absolute top-3 right-3 z-20 flex h-8 w-8 items-center justify-center rounded-full bg-white/90 shadow-md transition-colors hover:bg-gray-100"
                >
                    <X className="h-5 w-5 text-gray-600" />
                </button>

                {/* Image Carousel */}
                <div className="relative w-full">
                    {canSlide ? (
                        <Carousel
                            autoplay
                            autoplayInterval={4000}
                            showArrows={canSlide}
                            showDots={canSlide}
                            className="w-full"
                        >
                            {images.map((img, idx) => (
                                <CarouselSlide key={idx}>
                                    <div className="aspect-4/3 w-full overflow-hidden bg-gray-100">
                                        <img
                                            src={img}
                                            alt={`${displayMenu.name} - ${idx + 1}`}
                                            className="h-full w-full object-cover"
                                            onError={(e) => {
                                                (e.target as HTMLImageElement).src = '/images/placeholder.jpg';
                                            }}
                                        />
                                    </div>
                                </CarouselSlide>
                            ))}
                        </Carousel>
                    ) : (
                        <div className="aspect-4/3 w-full overflow-hidden bg-gray-100">
                            <img
                                src={images[0]}
                                alt={displayMenu.name}
                                className="h-full w-full object-cover"
                                onError={(e) => {
                                    (e.target as HTMLImageElement).src = '/images/placeholder.jpg';
                                }}
                            />
                        </div>
                    )}

                    {isOutOfStock && (
                        <div className="absolute inset-0 flex items-center justify-center bg-black/40">
                            <span className="rounded-full bg-white px-4 py-2 text-sm font-bold text-gray-700">
                                Stok Habis
                            </span>
                        </div>
                    )}
                </div>

                {/* Detail Content */}
                <div className="p-5">
                    {/* Category Badge */}
                    {displayMenu.menu_category && (
                        <span className="inline-block rounded-full bg-orange-50 px-3 py-1 text-xs font-medium text-orange-600">
                            {displayMenu.menu_category.name}
                        </span>
                    )}

                    {/* Name & Price */}
                    <h2 className="mt-3 text-xl font-bold text-gray-900">{displayMenu.name}</h2>
                    <p className="mt-1 text-lg font-bold text-orange-500">
                        Rp {(displayMenu.price * 1).toLocaleString('id-ID')}
                    </p>

                    {/* Stock info */}
                    {!isOutOfStock && (
                        <p className="mt-1 text-xs text-gray-400">Stok tersedia: {displayMenu.stock}</p>
                    )}

                    {/* Description */}
                    {displayMenu.description && (
                        <div className="mt-4">
                            <h3 className="text-sm font-semibold text-gray-700">Deskripsi</h3>
                            <div
                                className="mt-1 text-sm leading-relaxed text-gray-500"
                                dangerouslySetInnerHTML={{ __html: displayMenu.description }}
                            />
                        </div>
                    )}
                    {isFetchingDetail && !displayMenu.description && (
                        <p className="mt-3 text-xs text-gray-400">Memuat detail menu...</p>
                    )}

                    {/* Quantity Selector & Add to Cart */}
                    {!isOutOfStock && (
                        <div className="mt-6 flex items-center gap-4">
                            {/* Quantity */}
                            <div className="flex items-center gap-3 rounded-full border border-gray-200 px-2 py-1">
                                <button
                                    onClick={handleDecrement}
                                    disabled={quantity <= 1}
                                    className="flex h-8 w-8 items-center justify-center rounded-full text-gray-500 transition-colors hover:bg-gray-100 disabled:opacity-30"
                                >
                                    <Minus className="h-4 w-4" />
                                </button>
                                <span className="w-8 text-center text-sm font-bold text-gray-800">
                                    {quantity}
                                </span>
                                <button
                                    onClick={handleIncrement}
                                    disabled={quantity >= maxQty}
                                    className="flex h-8 w-8 items-center justify-center rounded-full text-gray-500 transition-colors hover:bg-gray-100 disabled:opacity-30"
                                >
                                    <Plus className="h-4 w-4" />
                                </button>
                            </div>

                            {/* Add to Cart Button */}
                            <button
                                onClick={handleAddToCart}
                                className="flex-1 rounded-full bg-orange-500 px-6 py-3 text-sm font-bold text-white shadow-md transition-all hover:bg-orange-600 hover:shadow-lg active:scale-[0.98]"
                            >
                                Tambah - Rp {(displayMenu.price * quantity).toLocaleString('id-ID')}
                            </button>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
