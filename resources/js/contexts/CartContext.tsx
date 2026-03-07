import { createContext, useCallback, useContext, useEffect, useState } from 'react';

export interface CartItem {
    menuId: number;
    name: string;
    price: number;
    quantity: number;
    image: string;
    note: string;
}

interface CartContextType {
    cart: CartItem[];
    addToCart: (item: Omit<CartItem, 'note'> & { note?: string }) => void;
    setItemQuantity: (menuId: number, quantity: number) => void;
    increment: (menuId: number) => void;
    decrement: (menuId: number) => void;
    remove: (menuId: number) => void;
    updateNote: (menuId: number, note: string) => void;
    clearCart: () => void;
    totalItems: number;
    totalPrice: number;
    getQuantity: (menuId: number) => number;
}

const CartContext = createContext<CartContextType | undefined>(undefined);

const CART_STORAGE_KEY = 'japemethe_cart';

function loadCart(): CartItem[] {
    try {
        const saved = localStorage.getItem(CART_STORAGE_KEY);
        return saved ? JSON.parse(saved) : [];
    } catch {
        return [];
    }
}

function saveCart(cart: CartItem[]) {
    localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(cart));
}

export function CartProvider({ children }: { children: React.ReactNode }) {
    const [cart, setCart] = useState<CartItem[]>(loadCart);

    useEffect(() => {
        saveCart(cart);
    }, [cart]);

    const addToCart = useCallback((item: Omit<CartItem, 'note'> & { note?: string }) => {
        setCart((prev) => {
            const existing = prev.find((i) => i.menuId === item.menuId);
            if (existing) {
                return prev.map((i) =>
                    i.menuId === item.menuId
                        ? { ...i, quantity: i.quantity + item.quantity }
                        : i
                );
            }
            return [...prev, { ...item, note: item.note ?? '' }];
        });
    }, []);

    const setItemQuantity = useCallback((menuId: number, quantity: number) => {
        setCart((prev) => {
            if (quantity <= 0) return prev.filter((i) => i.menuId !== menuId);
            return prev.map((i) =>
                i.menuId === menuId ? { ...i, quantity } : i
            );
        });
    }, []);

    const increment = useCallback((menuId: number) => {
        setCart((prev) =>
            prev.map((i) =>
                i.menuId === menuId ? { ...i, quantity: i.quantity + 1 } : i
            )
        );
    }, []);

    const decrement = useCallback((menuId: number) => {
        setCart((prev) =>
            prev
                .map((i) =>
                    i.menuId === menuId ? { ...i, quantity: i.quantity - 1 } : i
                )
                .filter((i) => i.quantity > 0)
        );
    }, []);

    const remove = useCallback((menuId: number) => {
        setCart((prev) => prev.filter((i) => i.menuId !== menuId));
    }, []);

    const updateNote = useCallback((menuId: number, note: string) => {
        setCart((prev) =>
            prev.map((i) =>
                i.menuId === menuId ? { ...i, note } : i
            )
        );
    }, []);

    const clearCart = useCallback(() => {
        setCart([]);
    }, []);

    const totalItems = cart.reduce((sum, i) => sum + i.quantity, 0);
    const totalPrice = cart.reduce((sum, i) => sum + i.price * i.quantity, 0);

    const getQuantity = useCallback(
        (menuId: number) => cart.find((i) => i.menuId === menuId)?.quantity ?? 0,
        [cart]
    );

    return (
        <CartContext.Provider
            value={{
                cart,
                addToCart,
                setItemQuantity,
                increment,
                decrement,
                remove,
                updateNote,
                clearCart,
                totalItems,
                totalPrice,
                getQuantity,
            }}
        >
            {children}
        </CartContext.Provider>
    );
}

export function useCart() {
    const context = useContext(CartContext);
    if (!context) {
        throw new Error('useCart must be used within a CartProvider');
    }
    return context;
}
