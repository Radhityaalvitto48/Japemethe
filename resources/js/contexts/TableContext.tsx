import { createContext, useContext, useEffect, useState } from 'react';

interface Table {
    id: number;
    table_number: string;
    seating_type: 'lesehan' | 'kursi';
    qr_code: string;
    is_active: boolean;
}

interface TableContextType {
    currentTable: Table | null;
    setCurrentTable: (table: Table | null) => void;
    clearTable: () => void;
}

const TableContext = createContext<TableContextType | undefined>(undefined);

export function TableProvider({ children }: { children: React.ReactNode }) {
    const [currentTable, setCurrentTableState] = useState<Table | null>(null);

    // Load table from localStorage on mount
    useEffect(() => {
        const savedTable = localStorage.getItem('current_table');
        if (savedTable) {
            try {
                setCurrentTableState(JSON.parse(savedTable));
            } catch (error) {
                console.error('Error parsing saved table:', error);
                localStorage.removeItem('current_table');
            }
        }
    }, []);

    const setCurrentTable = (table: Table | null) => {
        setCurrentTableState(table);
        if (table) {
            localStorage.setItem('current_table', JSON.stringify(table));
        } else {
            localStorage.removeItem('current_table');
        }
    };

    const clearTable = () => {
        setCurrentTable(null);
    };

    return (
        <TableContext.Provider value={{
            currentTable,
            setCurrentTable,
            clearTable,
        }}>
            {children}
        </TableContext.Provider>
    );
}

export function useTable() {
    const context = useContext(TableContext);
    if (context === undefined) {
        throw new Error('useTable must be used within a TableProvider');
    }
    return context;
}
