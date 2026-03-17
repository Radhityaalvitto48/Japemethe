import { Head } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import { OrderCard, OrderEmptyState, OrderHeader } from '@/components/order';
import type { Order } from '@/components/order';

export default function OrdersPage() {
    const [orders, setOrders] = useState<Order[]>([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchOrders = async () => {
            const stored = JSON.parse(sessionStorage.getItem('order_ids') || '[]');
            if (stored.length === 0) {
                setLoading(false);
                return;
            }

            try {
                const res = await fetch('/api/orders/by-ids', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ ids: stored }),
                });
                const data = await res.json();
                if (data.success) {
                    setOrders(data.data);
                }
            } catch {
                // silently fail
            } finally {
                setLoading(false);
            }
        };

        fetchOrders();

        const interval = setInterval(fetchOrders, 30000);
        return () => clearInterval(interval);
    }, []);

    return (
        <>
            <Head title="Pesanan - Japemethe" />

            <div className="min-h-screen bg-gray-100">
            <div className="relative mx-auto min-h-screen max-w-lg bg-gray-50/80 shadow-xl lg:max-w-3xl">
                <OrderHeader />

                {loading ? (
                    <div className="flex items-center justify-center py-20">
                        <div className="h-8 w-8 animate-spin rounded-full border-4 border-orange-500 border-t-transparent" />
                    </div>
                ) : orders.length === 0 ? (
                    <OrderEmptyState />
                ) : (
                    <div className="px-4 pt-4 pb-24 space-y-4">
                        {orders.map((order) => (
                            <OrderCard key={order.id} order={order} />
                        ))}
                    </div>
                )}
            </div>
            </div>
        </>
    );
}
