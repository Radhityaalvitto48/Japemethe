type PosMenu = {
    id: number;
    name: string;
};

type PosTable = {
    id: number;
    table_number: string;
};

type ManualItem = {
    menu_id: number;
    quantity: number;
};

type ManualOrderDraftSectionProps = {
    tables: PosTable[];
    menus: PosMenu[];
    manualTableId: number | '';
    manualCustomerPhone: string;
    manualCustomerEmail: string;
    manualItems: ManualItem[];
    onChangeTable: (value: number | '') => void;
    onChangePhone: (value: string) => void;
    onChangeEmail: (value: string) => void;
    onDecreaseItem: (menuId: number, qty: number) => void;
    onIncreaseItem: (menuId: number, qty: number) => void;
    onRemoveItem: (menuId: number) => void;
    onCreateManualOrder: () => void;
};

export default function ManualOrderDraftSection({
    tables,
    menus,
    manualTableId,
    manualCustomerPhone,
    manualCustomerEmail,
    manualItems,
    onChangeTable,
    onChangePhone,
    onChangeEmail,
    onDecreaseItem,
    onIncreaseItem,
    onRemoveItem,
    onCreateManualOrder,
}: ManualOrderDraftSectionProps): React.JSX.Element {
    return (
        <div className="rounded-xl border border-gray-200 p-3">
            <p className="text-sm font-semibold text-gray-800">Draft Pesanan Manual</p>
            <div className="mt-2 grid gap-2">
                <select
                    value={manualTableId}
                    onChange={(event) => onChangeTable(event.target.value ? Number(event.target.value) : '')}
                    className="rounded-lg border border-gray-300 px-3 py-2 text-sm"
                >
                    <option value="">Pilih meja</option>
                    {tables.map((table) => (
                        <option key={table.id} value={table.id}>
                            {table.table_number}
                        </option>
                    ))}
                </select>
                <input
                    value={manualCustomerPhone}
                    onChange={(event) => onChangePhone(event.target.value)}
                    placeholder="No HP"
                    className="rounded-lg border border-gray-300 px-3 py-2 text-sm"
                />
                <input
                    value={manualCustomerEmail}
                    onChange={(event) => onChangeEmail(event.target.value)}
                    placeholder="Email"
                    className="rounded-lg border border-gray-300 px-3 py-2 text-sm"
                />
            </div>

            <div className="mt-2 max-h-32 space-y-1 overflow-y-auto rounded-lg bg-gray-50 p-2 text-xs">
                {manualItems.length === 0 && <p className="text-gray-500">Belum ada item manual.</p>}
                {manualItems.map((item) => {
                    const menu = menus.find((menuOption) => menuOption.id === item.menu_id);

                    if (!menu) {
                        return null;
                    }

                    return (
                        <div key={item.menu_id} className="flex items-center justify-between gap-2">
                            <span className="text-gray-700">{menu.name} x{item.quantity}</span>
                            <div className="flex items-center gap-2">
                                <button
                                    type="button"
                                    onClick={() => onDecreaseItem(item.menu_id, item.quantity - 1)}
                                    className="rounded border border-gray-300 px-2 py-0.5 text-[10px] font-semibold text-gray-700"
                                >
                                    -
                                </button>
                                <button
                                    type="button"
                                    onClick={() => onIncreaseItem(item.menu_id, item.quantity + 1)}
                                    className="rounded border border-gray-300 px-2 py-0.5 text-[10px] font-semibold text-gray-700"
                                >
                                    +
                                </button>
                                <button
                                    type="button"
                                    onClick={() => onRemoveItem(item.menu_id)}
                                    className="text-rose-600"
                                >
                                    hapus
                                </button>
                            </div>
                        </div>
                    );
                })}
            </div>

            <button
                type="button"
                onClick={onCreateManualOrder}
                className="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-700"
            >
                Buat Pesanan Manual
            </button>
        </div>
    );
}
