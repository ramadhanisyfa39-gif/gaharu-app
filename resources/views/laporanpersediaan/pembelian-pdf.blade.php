{{-- ============================================================ --}}
{{-- pembelian-pdf.blade.php                                      --}}
{{-- ============================================================ --}}
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family:'DejaVu Sans',sans-serif; font-size:11px; color:#222; }
    .header { border-bottom:3px solid #5a3416; padding-bottom:10px; margin-bottom:14px; }
    .header h1 { font-size:17px; font-weight:700; color:#5a3416; }
    .header .meta { font-size:10px; color:#888; margin-top:3px; }
    table { width:100%; border-collapse:collapse; }
    thead th { background:#5a3416; color:#fff; padding:7px 9px; font-size:10px; text-transform:uppercase; }
    tbody tr:nth-child(even) { background:#fdf9f6; }
    tbody td { padding:6px 9px; border-bottom:1px solid #f0e8e0; font-size:11px; }
    tfoot td { background:#fdf3ec; font-weight:700; color:#5a3416; padding:7px 9px; border-top:2px solid #eadfd4; }
    .badge { display:inline-block; padding:2px 6px; border-radius:10px; font-size:9px; font-weight:600; }
    .bs { background:#d1e7dd; color:#0a3622; }
    .bw { background:#fff3cd; color:#664d03; }
    .bi { background:#cff4fc; color:#055160; }
    .bd { background:#e2e3e5; color:#41464b; }
    .text-right { text-align:right; }
    .footer { margin-top:16px; border-top:1px solid #eee; padding-top:6px; font-size:9px; color:#aaa; text-align:right; }
</style>
</head>
<body>
    <div class="header">
        <h1>Laporan Pembelian</h1>
        <div class="meta">
            Dicetak: {{ now()->format('d M Y, H:i') }}
            @if(request('dari') || request('sampai'))
                · Periode: {{ request('dari') ?? '—' }} s/d {{ request('sampai') ?? '—' }}
            @endif
            @if(request('supplier_id'))
                · Supplier: {{ $data->first()?->supplier->nama ?? '-' }}
            @endif
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Kode</th>
                <th>Tanggal</th>
                <th>Supplier</th>
                <th>Gudang</th>
                <th class="text-right">Total</th>
                <th>Metode</th>
                <th>Jatuh Tempo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $row)
                @php
                    [$label, $cls] = match($row->metode_pembayaran) {
                        'cod'    => ['COD',    'bs'],
                        'termin' => ['Termin', 'bw'],
                        'dp'     => ['DP '.$row->persen_dp.'%', 'bi'],
                        default  => ['—',      'bd'],
                    };
                @endphp
                <tr>
                    <td>{{ $row->kode_pembelian }}</td>
                    <td>{{ \Carbon\Carbon::parse($row->tanggal)->format('d/m/Y') }}</td>
                    <td>{{ $row->supplier->nama ?? '-' }}</td>
                    <td>{{ $row->gudang->nama ?? '-' }}</td>
                    <td class="text-right">Rp {{ number_format($row->total, 0, ',', '.') }}</td>
                    <td><span class="badge {{ $cls }}">{{ $label }}</span></td>
                    <td>{{ $row->tanggal_jatuh_tempo ? \Carbon\Carbon::parse($row->tanggal_jatuh_tempo)->format('d/m/Y') : '—' }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4">Total ({{ $totalTransaksi }} transaksi)</td>
                <td class="text-right">Rp {{ number_format($totalNilai, 0, ',', '.') }}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>
    <div class="footer">Sistem ERP Gaharu · {{ auth()->user()->nama ?? '' }} · {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>