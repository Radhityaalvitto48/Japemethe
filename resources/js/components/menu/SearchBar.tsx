import { Search } from 'lucide-react';
import { FormEvent } from 'react';

interface SearchBarProps {
    value: string;
    onChange: (value: string) => void;
    onSubmit: (e: FormEvent) => void;
}

export default function SearchBar({ value, onChange, onSubmit }: SearchBarProps) {
    return (
        <section className="px-4 py-4">
            <form onSubmit={onSubmit}>
                <div className="relative">
                    <input
                        type="text"
                        placeholder="cari makanan & minuman"
                        value={value}
                        onChange={(e) => onChange(e.target.value)}
                        className="w-full rounded-lg border border-gray-200 bg-gray-50 py-3 pr-12 pl-4 text-sm text-gray-700 placeholder-gray-400 focus:border-orange-400 focus:bg-white focus:outline-none focus:ring-1 focus:ring-orange-400"
                    />
                    <button type="submit" className="absolute top-1/2 right-3 -translate-y-1/2">
                        <Search className="h-5 w-5 text-gray-400" />
                    </button>
                </div>
            </form>
        </section>
    );
}
