import { Head, router } from '@inertiajs/react';
import { useCallback, useState } from 'react';
import {
    BottomNavigation,
    CartBar,
    CartModal,
    CategoryList,
    Header,
    MenuDetailModal,
    MenuGrid,
    PromoSection,
    SearchBar,
} from '@/components/menu';
import { type CartItem } from '@/components/menu/CartBar';
import { getMenuImage, type Menu } from '@/components/menu/MenuGrid';

// Types
interface Promo {
    id: number;
    code: string;
    name: string;
    type: string;
    value: number;
    minimum_price: number;
    status_promo: string;
    valid_from: string;
    valid_until: string;
}

interface MenuCategory {
    id: number;
    name: string;
    image: string;
    display: boolean;
    status_category: string;
}

interface Banner {
    id: number;
    image: string;
    title?: string;
}

interface Props {
    menus: Menu[];
    categories: MenuCategory[];
    promos: Promo[];
    banners?: Banner[];
    recommendedMenus: Menu[];
    selectedCategory?: number;
    searchQuery?: string;
}

export default function MenuPage({ menus, categories, promos, banners, recommendedMenus, selectedCategory, searchQuery }: Props) {
    const [search, setSearch] = useState(searchQuery || '');
    const [activeCategory, setActiveCategory] = useState<number | null>(selectedCategory || null);
    const [cart, setCart] = useState<CartItem[]>([]);
    const [selectedMenu, setSelectedMenu] = useState<Menu | null>(null);
    const [isDetailOpen, setIsDetailOpen] = useState(false);
    const [isCartOpen, setIsCartOpen] = useState(false);

    // Handle search
    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/', { search: search, category_id: activeCategory }, { preserveState: true, preserveScroll: true });
    };

    // Handle category filter
    const handleCategoryClick = (categoryId: number | null) => {
        setActiveCategory(categoryId);
        router.get('/', { search: search, category_id: categoryId }, { preserveState: true, preserveScroll: true });
    };

    // Add to cart (from + button on card)
    const handleAddToCart = useCallback((menu: Menu, quantity: number = 1) => {
        setCart((prev) => {
            const existing = prev.find((item) => item.menuId === menu.id);
            if (existing) {
                return prev.map((item) =>
                    item.menuId === menu.id
                        ? { ...item, quantity: item.quantity + quantity }
                        : item
                );
            }
            return [
                ...prev,
                {
                    menuId: menu.id,
                    name: menu.name,
                    price: menu.price,
                    quantity: quantity,
                    image: getMenuImage(menu),
                    note: '',
                },
            ];
        });
    }, []);

    // Quick add from grid + button
    const handleQuickAdd = useCallback((menu: Menu) => {
        handleAddToCart(menu, 1);
    }, [handleAddToCart]);

    // Increment quantity
    const handleIncrement = useCallback((menuId: number) => {
        setCart((prev) =>
            prev.map((item) =>
                item.menuId === menuId ? { ...item, quantity: item.quantity + 1 } : item
            )
        );
    }, []);

    // Decrement quantity (remove if qty becomes 0)
    const handleDecrement = useCallback((menuId: number) => {
        setCart((prev) =>
            prev
                .map((item) =>
                    item.menuId === menuId ? { ...item, quantity: item.quantity - 1 } : item
                )
                .filter((item) => item.quantity > 0)
        );
    }, []);

    // Remove item from cart
    const handleRemove = useCallback((menuId: number) => {
        setCart((prev) => prev.filter((item) => item.menuId !== menuId));
    }, []);

    // Update note for cart item
    const handleUpdateNote = useCallback((menuId: number, note: string) => {
        setCart((prev) =>
            prev.map((item) =>
                item.menuId === menuId ? { ...item, note } : item
            )
        );
    }, []);

    // Handle menu card click -> open detail
    const handleMenuClick = useCallback((menu: Menu) => {
        setSelectedMenu(menu);
        setIsDetailOpen(true);
    }, []);

    // Close detail modal
    const handleCloseDetail = useCallback(() => {
        setIsDetailOpen(false);
        setSelectedMenu(null);
    }, []);

    // Add to cart from detail modal (with specific quantity)
    const handleAddFromDetail = useCallback((menu: Menu, quantity: number) => {
        setCart((prev) => {
            const existing = prev.find((item) => item.menuId === menu.id);
            if (existing) {
                return prev.map((item) =>
                    item.menuId === menu.id
                        ? { ...item, quantity: quantity }
                        : item
                );
            }
            return [
                ...prev,
                {
                    menuId: menu.id,
                    name: menu.name,
                    price: menu.price,
                    quantity: quantity,
                    image: getMenuImage(menu),
                    note: '',
                },
            ];
        });
    }, []);

    // Get current quantity in cart for a menu (for detail modal)
    const getCartQuantity = (menuId: number): number => {
        return cart.find((item) => item.menuId === menuId)?.quantity ?? 0;
    };

    // Handle cart bar click -> open cart modal
    const handleCartClick = useCallback(() => {
        setIsCartOpen(true);
    }, []);

    // Handle order
    const handleOrder = useCallback(() => {
        console.log('Order placed:', cart);
        // TODO: send order to backend
        setIsCartOpen(false);
    }, [cart]);

    // Handle back button
    const handleBack = () => {
        router.get('/');
    };

    // Handle tab navigation
    const handleTabClick = (tab: string) => {
        console.log('Tab clicked:', tab);
    };

    const totalCartItems = cart.reduce((sum, item) => sum + item.quantity, 0);

    return (
        <>
            <Head title="Menu - Japemethe" />

            <div className="min-h-screen bg-white">
                <Header onBackClick={handleBack} cartCount={totalCartItems} />

                <PromoSection banners={banners} />

                <SearchBar
                    value={search}
                    onChange={setSearch}
                    onSubmit={handleSearch}
                />

                <CategoryList
                    categories={categories}
                    activeCategory={activeCategory}
                    onCategoryClick={handleCategoryClick}
                />

                <MenuGrid
                    menus={menus}
                    cart={cart}
                    onMenuClick={handleMenuClick}
                    onAddToCart={handleQuickAdd}
                    onIncrement={handleIncrement}
                    onDecrement={handleDecrement}
                />

                {/* Cart Bar */}
                <CartBar items={cart} onCartClick={handleCartClick} />

                {/* Menu Detail Modal */}
                <MenuDetailModal
                    menu={selectedMenu}
                    isOpen={isDetailOpen}
                    onClose={handleCloseDetail}
                    onAddToCart={handleAddFromDetail}
                    initialQuantity={selectedMenu ? getCartQuantity(selectedMenu.id) : 0}
                />

                {/* Cart Modal */}
                <CartModal
                    items={cart}
                    isOpen={isCartOpen}
                    onClose={() => setIsCartOpen(false)}
                    onIncrement={handleIncrement}
                    onDecrement={handleDecrement}
                    onRemove={handleRemove}
                    onUpdateNote={handleUpdateNote}
                    onOrder={handleOrder}
                />

                <BottomNavigation
                    activeTab="home"
                    onTabClick={handleTabClick}
                />
            </div>
        </>
    );
}
