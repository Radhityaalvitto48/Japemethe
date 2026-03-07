import { ChevronLeft, ShoppingCart, MapPin } from 'lucide-react';
import { useTable } from '@/contexts';

interface HeaderProps {
    onBackClick?: () => void;
    cartCount?: number;
}

export default function Header({ onBackClick, cartCount = 0 }: HeaderProps) {
    const { currentTable, clearTable } = useTable();

    const getTableDisplayName = (tableNumber: string) => {
        if (tableNumber.startsWith('L-')) {
            return `Meja Lesehan ${tableNumber}`;
        } else if (tableNumber.startsWith('K-')) {
            return `Meja Kursi ${tableNumber}`;
        }
        return `Meja ${tableNumber}`;
    };

    const handleTableClear = () => {
        if (confirm('Apakah Anda yakin ingin keluar dari meja ini?')) {
            clearTable();
        }
    };

    return (
        <header className="sticky top-0 z-50 bg-white shadow-sm">
            <div className="flex items-center justify-between px-4 py-3">
                <h1 className="text-lg font-bold tracking-wide text-gray-800">JAPEMETHE</h1>

                {/* Show table name if available, otherwise show cart */}
                {currentTable ? (
                    <button
                        onClick={handleTableClear}
                        className="flex items-center gap-2 bg-orange-100 px-3 py-1 rounded-full hover:bg-orange-200 transition-colors"
                        title="Klik untuk keluar dari meja"
                    >
                        <MapPin className="h-4 w-4 text-orange-600" />
                        <span className="text-sm font-medium text-orange-700">
                            {getTableDisplayName(currentTable.table_number)}
                        </span>
                    </button>
                ) : (
                    <button className="relative rounded-full p-2 hover:bg-gray-100">
                        <ShoppingCart className="h-6 w-6 text-gray-700" />
                        <span className="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-orange-500 text-xs font-bold text-white">
                            {cartCount}
                        </span>
                    </button>
                )}
            </div>
        </header>
    );
}
