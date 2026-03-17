export default function WelcomeScreen({ onStart }: { onStart: () => void }) {
    return (
        <div className="flex min-h-screen flex-col items-center justify-between bg-white px-6 py-10">
            {/* Top label */}
            <p className="text-xs font-medium uppercase tracking-[0.3em] text-gray-400">
                Selamat Datang 
            </p>

            {/* Center content */}
            <div className="flex flex-col items-center text-center -mt-8">
                <h1 className="text-3xl font-bold leading-tight tracking-wide text-gray-800">
                    WARMINDO<br /><span className="text-orange-500">JAPEMETHE</span>
                </h1>

                {/* Logo circle */}
                <div className="my-8 flex h-55 w-55 items-center justify-center rounded-full border-4 border-gray-100 bg-white shadow-lg shadow-gray-100">
                    <img
                        src="/storage/logo.webp"
                        alt="Japemethe Logo"
                        className="h-54 w-54 object-contain"
                    />
                </div>

                <h2 className="text-lg font-bold text-gray-800">SELAMAT DATANG</h2>
                <p className="mt-3 max-w-xs text-sm leading-relaxed text-gray-400">
                    Hai! selamat datang di JAPEMETHE warmindo terenak dipurwokerto.
                    <br />
                    Yuk reservasi meja sekarang!
                </p>
            </div>

            {/* Start button */}
            <button
                onClick={onStart}
                className="w-full max-w-xs rounded-full bg-orange-500 py-3.5 text-sm font-bold text-white shadow-md shadow-orange-500/25 transition-all hover:bg-orange-600 hover:shadow-lg active:scale-[0.98]"
            >
                Reservasi Sekarang
            </button>
        </div>
    );
}
