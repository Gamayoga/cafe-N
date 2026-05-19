<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: sans-serif; font-size: 11px; color: #1a1a1a; padding: 30px; }

        .header { text-align: center; border-bottom: 3px solid #E97D5A; padding-bottom: 14px; margin-bottom: 20px; }
        .header h1 { font-size: 20px; font-weight: 900; letter-spacing: -0.5px; text-transform: uppercase; }
        .header p { font-size: 10px; color: #666; margin-top: 4px; text-transform: uppercase; letter-spacing: 1px; }

        .stats { display: flex; gap: 12px; margin-bottom: 24px; }
        .stat-box { flex: 1; border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px 16px; }
        .stat-box .label { font-size: 9px; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: 1px; }
        .stat-box .value { font-size: 15px; font-weight: 900; margin-top: 4px; }

        .section-title { font-size: 11px; font-weight: 900; text-transform: uppercase; letter-spacing: 1px; color: #374151; margin-bottom: 8px; margin-top: 20px; }

        table { width: 100%; border-collapse: collapse; font-size: 10px; }
        thead tr { background-color: #E97D5A; color: white; }
        thead th { padding: 7px 10px; text-align: left; font-weight: 700; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; }
        tbody tr:nth-child(even) { background-color: #f9fafb; }
        tbody td { padding: 6px 10px; border-bottom: 1px solid #f3f4f6; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .footer { margin-top: 30px; border-top: 1px solid #e5e7eb; padding-top: 10px; text-align: center; font-size: 9px; color: #9ca3af; }
    </style>
</head>
<body>

    <div class="header">
        <h1>Laporan Penjualan Northern Cafe</h1>
        <p>Periode: {{ $startDate->format('d/m/Y') }} &mdash; {{ $endDate->format('d/m/Y') }}</p>
    </div>

    {{-- Ringkasan --}}
    <div class="stats">
        <div class="stat-box">
            <div class="label">Total Pendapatan</div>
            <div class="value">Rp {{ number_format($stats['revenue'], 0, ',', '.') }}</div>
        </div>
        <div class="stat-box">
            <div class="label">Total Pesanan</div>
            <div class="value">{{ number_format($stats['orders']) }}</div>
        </div>
        <div class="stat-box">
            <div class="label">Rata-rata per Order</div>
            <div class="value">Rp {{ number_format($stats['avg'], 0, ',', '.') }}</div>
        </div>
    </div>

    {{-- Produk Terlaris --}}
    <div class="section-title">Produk Terlaris</div>
    <table>
        <thead>
            <tr>
                <th class="text-center" style="width:30px">No</th>
                <th>Produk</th>
                <th class="text-right">Qty Terjual</th>
                <th class="text-right">Total Pendapatan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($topProducts as $idx => $item)
            <tr>
                <td class="text-center">{{ $idx + 1 }}</td>
                <td>{{ $item->product->name ?? '-' }}</td>
                <td class="text-right">{{ number_format($item->total_qty) }}</td>
                <td class="text-right">Rp {{ number_format($item->total_revenue, 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr><td colspan="4" class="text-center" style="padding:12px;color:#9ca3af;">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- Detail Transaksi --}}
    <div class="section-title">Detail Transaksi</div>
    <table>
        <thead>
            <tr>
                <th>Waktu</th>
                <th>Kode Transaksi</th>
                <th>Kasir</th>
                <th>Metode</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $tx)
            <tr>
                <td>{{ $tx->created_at->format('d/m/Y H:i') }}</td>
                <td>{{ $tx->transaction_code }}</td>
                <td>{{ $tx->cashier->name ?? 'System' }}</td>
                <td>{{ $tx->payment_method }}</td>
                <td class="text-right">Rp {{ number_format($tx->total_amount, 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-center" style="padding:12px;color:#9ca3af;">Tidak ada transaksi.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Dicetak pada {{ now()->format('d/m/Y H:i') }} WIB &bull; Northern Cafe
    </div>

</body>
</html>
