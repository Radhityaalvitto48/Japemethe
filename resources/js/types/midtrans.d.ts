interface MidtransSnapResult {
    order_id: string;
    transaction_status: string;
    payment_type: string;
    gross_amount: string;
    status_code: string;
    status_message: string;
}

interface MidtransSnap {
    pay(
        token: string,
        callbacks: {
            onSuccess?: (result: MidtransSnapResult) => void;
            onPending?: (result: MidtransSnapResult) => void;
            onError?: (result: MidtransSnapResult) => void;
            onClose?: () => void;
        },
    ): void;
}

interface Window {
    snap: MidtransSnap;
}
