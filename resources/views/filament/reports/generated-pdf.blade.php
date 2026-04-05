<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan {{ $reportType }} – Warmindo Japemethe</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap');

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #1a1a2e;
            font-size: 11px;
            line-height: 1.5;
            background: #ffffff;
        }

        /* ── HEADER ── */
        .header {
            display: table;
            width: 100%;
            background: #1a1a2e;
            padding: 18px 24px;
            margin-bottom: 0;
        }

        .header-left {
            display: table-cell;
            vertical-align: middle;
            width: 64px;
        }

        .header-center {
            display: table-cell;
            vertical-align: middle;
            padding-left: 14px;
        }

        .brand-name {
            font-size: 18px;
            font-weight: 800;
            color: #f5c842;
            letter-spacing: 0.5px;
            line-height: 1.1;
        }

        .brand-tagline {
            font-size: 9px;
            color: #a0aec0;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-top: 2px;
        }

        .header-right {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
        }

        .report-badge {
            display: inline-block;
            background: #f5c842;
            color: #1a1a2e;
            font-size: 9px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* ── ACCENT BAR ── */
        .accent-bar {
            height: 4px;
            background: linear-gradient(to right, #f5c842, #f97316, #e11d48);
        }

        /* ── META INFO ── */
        .meta-section {
            background: #f8f9fb;
            border-left: 4px solid #f5c842;
            padding: 10px 16px;
            margin: 16px 0 12px;
            font-size: 10px;
            color: #4b5563;
        }

        .meta-section table {
            border: none;
            margin: 0;
        }

        .meta-section td {
            border: none;
            padding: 1px 16px 1px 0;
            font-size: 10px;
            color: #4b5563;
        }

        .meta-label {
            font-weight: 700;
            color: #374151;
            white-space: nowrap;
        }

        /* ── SECTION HEADINGS ── */
        h2 {
            font-size: 12px;
            font-weight: 700;
            color: #1a1a2e;
            margin: 18px 0 8px;
            padding: 6px 10px;
            background: #f0f2f8;
            border-left: 3px solid #f5c842;
        }

        /* ── SUMMARY CARDS ── */
        .summary-grid {
            display: table;
            width: 100%;
            margin-bottom: 4px;
        }

        .summary-card {
            display: table-cell;
            background: #1a1a2e;
            color: #fff;
            border-radius: 6px;
            padding: 10px 14px;
            text-align: center;
            vertical-align: middle;
        }

        .summary-card + .summary-card {
            padding-left: 8px;
        }

        .summary-card .card-value {
            font-size: 14px;
            font-weight: 800;
            color: #f5c842;
            display: block;
        }

        .summary-card .card-label {
            font-size: 9px;
            color: #a0aec0;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-top: 3px;
        }

        /* Spacer between cards */
        .summary-spacer {
            display: table-cell;
            width: 8px;
        }

        /* ── TABLE ── */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            font-size: 10.5px;
        }

        thead tr {
            background: #1a1a2e;
            color: #f5c842;
        }

        th {
            padding: 7px 10px;
            text-align: left;
            font-weight: 700;
            font-size: 9.5px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }

        td {
            border-bottom: 1px solid #e5e7eb;
            padding: 7px 10px;
            color: #374151;
            vertical-align: top;
        }

        tbody tr:nth-child(even) td {
            background: #f9fafb;
        }

        tbody tr:last-child td {
            border-bottom: 2px solid #1a1a2e;
        }

        .text-right {
            text-align: right;
        }

        .empty {
            color: #9ca3af;
            font-style: italic;
            padding: 10px 0;
        }

        /* ── FOOTER ── */
        .footer {
            margin-top: 28px;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 9px;
            color: #9ca3af;
        }

        .footer strong {
            color: #f5c842;
        }
    </style>
</head>
<body>

    {{-- ══ HEADER ══ --}}
    <div class="header">
        <div class="header-left">
            {{-- Logo SVG Warmindo Japemethe --}}
            <svg width="56" height="56" viewBox="0 0 56 56" xmlns="http://www.w3.org/2000/svg">
                <!-- Bowl base -->
                <circle cx="28" cy="28" r="26" fill="#f5c842"/>
                <!-- Bowl shape -->
                <ellipse cx="28" cy="33" rx="17" ry="10" fill="#1a1a2e"/>
                <rect x="11" y="24" width="34" height="10" rx="2" fill="#1a1a2e"/>
                <!-- Steam lines -->
                <path d="M20 20 Q21 16 20 12" stroke="#f97316" stroke-width="1.8" stroke-linecap="round" fill="none"/>
                <path d="M28 19 Q29 15 28 11" stroke="#f97316" stroke-width="1.8" stroke-linecap="round" fill="none"/>
                <path d="M36 20 Q37 16 36 12" stroke="#f97316" stroke-width="1.8" stroke-linecap="round" fill="none"/>
                <!-- Noodle swoosh inside bowl -->
                <path d="M16 30 Q22 27 28 30 Q34 33 40 30" stroke="#f5c842" stroke-width="1.6" fill="none" stroke-linecap="round"/>
            </svg>
        </div>

        <div class="header-center">
            <div class="brand-name">Warmindo Japemethe</div>
            <div class="brand-tagline">Warung Mie Indomie · Laporan Resmi</div>
        </div>

        <div class="header-right">
            <span class="report-badge">
                @switch($reportType)
                    @case('financial') Laporan Pemasukan @break
                    @case('sales') Laporan Penjualan @break
                    @case('inventory') Laporan Stok @break
                    @case('customer') Laporan Pelanggan @break
                    @default Laporan
                @endswitch
            </span>
        </div>
    </div>

    <div class="accent-bar"></div>

    {{-- ══ META INFO ══ --}}
    <div class="meta-section">
        <table>
            <tr>
                <td class="meta-label">Periode</td>
                <td>{{ $startDate->format('d/m/Y') }} – {{ $endDate->format('d/m/Y') }}</td>
                <td class="meta-label">Dibuat oleh</td>
                <td>{{ $generatedBy }}</td>
                <td class="meta-label">Dibuat pada</td>
                <td>{{ $generatedAt->format('d/m/Y H:i') }}</td>
            </tr>
        </table>
    </div>

    {{-- ══════════════════════════════════ --}}
    {{-- FINANCIAL REPORT                   --}}
    {{-- ══════════════════════════════════ --}}
    @if ($reportType === 'financial')

        <h2>Ringkasan</h2>
        <div class="summary-grid">
            <div class="summary-card">
                <span class="card-value">Rp {{ number_format($payload['summary']['total_income'] ?? 0, 0, ',', '.') }}</span>
                <div class="card-label">Total Pemasukan</div>
            </div>
            <div class="summary-spacer"></div>
            <div class="summary-card">
                <span class="card-value">{{ number_format($payload['summary']['total_transactions'] ?? 0, 0, ',', '.') }}</span>
                <div class="card-label">Total Transaksi</div>
            </div>
        </div>

        <h2>Pemasukan per Metode Pembayaran</h2>
        @if (! empty($payload['payment_by_method']) && count($payload['payment_by_method']) > 0)
            <table>
                <thead>
                    <tr>
                        <th>Metode Pembayaran</th>
                        <th class="text-right">Jumlah Transaksi</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($payload['payment_by_method'] as $row)
                        <tr>
                            <td>{{ strtoupper((string) ($row->payment_method ?? '-')) }}</td>
                            <td class="text-right">{{ number_format((int) ($row->total_transactions ?? 0), 0, ',', '.') }}</td>
                            <td class="text-right">Rp {{ number_format((float) ($row->total_amount ?? 0), 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="empty">Tidak ada data pada periode ini.</p>
        @endif

        <h2>Pemasukan Harian</h2>
        @if (! empty($payload['daily_income']) && count($payload['daily_income']) > 0)
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th class="text-right">Total Pemasukan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($payload['daily_income'] as $row)
                        <tr>
                            <td>{{ \Illuminate\Support\Carbon::parse($row->day)->format('d/m/Y') }}</td>
                            <td class="text-right">Rp {{ number_format((float) ($row->total_amount ?? 0), 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="empty">Tidak ada data harian pada periode ini.</p>
        @endif

    @endif

    {{-- ══════════════════════════════════ --}}
    {{-- SALES REPORT                       --}}
    {{-- ══════════════════════════════════ --}}
    @if ($reportType === 'sales')

        <h2>Ringkasan</h2>
        <div class="summary-grid">
            <div class="summary-card">
                <span class="card-value">{{ number_format($payload['summary']['total_qty'] ?? 0, 0, ',', '.') }}</span>
                <div class="card-label">Item Terjual</div>
            </div>
            <div class="summary-spacer"></div>
            <div class="summary-card">
                <span class="card-value">Rp {{ number_format($payload['summary']['gross_sales'] ?? 0, 0, ',', '.') }}</span>
                <div class="card-label">Omzet Kotor</div>
            </div>
            <div class="summary-spacer"></div>
            <div class="summary-card">
                <span class="card-value">{{ number_format($payload['summary']['total_orders'] ?? 0, 0, ',', '.') }}</span>
                <div class="card-label">Order Selesai</div>
            </div>
        </div>

        <h2>Menu Terlaris</h2>
        @if (! empty($payload['top_menus']) && count($payload['top_menus']) > 0)
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nama Menu</th>
                        <th class="text-right">Qty Terjual</th>
                        <th class="text-right">Total Penjualan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($payload['top_menus'] as $i => $row)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $row->menu_name }}</td>
                            <td class="text-right">{{ number_format((int) ($row->total_qty ?? 0), 0, ',', '.') }}</td>
                            <td class="text-right">Rp {{ number_format((float) ($row->total_amount ?? 0), 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="empty">Tidak ada data menu terjual pada periode ini.</p>
        @endif

    @endif

    {{-- ══════════════════════════════════ --}}
    {{-- INVENTORY REPORT                   --}}
    {{-- ══════════════════════════════════ --}}
    @if ($reportType === 'inventory')

        <h2>Ringkasan</h2>
        <div class="summary-grid">
            <div class="summary-card">
                <span class="card-value">{{ number_format($payload['summary']['total_menu'] ?? 0, 0, ',', '.') }}</span>
                <div class="card-label">Total Menu</div>
            </div>
            <div class="summary-spacer"></div>
            <div class="summary-card">
                <span class="card-value">{{ number_format($payload['summary']['low_stock_count'] ?? 0, 0, ',', '.') }}</span>
                <div class="card-label">Stok Rendah (≤ 10)</div>
            </div>
        </div>

        <h2>Daftar Stok Menu</h2>
        @if (! empty($payload['items']) && count($payload['items']) > 0)
            <table>
                <thead>
                    <tr>
                        <th>Nama Menu</th>
                        <th class="text-right">Stok</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($payload['items'] as $row)
                        <tr>
                            <td>{{ $row->name }}</td>
                            <td class="text-right">{{ number_format((int) ($row->stock ?? 0), 0, ',', '.') }}</td>
                            <td>{{ $row->status_menu }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="empty">Data stok tidak tersedia.</p>
        @endif

    @endif

    {{-- ══════════════════════════════════ --}}
    {{-- CUSTOMER / RESERVATION REPORT      --}}
    {{-- ══════════════════════════════════ --}}
    @if ($reportType === 'customer')

        <h2>Ringkasan</h2>
        <div class="summary-grid">
            <div class="summary-card" style="width:200px;">
                <span class="card-value">{{ number_format($payload['summary']['total_reservations'] ?? 0, 0, ',', '.') }}</span>
                <div class="card-label">Total Reservasi</div>
            </div>
        </div>

        <h2>Reservasi per Tipe Duduk</h2>
        @if (! empty($payload['by_seating_type']) && count($payload['by_seating_type']) > 0)
            <table>
                <thead>
                    <tr>
                        <th>Tipe Duduk</th>
                        <th class="text-right">Total Reservasi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($payload['by_seating_type'] as $row)
                        <tr>
                            <td>{{ $row->seating_type }}</td>
                            <td class="text-right">{{ number_format((int) ($row->total ?? 0), 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="empty">Tidak ada data tipe duduk pada periode ini.</p>
        @endif

        <h2>Reservasi per Status</h2>
        @if (! empty($payload['by_status']) && count($payload['by_status']) > 0)
            <table>
                <thead>
                    <tr>
                        <th>Status</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($payload['by_status'] as $row)
                        <tr>
                            <td>{{ $row->status }}</td>
                            <td class="text-right">{{ number_format((int) ($row->total ?? 0), 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="empty">Tidak ada data status reservasi pada periode ini.</p>
        @endif

    @endif

    {{-- ══ FOOTER ══ --}}
    <div class="footer">
        <strong>Warmindo Japemethe</strong> &mdash; Dokumen ini digenerate secara otomatis oleh sistem &middot; {{ $generatedAt->format('d/m/Y H:i') }}
    </div>

</body>
</html>
