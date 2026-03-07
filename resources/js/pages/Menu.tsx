import { Head, router } from '@inertiajs/react';
import { useCallback, useState, useEffect } from 'react';
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
import { getMenuImage, type Menu } from '@/components/menu/MenuGrid';
import { TableProvider, useTable, CartProvider, useCart } from '@/contexts';

// Types
interface Table {
    id: number;
    table_number: string;
    seating_type: 'lesehan' | 'kursi';
    qr_code: string;
    is_active: boolean;
}

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
    currentTable?: Table;
}

export default function MenuPage({ menus, categories, promos, banners, recommendedMenus, selectedCategory, searchQuery, currentTable }: Props) {
    return (
        <TableProvider>
        <CartProvider>
            <MenuPageContent
                menus={menus}
                categories={categories}
                promos={promos}
                banners={banners}
                recommendedMenus={recommendedMenus}
                selectedCategory={selectedCategory}
                searchQuery={searchQuery}
                currentTable={currentTable}
            />
        </CartProvider>
        </TableProvider>
    );
}

function MenuPageContent({ menus, categories, promos, banners, recommendedMenus, selectedCategory, searchQuery, currentTable }: Props) {
    const { setCurrentTable } = useTable();
    const { cart, addToCart: ctxAddToCart, setItemQuantity, increment: ctxIncrement, decrement: ctxDecrement, remove: ctxRemove, updateNote: ctxUpdateNote, totalItems: totalCartItems } = useCart();
    const [search, setSearch] = useState(searchQuery || '');
    const [activeCategory, setActiveCategory] = useState<number | null>(selectedCategory || null);
    const [selectedMenu, setSelectedMenu] = useState<Menu | null>(null);
    const [isDetailOpen, setIsDetailOpen] = useState(false);
    const [isCartOpen, setIsCartOpen] = useState(false);

    // Set table from props if available (from scan route)
    useEffect(() => {
        if (currentTable) {
            setCurrentTable(currentTable);
        }
    }, [currentTable, setCurrentTable]);

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
        ctxAddToCart({
            menuId: menu.id,
            name: menu.name,
            price: menu.price,
            quantity: quantity,
            image: getMenuImage(menu),
        });
    }, [ctxAddToCart]);

    // Quick add from grid + button
    const handleQuickAdd = useCallback((menu: Menu) => {
        handleAddToCart(menu, 1);
    }, [handleAddToCart]);

    // Increment quantity
    const handleIncrement = useCallback((menuId: number) => {
        ctxIncrement(menuId);
    }, [ctxIncrement]);

    // Decrement quantity (remove if qty becomes 0)
    const handleDecrement = useCallback((menuId: number) => {
        ctxDecrement(menuId);
    }, [ctxDecrement]);

    // Remove item from cart
    const handleRemove = useCallback((menuId: number) => {
        ctxRemove(menuId);
    }, [ctxRemove]);

    // Update note for cart item
    const handleUpdateNote = useCallback((menuId: number, note: string) => {
        ctxUpdateNote(menuId, note);
    }, [ctxUpdateNote]);

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
        setItemQuantity(menu.id, quantity);
        // If item doesn't exist yet, add it
        const exists = cart.find((item) => item.menuId === menu.id);
        if (!exists && quantity > 0) {
            ctxAddToCart({
                menuId: menu.id,
                name: menu.name,
                price: menu.price,
                quantity: quantity,
                image: getMenuImage(menu),
            });
        }
    }, [setItemQuantity, cart, ctxAddToCart]);

    // Get current quantity in cart for a menu (for detail modal)
    const getCartQuantity = (menuId: number): number => {
        return cart.find((item) => item.menuId === menuId)?.quantity ?? 0;
    };

    // Handle cart bar click -> open cart modal
    const handleCartClick = useCallback(() => {
        setIsCartOpen(true);
    }, []);

    // Handle order -> navigate to cart/checkout page
    const handleOrder = useCallback(() => {
        setIsCartOpen(false);
        router.get('/cart');
    }, []);

    // Handle back button
    const handleBack = () => {
        router.get('/');
    };

    // Handle tab navigation
    const handleTabClick = (tab: string) => {
        console.log('Tab clicked:', tab);
    };

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
