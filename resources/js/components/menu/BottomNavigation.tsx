interface BottomNavigationProps {
    activeTab?: 'home' | 'orders' | 'profile';
    onTabClick?: (tab: string) => void;
}

export default function BottomNavigation({ activeTab = 'home', onTabClick }: BottomNavigationProps) {
    const tabs = [
        {
            id: 'home',
            label: 'Beranda',
            icon: (isActive: boolean) => (
                <svg
                    className={`h-6 w-6 ${isActive ? 'text-orange-500' : 'text-gray-400'}`}
                    fill="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z" />
                </svg>
            )
        },
        {
            id: 'orders',
            label: 'Pesanan',
            icon: (isActive: boolean) => (
                <svg
                    className={`h-6 w-6 ${isActive ? 'text-orange-500' : 'text-gray-400'}`}
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth={2}
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                    />
                </svg>
            )
        }
    ];

    return (
        <nav className="fixed right-0 bottom-0 left-0 z-50 border-t border-gray-100 bg-white px-6 py-3">
            <div className="mx-auto flex max-w-lg items-center justify-around">
                {tabs.map((tab) => (
                    <button
                        key={tab.id}
                        onClick={() => onTabClick?.(tab.id)}
                        className="flex flex-col items-center gap-1"
                    >
                        {tab.icon(activeTab === tab.id)}
                        <span className={`text-xs font-medium ${activeTab === tab.id ? 'text-orange-500' : 'text-gray-400'}`}>
                            {tab.label}
                        </span>
                    </button>
                ))}
            </div>
        </nav>
    );
}
