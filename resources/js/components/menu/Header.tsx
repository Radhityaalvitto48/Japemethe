import { ShoppingCart, MapPin, Search } from 'lucide-react';
import { useTable } from '@/contexts';
import { type FormEvent } from 'react';

interface HeaderProps {
    onBackClick?: () => void;
    cartCount?: number;
    searchValue?: string;
    onSearchChange?: (value: string) => void;
    onSearchSubmit?: (e: FormEvent) => void;
}

export default function Header({ onBackClick, cartCount = 0, searchValue, onSearchChange, onSearchSubmit }: HeaderProps) {
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
            <div className="flex items-center justify-between px-4 py-3 lg:mx-auto lg:max-w-7xl lg:px-8">
                {/* <h1 className="shrink-0 text-lg font-bold tracking-wide text-gray-800 lg:text-xl">JAPEMETHE</h1> */}
                <img
                    src="/storage/logo.webp"
                    alt="JAPEMETHE"
                    className="h-10 w-20 object-contain"
                />

                {/* Desktop Search Bar */}
                {onSearchSubmit && (
                    <form onSubmit={onSearchSubmit} className="mx-8 hidden max-w-2xl flex-1 lg:block">
                        <div className="relative">
                            <input
                                type="text"
                                placeholder="Cari makanan & minuman..."
                                value={searchValue || ''}
                                onChange={(e) => onSearchChange?.(e.target.value)}
                                className="w-full rounded-lg border border-gray-200 bg-gray-50 py-2.5 pr-12 pl-4 text-sm text-gray-700 placeholder-gray-400 focus:border-orange-400 focus:bg-white focus:outline-none focus:ring-1 focus:ring-orange-400"
                            />
                            <button type="submit" className="absolute top-1/2 right-3 -translate-y-1/2">
                                <Search className="h-5 w-5 text-gray-400" />
                            </button>
                        </div>
                    </form>
                )}

                <div className="flex items-center gap-3">
                    {/* Table info */}
                    {currentTable && (
                        <button
                            onClick={handleTableClear}
                            className="flex items-center gap-2 rounded-full bg-orange-100 px-3 py-1 transition-colors hover:bg-orange-200"
                            title="Klik untuk keluar dari meja"
                        >
                            <MapPin className="h-4 w-4 text-orange-600" />
                            <span className="text-sm font-medium text-orange-700">
                                {getTableDisplayName(currentTable.table_number)}
                            </span>
                        </button>
                    )}

                    {/* Cart icon - on mobile: hidden when table is set; on desktop: always visible */}
                    <button className={`relative rounded-full p-2 hover:bg-gray-100 ${currentTable ? 'hidden lg:flex' : 'flex'} items-center justify-center`}>
                        <ShoppingCart className="h-6 w-6 text-gray-700" />
                        {cartCount > 0 && (
                            <span className="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-orange-500 text-xs font-bold text-white">
                                {cartCount}
                            </span>
                        )}
                    </button>
                </div>
            </div>
        </header>
    );
}
