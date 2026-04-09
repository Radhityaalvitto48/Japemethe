import React, { useEffect, useMemo, useRef, useState } from 'react';
import { createRoot } from 'react-dom/client';
import OrderLineSection from '@/components/pos/OrderLineSection';
import MenuTabSection from '@/components/pos/MenuTabSection';
import OrderDetailPanel from '@/components/pos/OrderDetailPanel';

type PaymentStatus = 'pending' | 'completed' | 'failed';

type PosMenu = {
    id: number;
    name: string;
    price: number;
    stock: number;
    menu_category_name: string | null;
};

type PosOrderItem = {
    id: number;
    menu_name: string | null;
    quantity: number;
    unit_price: number;
    subtotal: number;
    note: string | null;
};

type PosOrder = {
    id: number;
    order_number: string;
    status_order: string;
    table_number: string | null;
    total_items: number;
    subtotal_amount: number;
    discount_amount: number;
    total_price: number;
    customer_email: string | null;
    customer_phone: string | null;
    items: PosOrderItem[];
    promo: {
        id: number;
        code: string;
        name: string;
        type: string;
        value: number;
    } | null;
    payment: {
        id: number;
        payment_method: string;
        status_payment: PaymentStatus;
        grass_amount: number;
        snap_token: string | null;
        payment_date: string | null;
    } | null;
};

type PosReservation = {
    id: number;
    customer_name: string;
    customer_phone: string;
    table_number: string | null;
    seating_type: string;
    reservation_date: string;
    reservation_time: string;
    status: string;
};

type ApiResponse<T> = {
    success: boolean;
    message?: string;
    data?: T;
    change_amount?: number;
};

type Flash = {
    type: 'success' | 'error';
    message: string;
} | null;

type Html5QrcodeInstance = {
    start: (
        cameraConfig: { facingMode: 'environment' },
        config: { fps: number; qrbox: number; aspectRatio: number },
        onSuccess: (decodedText: string) => void,
    ) => Promise<void>;
    stop: () => Promise<void>;
    clear: () => Promise<void>;
};

type Html5QrcodeCtor = new (elementId: string) => Html5QrcodeInstance;

function formatRupiah(value: number): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value ?? 0);
}

function parseIdrInput(value: string): number {
    const digits = value.replace(/\D/g, '');

    return digits === '' ? 0 : Number(digits);
}

function formatIdrInput(value: number): string {
    return new Intl.NumberFormat('id-ID', {
        maximumFractionDigits: 0,
    }).format(value);
}

function PosAdminApp({
    endpointBase,
    panelUrl,
    loginUrl,
}: {
    endpointBase: string;
    panelUrl: string;
    loginUrl: string;
}): React.JSX.Element {

    const [menus, setMenus] = useState<PosMenu[]>([]);
    const [orders, setOrders] = useState<PosOrder[]>([]);
    const [reservations, setReservations] = useState<PosReservation[]>([]);
    const [selectedOrder, setSelectedOrder] = useState<PosOrder | null>(null);

    const [leftTab, setLeftTab] = useState<'orderline' | 'menu' | 'reservation'>('orderline');
    const [paymentFilter, setPaymentFilter] = useState<'all' | 'unpaid' | 'paid'>('all');
    const [menuSearch, setMenuSearch] = useState('');
    const [menuCategoryFilter, setMenuCategoryFilter] = useState('all');
    const [promoCode, setPromoCode] = useState('');
    const [cashReceived, setCashReceived] = useState('');
    const [paymentMode, setPaymentMode] = useState<'cash' | 'qris'>('cash');

    const [loading, setLoading] = useState(false);
    const [cameraOn, setCameraOn] = useState(false);
    const [flash, setFlash] = useState<Flash>(null);
    const [scannedOrderId, setScannedOrderId] = useState<number | null>(null);
    const [isNavigating, setIsNavigating] = useState(false);

    const scannerRef = useRef<Html5QrcodeInstance | null>(null);
    const scannerBusyRef = useRef(false);
    const scannerElementId = 'kasir-pos-react-scanner';

    const csrfToken = useMemo(() => {
        return document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') ?? '';
    }, []);

    const showFlash = (type: 'success' | 'error', message: string): void => {
        setFlash({ type, message });
        window.setTimeout(() => setFlash(null), 2800);
    };

    const api = async <T,>(path: string, options: RequestInit = {}): Promise<ApiResponse<T>> => {
        const response = await fetch(`${endpointBase}${path}`, {
            ...options,
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                ...(options.headers ?? {}),
            },
        });

        if (response.status === 401 || response.status === 419) {
            window.location.assign(loginUrl);
            throw new Error('Sesi login berakhir. Silakan login ulang.');
        }

        if (response.redirected && response.url.includes('/login')) {
            window.location.assign(response.url);
            throw new Error('Sesi login berakhir. Silakan login ulang.');
        }

        const contentType = response.headers.get('content-type') ?? '';
        if (!contentType.includes('application/json')) {
            throw new Error('Respons server tidak valid. Silakan login ulang.');
        }

        const data = (await response.json()) as ApiResponse<T>;

        if (!response.ok || data.success === false) {
            throw new Error(data.message ?? 'Terjadi kesalahan pada server.');
        }

        return data;
    };

    const hydrateInitial = async (): Promise<void> => {
        setLoading(true);
        try {
            const [menusRes, ordersRes, reservationsRes] = await Promise.all([
                api<PosMenu[]>('/menus'),
                api<PosOrder[]>('/orders/active'),
                api<PosReservation[]>('/reservations/pending'),
            ]);

            setMenus(menusRes.data ?? []);
            setOrders(ordersRes.data ?? []);
            setReservations(reservationsRes.data ?? []);
        } catch (error) {
            showFlash('error', error instanceof Error ? error.message : 'Gagal memuat data PoS.');
        } finally {
            setLoading(false);
        }
    };

    const refreshOrdersAndReservations = async (): Promise<void> => {
        try {
            const [menusRes, ordersRes, reservationsRes] = await Promise.all([
                api<PosMenu[]>('/menus'),
                api<PosOrder[]>('/orders/active'),
                api<PosReservation[]>('/reservations/pending'),
            ]);

            setMenus(menusRes.data ?? []);
            const nextOrders = ordersRes.data ?? [];
            setOrders(nextOrders);
            setReservations(reservationsRes.data ?? []);

            if (selectedOrder) {
                const nextSelected = nextOrders.find((order) => order.id === selectedOrder.id);
                if (nextSelected) {
                    setSelectedOrder(nextSelected);
                }
            }
        } catch {
            // silent refresh fail
        }
    };

    useEffect(() => {
        void hydrateInitial();
    }, []);

    useEffect(() => {
        const previousBodyOverflow = document.body.style.overflow;
        const previousHtmlOverflow = document.documentElement.style.overflow;

        document.body.style.overflow = 'hidden';
        document.documentElement.style.overflow = 'hidden';

        return () => {
            document.body.style.overflow = previousBodyOverflow;
            document.documentElement.style.overflow = previousHtmlOverflow;
        };
    }, []);

    useEffect(() => {
        const interval = window.setInterval(() => {
            void refreshOrdersAndReservations();
        }, 12000);

        return () => window.clearInterval(interval);
    }, [selectedOrder]);

    useEffect(() => {
        if (!cameraOn) {
            return;
        }

        let cancelled = false;

        const bootScanner = async (): Promise<void> => {
            await new Promise<void>((resolve) => window.requestAnimationFrame(() => resolve()));

            if (cancelled || scannerRef.current) {
                return;
            }

            await startCamera();
        };

        void bootScanner();

        return () => {
            cancelled = true;
        };
    }, [cameraOn]);

    const selectOrderById = async (orderId: number): Promise<void> => {
        try {
            const response = await api<PosOrder>(`/orders/${orderId}`);
            setSelectedOrder(response.data ?? null);

            const method = response.data?.payment?.payment_method ?? '';
            setPaymentMode(method.includes('cash') ? 'cash' : 'qris');
            setLeftTab('orderline');
        } catch (error) {
            showFlash('error', error instanceof Error ? error.message : 'Order tidak ditemukan.');
        }
    };

    const scanOrder = async (payload: string, silentSuccess = false): Promise<boolean> => {
        const currentPayload = payload.trim();

        if (!currentPayload) {
            showFlash('error', 'Payload scan tidak boleh kosong.');
            return false;
        }

        try {
            const response = await api<PosOrder>('/orders/scan', {
                method: 'POST',
                body: JSON.stringify({ payload: currentPayload }),
            });

            if (response.data) {
                setSelectedOrder(response.data);
                setScannedOrderId(response.data.id);
                setPaymentFilter('all');
                setLeftTab('orderline');
                window.setTimeout(() => setScannedOrderId(null), 2500);
            }

            if (!silentSuccess) {
                showFlash('success', 'Order berhasil ditemukan dari scan.');
            }

            void refreshOrdersAndReservations();
            return true;
        } catch (error) {
            showFlash('error', error instanceof Error ? error.message : 'Scan gagal.');
            return false;
        }
    };

    const addMenuToOrder = async (menuId: number, quantity = 1, note = ''): Promise<void> => {
        if (!selectedOrder) {
            showFlash('error', 'Scan/pilih order dulu sebelum tambah menu.');
            return;
        }

        const normalizedNote = note.trim();

        try {
            const response = await api<PosOrder>(`/orders/${selectedOrder.id}/items`, {
                method: 'POST',
                body: JSON.stringify({
                    menu_id: menuId,
                    quantity,
                    note: normalizedNote === '' ? null : normalizedNote,
                }),
            });

            if (response.data) {
                setSelectedOrder(response.data);
            }

            showFlash('success', 'Menu berhasil ditambahkan ke order.');
            void refreshOrdersAndReservations();
        } catch (error) {
            showFlash('error', error instanceof Error ? error.message : 'Gagal menambahkan menu.');
        }
    };

    const updateOrderItemQuantity = async (detailId: number, quantity: number): Promise<void> => {
        if (!selectedOrder) {
            return;
        }

        try {
            const response = await api<PosOrder>(`/orders/${selectedOrder.id}/items/${detailId}`, {
                method: 'PATCH',
                body: JSON.stringify({ quantity: Math.max(0, quantity) }),
            });

            if (response.data) {
                setSelectedOrder(response.data);
            }

            showFlash('success', 'Item order diperbarui.');
            void refreshOrdersAndReservations();
        } catch (error) {
            showFlash('error', error instanceof Error ? error.message : 'Gagal memperbarui item order.');
        }
    };

    const applyPromo = async (): Promise<void> => {
        if (!selectedOrder) {
            showFlash('error', 'Pilih order terlebih dahulu.');
            return;
        }

        if (!promoCode.trim()) {
            showFlash('error', 'Kode promo wajib diisi.');
            return;
        }

        try {
            const response = await api<PosOrder>(`/orders/${selectedOrder.id}/promo`, {
                method: 'POST',
                body: JSON.stringify({ code: promoCode }),
            });

            if (response.data) {
                setSelectedOrder(response.data);
            }

            showFlash('success', 'Promo berhasil diterapkan.');
            void refreshOrdersAndReservations();
        } catch (error) {
            showFlash('error', error instanceof Error ? error.message : 'Promo gagal diterapkan.');
        }
    };

    const removePromo = async (): Promise<void> => {
        if (!selectedOrder) {
            return;
        }

        try {
            const response = await api<PosOrder>(`/orders/${selectedOrder.id}/promo`, {
                method: 'DELETE',
            });

            if (response.data) {
                setSelectedOrder(response.data);
            }

            showFlash('success', 'Promo dihapus dari order.');
            void refreshOrdersAndReservations();
        } catch (error) {
            showFlash('error', error instanceof Error ? error.message : 'Gagal menghapus promo.');
        }
    };

    const payCash = async (): Promise<void> => {
        if (!selectedOrder) {
            return;
        }

        const cashValue = parseIdrInput(cashReceived);

        try {
            const response = await api<PosOrder>(`/orders/${selectedOrder.id}/payments/cash`, {
                method: 'POST',
                body: JSON.stringify({
                    cash_received: cashReceived.trim() === '' ? null : cashValue,
                }),
            });

            if (response.data) {
                setSelectedOrder(response.data);
            }

            showFlash(
                'success',
                `Pembayaran tunai berhasil. Kembalian: ${formatRupiah(response.change_amount ?? 0)}`,
            );

            setCashReceived('');
            void refreshOrdersAndReservations();
        } catch (error) {
            showFlash('error', error instanceof Error ? error.message : 'Pembayaran tunai gagal.');
        }
    };

    const markDigitalPaid = async (): Promise<void> => {
        if (!selectedOrder) {
            return;
        }

        try {
            const response = await api<PosOrder>(`/orders/${selectedOrder.id}/payments/mark-completed`, {
                method: 'POST',
            });

            if (response.data) {
                setSelectedOrder(response.data);
            }

            showFlash('success', 'Pembayaran QRIS berhasil ditandai lunas.');
            void refreshOrdersAndReservations();
        } catch (error) {
            showFlash('error', error instanceof Error ? error.message : 'Gagal update status pembayaran.');
        }
    };

    const confirmReservation = async (reservationId: number): Promise<void> => {
        try {
            await api<PosReservation>(`/reservations/${reservationId}/confirm`, {
                method: 'POST',
            });

            showFlash('success', 'Reservasi dikonfirmasi oleh kasir.');
            void refreshOrdersAndReservations();
        } catch (error) {
            showFlash('error', error instanceof Error ? error.message : 'Gagal konfirmasi reservasi.');
        }
    };

    const toggleFullScreen = async (): Promise<void> => {
        if (!document.fullscreenElement) {
            await document.documentElement.requestFullscreen();
            return;
        }

        await document.exitFullscreen();
    };

    const navigateToPanel = async (): Promise<void> => {
        if (isNavigating) {
            return;
        }

        setIsNavigating(true);

        if (cameraOn) {
            await stopCamera();
        }

        window.location.assign(panelUrl);
    };

    const stopCamera = async (): Promise<void> => {
        if (scannerRef.current) {
            try {
                await scannerRef.current.stop();
                await scannerRef.current.clear();
            } catch {
                // ignore scanner stop error
            }
        }

        scannerRef.current = null;
        scannerBusyRef.current = false;
        setCameraOn(false);
    };

    const startCamera = async (): Promise<void> => {
        const scannerClass = (window as Window & { Html5Qrcode?: Html5QrcodeCtor }).Html5Qrcode;

        if (!scannerClass) {
            showFlash('error', 'Library scanner belum termuat.');
            return;
        }

        if (!navigator.mediaDevices?.getUserMedia) {
            showFlash('error', 'Browser tidak mendukung akses kamera.');
            return;
        }

        try {
            if ('permissions' in navigator && navigator.permissions?.query) {
                const status = await navigator.permissions.query({ name: 'camera' as PermissionName });

                if (status.state === 'denied') {
                    showFlash('error', 'Izin kamera ditolak. Aktifkan camera permission di browser lalu coba lagi.');
                    return;
                }

                if (status.state === 'prompt') {
                    showFlash('success', 'Browser akan meminta izin kamera. Klik Allow untuk scan QR.');
                }
            }

            const scanner = new scannerClass(scannerElementId);
            scannerRef.current = scanner;

            await scanner.start(
                { facingMode: 'environment' },
                {
                    fps: 30,
                    qrbox: 300,
                    aspectRatio: 1,
                },
                (decodedText) => {
                    if (scannerBusyRef.current) {
                        return;
                    }

                    scannerBusyRef.current = true;

                    void (async () => {
                        await stopCamera();

                        const success = await scanOrder(decodedText, true);

                        if (success) {
                            showFlash('success', 'QR terdeteksi. Detail order berhasil dimuat.');
                        } else {
                            scannerBusyRef.current = false;
                            setCameraOn(true);
                        }
                    })();
                },
            );

            showFlash('success', 'Kamera aktif. Arahkan ke QR pesanan.');
        } catch {
            showFlash('error', 'Tidak bisa mengakses kamera. Pastikan izin kamera aktif.');
            await stopCamera();
        }
    };

    const toggleCamera = async (): Promise<void> => {
        if (cameraOn) {
            await stopCamera();
            return;
        }

        setCameraOn(true);
    };

    const selectedOrderItems = selectedOrder?.items ?? [];
    const menuCategories = useMemo(() => {
        const categories = menus
            .map((menu) => menu.menu_category_name)
            .filter((value): value is string => Boolean(value));

        return ['all', ...Array.from(new Set(categories))];
    }, [menus]);

    const filteredMenus = menus.filter((menu) => {
        const matchesSearch = menu.name.toLowerCase().includes(menuSearch.trim().toLowerCase());
        const matchesCategory = menuCategoryFilter === 'all' || menu.menu_category_name === menuCategoryFilter;

        return matchesSearch && matchesCategory;
    });

    const filteredOrders = orders.filter((order) => {
        const isPaid = order.payment?.status_payment === 'completed';

        if (paymentFilter === 'paid') {
            return isPaid;
        }

        if (paymentFilter === 'unpaid') {
            return !isPaid;
        }

        return true;
    });

    const leftPanelOrders = useMemo(() => {
        if (!scannedOrderId) {
            return filteredOrders;
        }

        const scanned = filteredOrders.find((order) => order.id === scannedOrderId);
        if (!scanned) {
            return filteredOrders;
        }

        return [scanned, ...filteredOrders.filter((order) => order.id !== scannedOrderId)];
    }, [filteredOrders, scannedOrderId]);

    useEffect(() => {
        if (!scannedOrderId || leftTab !== 'orderline') {
            return;
        }

        const timer = window.setTimeout(() => {
            const card = document.getElementById(`order-card-${scannedOrderId}`);
            card?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 80);

        return () => window.clearTimeout(timer);
    }, [scannedOrderId, leftTab, leftPanelOrders]);

    const unpaidOrders = orders.filter((order) => order.payment?.status_payment !== 'completed').length;
    const paidOrders = orders.filter((order) => order.payment?.status_payment === 'completed').length;
    const totalActiveOrders = orders.length;
    const pendingReservations = reservations.length;

    const selectedOrderSubtotal = selectedOrder?.subtotal_amount ?? 0;
    const selectedOrderTax = Math.round(selectedOrderSubtotal * 0.11);
    const selectedOrderFinal = selectedOrder?.total_price ?? 0;
    const cashReceivedValue = parseIdrInput(cashReceived);
    const changeAmount = Math.max(0, cashReceivedValue - selectedOrderFinal);

    const handlePayAction = (): void => {
        if (!selectedOrder) {
            showFlash('error', 'Pilih order terlebih dahulu.');
            return;
        }

        if (paymentMode === 'cash') {
            if (cashReceived.trim() !== '' && cashReceivedValue < selectedOrderFinal) {
                showFlash('error', 'Nominal cash kurang dari total tagihan.');
                return;
            }

            void payCash();
            return;
        }

        void markDigitalPaid();
    };

    const selectedPaymentStatus = selectedOrder?.payment?.status_payment === 'completed' ? 'LUNAS' : 'BELUM BAYAR';

    return (
        <div className="h-screen overflow-hidden rounded-3xl border border-gray-200 bg-linear-to-b from-gray-50 to-white p-3 md:p-5">
            <div className="relative flex h-full flex-col space-y-4 rounded-2xl bg-white p-4 shadow-sm md:p-5 lg:pr-[34%]">
                <div className="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-gray-200 bg-white px-4 py-3">
                    <div>
                        <h2 className="text-xl font-bold text-gray-900">Japemethe Kasir</h2>
                        <p className="text-xs text-gray-500">Dashboard • Kasir</p>
                    </div>
                    <div className="flex items-center gap-2">
                        <a
                            href={panelUrl}
                            onClick={(event) => {
                                event.preventDefault();
                                void navigateToPanel();
                            }}
                            className="rounded-xl border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                        >
                            {isNavigating ? 'Menuju Panel...' : 'Ke Panel'}
                        </a>
                        <button
                            type="button"
                            onClick={() => {
                                void hydrateInitial();
                            }}
                            className="rounded-xl border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                        >
                            Refresh
                        </button>
                        <button
                            type="button"
                            onClick={() => {
                                void toggleFullScreen();
                            }}
                            className="rounded-xl border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                        >
                            Fullscreen
                        </button>
                        <button
                            type="button"
                            onClick={() => {
                                void toggleCamera();
                            }}
                            className="rounded-xl bg-amber-500 px-3 py-2 text-xs font-semibold text-white hover:bg-amber-600"
                        >
                            {cameraOn ? 'Stop Camera' : 'Scan Camera'}
                        </button>
                    </div>
                </div>

                {flash && (
                    <div
                        className={`rounded-lg border px-4 py-2 text-sm ${
                            flash.type === 'success'
                                ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                                : 'border-rose-200 bg-rose-50 text-rose-700'
                        }`}
                    >
                        {flash.message}
                    </div>
                )}

                {cameraOn && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/45 p-4">
                        <div className="w-full max-w-md rounded-2xl bg-white p-4 shadow-xl">
                            <div className="mb-3 flex items-center justify-between">
                                <p className="text-sm font-semibold text-gray-900">Scan QR Pesanan</p>
                                <button
                                    type="button"
                                    onClick={() => {
                                        void stopCamera();
                                    }}
                                    className="rounded-lg border border-gray-300 px-2 py-1 text-xs font-semibold text-gray-700"
                                >
                                    Tutup
                                </button>
                            </div>
                            <div
                                id={scannerElementId}
                                className="w-full overflow-hidden rounded-xl border border-amber-300"
                            />
                            <p className="mt-2 text-xs text-gray-500">Arahkan kamera ke QR order customer.</p>
                        </div>
                    </div>
                )}

                <div className="min-h-0 flex-1">
                    <section className="h-full space-y-3 rounded-2xl border border-gray-200 bg-white p-4 lg:max-h-[calc(100vh-220px)] lg:overflow-y-auto lg:pr-2">
                        <div className="flex gap-2 text-sm font-semibold">
                            <button
                                type="button"
                                onClick={() => setLeftTab('orderline')}
                                className={`rounded-xl px-3 py-2 ${leftTab === 'orderline' ? 'bg-amber-500 text-white' : 'bg-gray-100 text-gray-700'}`}
                            >
                                Orderline
                            </button>
                            <button
                                type="button"
                                onClick={() => setLeftTab('menu')}
                                className={`rounded-xl px-3 py-2 ${leftTab === 'menu' ? 'bg-amber-500 text-white' : 'bg-gray-100 text-gray-700'}`}
                            >
                                Menu
                            </button>
                            <button
                                type="button"
                                onClick={() => setLeftTab('reservation')}
                                className={`rounded-xl px-3 py-2 ${leftTab === 'reservation' ? 'bg-amber-500 text-white' : 'bg-gray-100 text-gray-700'}`}
                            >
                                Reservasi ({pendingReservations})
                            </button>
                        </div>

                        {leftTab === 'orderline' ? (
                            <OrderLineSection
                                paymentFilter={paymentFilter}
                                onChangeFilter={setPaymentFilter}
                                totalActiveOrders={totalActiveOrders}
                                unpaidOrders={unpaidOrders}
                                paidOrders={paidOrders}
                                orders={leftPanelOrders}
                                selectedOrderId={selectedOrder?.id ?? null}
                                scannedOrderId={scannedOrderId}
                                onSelectOrder={(orderId) => {
                                    void selectOrderById(orderId);
                                }}
                                formatRupiah={formatRupiah}
                            />
                        ) : leftTab === 'menu' ? (
                            <MenuTabSection
                                menuSearch={menuSearch}
                                onMenuSearchChange={setMenuSearch}
                                menuCategories={menuCategories}
                                menuCategoryFilter={menuCategoryFilter}
                                onCategoryChange={setMenuCategoryFilter}
                                filteredMenus={filteredMenus}
                                loading={loading}
                                selectedOrderExists={Boolean(selectedOrder)}
                                formatRupiah={formatRupiah}
                                onAddMenuToOrder={(menuId) => {
                                    void addMenuToOrder(menuId, 1);
                                }}
                            />
                        ) : (
                            <div className="space-y-3">
                                <div className="rounded-lg border border-amber-100 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                                    Alur reservasi: user reservasi {'->'} kasir validasi {'->'} kasir konfirmasi.
                                </div>

                                {reservations.length === 0 ? (
                                    <p className="rounded-lg border border-dashed border-gray-300 p-3 text-xs text-gray-500">
                                        Tidak ada reservasi pending saat ini.
                                    </p>
                                ) : (
                                    <div className="grid gap-2 md:grid-cols-2 xl:grid-cols-3">
                                        {reservations.map((reservation) => (
                                            <div
                                                key={reservation.id}
                                                className="rounded-xl border border-gray-200 bg-white p-3"
                                            >
                                                <p className="text-sm font-semibold text-gray-900">
                                                    {reservation.customer_name}
                                                </p>
                                                <p className="mt-1 text-xs text-gray-600">
                                                    {reservation.customer_phone}
                                                </p>
                                                <p className="mt-1 text-xs text-gray-600">
                                                    Meja: {reservation.table_number ?? '-'}
                                                </p>
                                                <p className="mt-1 text-xs text-gray-600">
                                                    Tanggal: {reservation.reservation_date} {reservation.reservation_time}
                                                </p>
                                                <p className="mt-1 text-xs text-gray-600">
                                                    Tipe: {reservation.seating_type}
                                                </p>

                                                <button
                                                    type="button"
                                                    onClick={() => {
                                                        void confirmReservation(reservation.id);
                                                    }}
                                                    className="mt-3 w-full rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700"
                                                >
                                                    Konfirmasi Reservasi
                                                </button>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>
                        )}
                    </section>

                    {leftTab !== 'reservation' && (
                        <OrderDetailPanel
                            selectedOrder={selectedOrder}
                            selectedPaymentStatus={selectedPaymentStatus}
                            selectedOrderSubtotal={selectedOrderSubtotal}
                            selectedOrderTax={selectedOrderTax}
                            selectedOrderFinal={selectedOrderFinal}
                            selectedOrderItems={selectedOrderItems}
                            promoCode={promoCode}
                            onPromoCodeChange={setPromoCode}
                            onApplyPromo={() => {
                                void applyPromo();
                            }}
                            onRemovePromo={() => {
                                void removePromo();
                            }}
                            paymentMode={paymentMode}
                            onPaymentModeChange={setPaymentMode}
                            cashReceived={cashReceived}
                            onCashReceivedChange={(value) => {
                                const numeric = parseIdrInput(value);
                                setCashReceived(numeric === 0 ? '' : formatIdrInput(numeric));
                            }}
                            cashReceivedValue={cashReceivedValue}
                            changeAmount={changeAmount}
                            onPayAction={handlePayAction}
                            onUpdateOrderItemQuantity={(detailId, quantity) => {
                                void updateOrderItemQuantity(detailId, quantity);
                            }}
                            formatRupiah={formatRupiah}
                        />
                    )}
                </div>
            </div>
        </div>
    );
}

const mountElement = document.getElementById('kasir-pos-react');

if (mountElement) {
    const endpointBase = mountElement.getAttribute('data-endpoint-base') ?? '/admin/pos-api';
    const panelUrl = mountElement.getAttribute('data-panel-url') ?? '/admin';
    const loginUrl = mountElement.getAttribute('data-login-url') ?? '/admin/login';

    createRoot(mountElement).render(
        <React.StrictMode>
            <PosAdminApp endpointBase={endpointBase} panelUrl={panelUrl} loginUrl={loginUrl} />
        </React.StrictMode>,
    );
}
