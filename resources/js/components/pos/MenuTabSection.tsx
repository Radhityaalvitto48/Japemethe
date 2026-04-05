type PosMenu = {
    id: number;
    name: string;
    price: number;
    stock: number;
    menu_category_name: string | null;
};

type MenuTabSectionProps = {
    menuSearch: string;
    onMenuSearchChange: (value: string) => void;
    menuCategories: string[];
    menuCategoryFilter: string;
    onCategoryChange: (value: string) => void;
    filteredMenus: PosMenu[];
    loading: boolean;
    selectedOrderExists: boolean;
    formatRupiah: (value: number) => string;
    onAddMenuToOrder: (menuId: number) => void;
};

export default function MenuTabSection({
    menuSearch,
    onMenuSearchChange,
    menuCategories,
    menuCategoryFilter,
    onCategoryChange,
    filteredMenus,
    loading,
    selectedOrderExists,
    formatRupiah,
    onAddMenuToOrder,
}: MenuTabSectionProps): React.JSX.Element {
    return (
        <div className="space-y-3">
            <div className="relative w-full max-w-xl">
                <input
                    value={menuSearch}
                    onChange={(event) => onMenuSearchChange(event.target.value)}
                    placeholder="Cari menu untuk tambah ke order"
                    className="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 outline-none focus:border-amber-400"
                />
            </div>
            <div className="flex flex-wrap gap-2 text-xs font-semibold">
                {menuCategories.map((category) => (
                    <button
                        key={category}
                        type="button"
                        onClick={() => onCategoryChange(category)}
                        className={`rounded-full border px-3 py-1 ${menuCategoryFilter === category ? 'border-sky-300 bg-sky-100 text-sky-700' : 'border-gray-200 bg-gray-50 text-gray-600'}`}
                    >
                        {category === 'all' ? 'Semua Kategori' : category}
                    </button>
                ))}
            </div>
            <div className="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                {filteredMenus.map((menu) => (
                    <div key={menu.id} className="rounded-xl border border-gray-200 p-3">
                        <div className="flex items-start justify-between gap-2">
                            <p className="text-sm font-semibold text-gray-900">{menu.name}</p>
                            <span className="rounded-full bg-emerald-50 px-2 py-1 text-[10px] font-semibold text-emerald-700">
                                Stock {menu.stock}
                            </span>
                        </div>
                        <p className="mt-1 text-[11px] text-gray-500">{menu.menu_category_name ?? 'Tanpa kategori'}</p>
                        <p className="mt-2 text-sm font-semibold text-gray-800">{formatRupiah(menu.price)}</p>
                        <button
                            type="button"
                            onClick={() => onAddMenuToOrder(menu.id)}
                            disabled={!selectedOrderExists}
                            className="mt-3 w-full rounded-lg bg-amber-500 px-3 py-2 text-xs font-semibold text-white hover:bg-amber-600 disabled:cursor-not-allowed disabled:bg-gray-300"
                        >
                            + Tambah ke Order
                        </button>
                    </div>
                ))}
            </div>
            {!loading && filteredMenus.length === 0 && (
                <p className="rounded-lg border border-dashed border-gray-300 p-3 text-xs text-gray-500">
                    Menu tidak ditemukan untuk kata kunci/kategori ini.
                </p>
            )}
        </div>
    );
}
