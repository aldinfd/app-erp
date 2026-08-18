import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import type { StoredCartItem } from '@/types/sales';

/**
 * Keranjang storefront — client-side localStorage (keputusan Phase 4,
 * tanpa dependency baru). Hanya untuk display; harga & stok selalu
 * divalidasi ulang server saat checkout.
 */

const STORAGE_KEY = 'storefront-cart';

type CartContextValue = {
    items: StoredCartItem[];
    count: number;
    subtotal: number;
    addItem: (item: StoredCartItem, qty: number) => void;
    setQty: (productId: number, qty: number) => void;
    removeItem: (productId: number) => void;
    clear: () => void;
};

const CartContext = createContext<CartContextValue | null>(null);

function readStorage(): StoredCartItem[] {
    try {
        const raw = localStorage.getItem(STORAGE_KEY);

        if (!raw) {
            return [];
        }

        const parsed: unknown = JSON.parse(raw);

        return Array.isArray(parsed) ? (parsed as StoredCartItem[]) : [];
    } catch {
        // JSON malformed / private mode — anggap keranjang kosong.
        return [];
    }
}

export function CartProvider({ children }: { children: React.ReactNode }) {
    const [items, setItems] = useState<StoredCartItem[]>([]);
    const [mounted, setMounted] = useState(false);

    useEffect(() => {
        setItems(readStorage());
        setMounted(true);
    }, []);

    useEffect(() => {
        if (!mounted) {
            return;
        }

        localStorage.setItem(STORAGE_KEY, JSON.stringify(items));
    }, [items, mounted]);

    const addItem = useCallback((item: StoredCartItem, qty: number) => {
        setItems((current) => {
            const existing = current.find((line) => line.product_id === item.product_id);

            if (existing) {
                return current.map((line) =>
                    line.product_id === item.product_id ? { ...line, qty: line.qty + qty } : line,
                );
            }

            return [...current, { ...item, qty }];
        });
    }, []);

    const setQty = useCallback((productId: number, qty: number) => {
        setItems((current) =>
            current.map((line) => (line.product_id === productId ? { ...line, qty } : line)),
        );
    }, []);

    const removeItem = useCallback((productId: number) => {
        setItems((current) => current.filter((line) => line.product_id !== productId));
    }, []);

    const clear = useCallback(() => {
        setItems([]);
    }, []);

    const value = useMemo<CartContextValue>(() => {
        const count = items.reduce((total, line) => total + line.qty, 0);
        const subtotal = items.reduce((total, line) => total + line.price * line.qty, 0);

        return { items, count, subtotal, addItem, setQty, removeItem, clear };
    }, [items, addItem, setQty, removeItem, clear]);

    return <CartContext.Provider value={value}>{children}</CartContext.Provider>;
}

export function useCart(): CartContextValue {
    const context = useContext(CartContext);

    if (!context) {
        throw new Error('useCart harus dipakai di dalam CartProvider.');
    }

    return context;
}
