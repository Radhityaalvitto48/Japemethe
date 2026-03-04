import { cn } from '@/lib/utils';

interface MenuCategory {
    id: number;
    name: string;
    image: string;
    display: boolean;
    status_category: string;
}

interface CategoryListProps {
    categories: MenuCategory[];
    activeCategory: number | null;
    onCategoryClick: (categoryId: number | null) => void;
}

// Get image URL
const getImageUrl = (path: string): string => {
    if (!path) return '/images/placeholder.jpg';
    if (path.startsWith('http')) return path;
    return `/storage/${path}`;
};

export default function CategoryList({ categories, activeCategory, onCategoryClick }: CategoryListProps) {
    return (
        <section className="px-4 pb-4">
            <div className="flex gap-4 overflow-x-auto pb-2 scrollbar-hide">
                {/* All Category */}
                <button onClick={() => onCategoryClick(null)} className="flex flex-shrink-0 flex-col items-center gap-2">
                    <div
                        className={cn(
                            'flex h-14 w-14 items-center justify-center rounded-full border-2 bg-white transition-all sm:h-16 sm:w-16',
                            activeCategory === null ? 'border-orange-500 shadow-md' : 'border-gray-100',
                        )}
                    >
                        <span className="text-2xl">🍽️</span>
                    </div>
                    <span
                        className={cn(
                            'text-xs font-medium sm:text-sm',
                            activeCategory === null ? 'text-orange-600' : 'text-gray-600',
                        )}
                    >
                        Semua
                    </span>
                </button>

                {/* Category Items */}
                {categories.map((category) => (
                    <button
                        key={category.id}
                        onClick={() => onCategoryClick(category.id)}
                        className="flex flex-shrink-0 flex-col items-center gap-2"
                    >
                        <div
                            className={cn(
                                'h-14 w-14 overflow-hidden rounded-full border-2 bg-white transition-all sm:h-16 sm:w-16',
                                activeCategory === category.id ? 'border-orange-500 shadow-md' : 'border-gray-100',
                            )}
                        >
                            <img
                                src={getImageUrl(category.image)}
                                alt={category.name}
                                className="h-full w-full object-cover"
                            />
                        </div>
                        <span
                            className={cn(
                                'max-w-[70px] truncate text-xs font-medium sm:text-sm',
                                activeCategory === category.id ? 'text-orange-600' : 'text-gray-600',
                            )}
                        >
                            {category.name}
                        </span>
                    </button>
                ))}
            </div>
        </section>
    );
}
