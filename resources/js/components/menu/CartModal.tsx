import { Minus, Plus, Trash2, X, StickyNote, ChevronRight } from 'lucide-react';
import { useEffect, useState } from 'react';
import { type CartItem } from './CartBar';

interface CartModalProps {
    items: CartItem[];
    isOpen: boolean;
    onClose: () => void;
    onIncrement: (menuId: number) => void;
    onDecrement: (menuId: number) => void;
    onRemove: (menuId: number) => void;
    onUpdateNote: (menuId: number, note: string) => void;
    onOrder: () => void;
}

export default function CartModal({
    items,
    isOpen,
    onClose,
    onIncrement,
    onDecrement,
    onRemove,
    onUpdateNote,
    onOrder,
}: CartModalProps) {
    const [editingNoteId, setEditingNoteId] = useState<number | null>(null);
    const [noteText, setNoteText] = useState('');

    // Lock body scroll
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

    if (!isOpen || items.length === 0) return null;

    const totalItems = items.reduce((sum, item) => sum + item.quantity, 0);
    const totalPrice = items.reduce((sum, item) => sum + item.price * item.quantity, 0);

    const handleOpenNote = (item: CartItem) => {
        setEditingNoteId(item.menuId);
        setNoteText(item.note || '');
    };

    const handleSaveNote = () => {
        if (editingNoteId !== null) {
            onUpdateNote(editingNoteId, noteText);
            setEditingNoteId(null);
            setNoteText('');
        }
    };

    return (
        <div className="fixed inset-0 z-100 flex items-end sm:items-center justify-center">
            {/* Backdrop */}
            <div className="absolute inset-0 bg-black/50 backdrop-blur-sm" onClick={onClose} />

            {/* Modal */}
            <div className="relative z-10 flex w-full max-w-lg max-h-[85vh] flex-col bg-white rounded-t-2xl sm:rounded-2xl animate-slide-up">
                {/* Header */}
                <div className="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <div>
                        <h2 className="text-lg font-bold text-gray-900">Keranjang</h2>
                        <p className="text-xs text-gray-400">{totalItems} item</p>
                    </div>
                    <button
                        onClick={onClose}
                        className="flex h-8 w-8 items-center justify-center rounded-full hover:bg-gray-100 transition-colors"
                    >
                        <X className="h-5 w-5 text-gray-500" />
                    </button>
                </div>

                {/* Cart Items */}
                <div className="flex-1 overflow-y-auto px-5 py-3">
                    <div className="space-y-4">
                        {items.map((item) => (
                            <div key={item.menuId} className="flex gap-3 rounded-xl border border-gray-100 bg-gray-50/50 p-3">
                                {/* Image */}
                                <img
                                    src={item.image}
                                    alt={item.name}
                                    className="h-16 w-16 shrink-0 rounded-lg object-cover"
                                    onError={(e) => {
                                        (e.target as HTMLImageElement).src = '/images/placeholder.jpg';
                                    }}
                                />

                                {/* Info */}
                                <div className="flex flex-1 flex-col justify-between min-w-0">
                                    <div>
                                        <h3 className="text-sm font-semibold text-gray-800 line-clamp-1">{item.name}</h3>
                                        <p className="text-sm font-bold text-orange-500">
                                            Rp {(item.price * item.quantity).toLocaleString('id-ID')}
                                        </p>
                                    </div>

                                    {/* Note indicator */}
                                    <button
                                        onClick={() => handleOpenNote(item)}
                                        className="mt-1 flex items-center gap-1 text-xs text-gray-400 hover:text-orange-500 transition-colors w-fit"
                                    >
                                        <StickyNote className="h-3 w-3" />
                                        {item.note ? (
                                            <span className="text-orange-500 line-clamp-1 max-w-30">{item.note}</span>
                                        ) : (
                                            <span>Tambah catatan</span>
                                        )}
                                        <ChevronRight className="h-3 w-3" />
                                    </button>
                                </div>

                                {/* Quantity & Remove */}
                                <div className="flex flex-col items-end justify-between shrink-0">
                                    <button
                                        onClick={() => onRemove(item.menuId)}
                                        className="flex h-6 w-6 items-center justify-center rounded-full text-gray-300 hover:text-red-500 hover:bg-red-50 transition-colors"
                                    >
                                        <Trash2 className="h-3.5 w-3.5" />
                                    </button>

                                    <div className="flex items-center gap-2">
                                        <button
                                            onClick={() => onDecrement(item.menuId)}
                                            className="flex h-6 w-6 items-center justify-center rounded-full border border-orange-300 text-orange-500 transition-all hover:bg-orange-50 active:scale-90"
                                        >
                                            <Minus className="h-3 w-3" />
                                        </button>
                                        <span className="w-5 text-center text-sm font-bold text-gray-800">{item.quantity}</span>
                                        <button
                                            onClick={() => onIncrement(item.menuId)}
                                            className="flex h-6 w-6 items-center justify-center rounded-full bg-orange-500 text-white transition-all hover:bg-orange-600 active:scale-90"
                                        >
                                            <Plus className="h-3 w-3" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>

                {/* Note editor overlay */}
                {editingNoteId !== null && (
                    <div className="absolute inset-0 z-20 flex items-center sm:items-center justify-center">
                        <div className="absolute inset-0 bg-black/30" onClick={handleSaveNote} />
                        <div className="relative z-10 w-full max-w-md mx-4 rounded-2xl bg-white p-5 shadow-xl">
                            <h3 className="text-sm font-semibold text-gray-800 mb-3">
                                Catatan untuk {items.find((i) => i.menuId === editingNoteId)?.name}
                            </h3>
                            <textarea
                                value={noteText}
                                onChange={(e) => setNoteText(e.target.value)}
                                placeholder="Contoh: tidak pedas, tanpa es..."
                                className="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm text-gray-700 placeholder:text-gray-300 focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-100 resize-none"
                                rows={3}
                                autoFocus
                            />
                            <div className="mt-3 flex gap-2 justify-end">
                                <button
                                    onClick={() => {
                                        setEditingNoteId(null);
                                        setNoteText('');
                                    }}
                                    className="rounded-full px-4 py-2 text-sm text-gray-500 hover:bg-gray-100 transition-colors"
                                >
                                    Batal
                                </button>
                                <button
                                    onClick={handleSaveNote}
                                    className="rounded-full bg-orange-500 px-5 py-2 text-sm font-semibold text-white hover:bg-orange-600 transition-colors"
                                >
                                    Simpan
                                </button>
                            </div>
                        </div>
                    </div>
                )}

                {/* Footer: Total + Order button */}
                <div className="border-t border-gray-100 px-5 py-4">
                    <div className="mb-3 flex items-center justify-between">
                        <span className="text-sm text-gray-500">Total</span>
                        <span className="text-lg font-bold text-gray-900">Rp {totalPrice.toLocaleString('id-ID')}</span>
                    </div>
                    <button
                        onClick={onOrder}
                        className="w-full rounded-full bg-orange-500 py-3.5 text-sm font-bold text-white shadow-md shadow-orange-500/25 transition-all hover:bg-orange-600 hover:shadow-lg active:scale-[0.98]"
                    >
                        Pesan Sekarang
                    </button>
                </div>
            </div>
        </div>
    );
}
