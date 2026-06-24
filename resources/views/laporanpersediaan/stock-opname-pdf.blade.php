{{-- stock-opname-pdf.blade.php --}}
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family:'DejaVu Sans',sans-serif; font-size:10px; color:#222; }
    .header { border-bottom:3px solid #5a3416; padding-bottom:10px; margin-bottom:14px; }
    .header h1 { font-size:17px; font-weight:700; color:#5a3416; }
    .header .meta { font-size:10px; color:#888; margin-top:3px; }
    table { width:100%; border-collapse:collapse; }
    thead th { background:#5a3416; color:#fff; padding:6px 8px; font-size:9px; text-transform:uppercase; }
    tbody tr:nth-child(even) { background:#fdf9f6; }
    tbody tr.sub td { background:#f8f8f8; color:#777; font-size:9px; }
    tbody td { padding:5px 8px; border-bottom:1px solid #f0e8e0; }
    tfoot td { background:#fdf3ec; font-weight:700; color:#5a3416; padding:6px 8px; border-top:2px solid #eadfd4; }
    .badge { display:inline-block; padding:2px 6px; border-radius:10px; font-size:8px; font-weight:600; }
    .bs { background:#d1e7dd; color:#0a3622; }
    .bd { background:#e2e3e5; color:#41464b; }
    .plus  { color:#198754; font-weight:700; }
    .minus { color:#dc3545; font-weight:700; }
    .text-right  { text-align:right; }
    .text-center { text-align:center; }
    .footer { margin-top:16px; border-top:1px solid #eee; padding-top:6px; font-size:9px; color:#aaa; text-align:right; }
</style>
</head>
<body>
    <div class="header">
        <h1>Laporan Stock Opname</h1>
        <div class="meta">
            Dicetak: {{ now()->format('d M Y, H:i') }}
            @if(request('dari') || request('sampai'))
                · Periode: {{ request('dari') ?? '—' }} s/d {{ request('sampai') ?? '—' }}
            @endif
        </div>
    </div>
    <table>
        <thead>
            <tr>
                <th>Kode Opname</th>
                <th>Tanggal</th>
                <th>Gudang</th>
                <th class="text-center">Item</th>
                <th class="text-center">Selisih +</th>
                <th class="text-center">Selisih −</th>
                <th class="text-right">Nilai Selisih</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $row)
                @php
                    $sPlus  = $row->details->where('selisih','>',0)->sum('selisih');
                    $sMinus = $row->details->where('selisih','<',0)->sum('selisih');
                    $sNilai = $row->details->sum('nilai_selisih');
                @endphp
                <tr>
                    <td>{{ $row->kode_opname }}</td>
                    <td>{{ \Carbon\Carbon::parse($row->tanggal)->format('d/m/Y') }}</td>
                    <td>{{ $row->gudang->nama ?? '-' }}</td>
                    <td class="text-center">{{ $row->details->count() }}</td>
                    <td class="text-center plus">{{ $sPlus > 0 ? '+'.number_format($sPlus,2) : '—' }}</td>
                    <td class="text-center minus">{{ $sMinus < 0 ? number_format($sMinus,2) : '—' }}</td>
                    <td class="text-right {{ $sNilai < 0 ? 'minus' : ($sNilai > 0 ? 'plus' : '') }}">
                        {{ $sNilai != 0 ? 'Rp '.number_format($sNilai,0,',','.') : '—' }}
                    </td>
                    <td class="text-center">
                        <span class="badge {{ $row->status === 'approved' ? 'bs' : 'bd' }}">{{ ucfirst($row->status) }}</span>
                    </td>
                </tr>
                @foreach($row->details->where('selisih','!=',0) as $d)
                    <tr class="sub">
                        <td style="padding-left:18px;">↳ {{ $d->barang->nama ?? '-' }}</td>
                        <td colspan="2">Sistem: {{ number_format($d->stok_sistem,2) }} → Fisik: {{ number_format($d->stok_fisik,2) }}</td>
                        <td></td>
                        <td class="text-center plus">{{ $d->selisih > 0 ? '+'.number_format($d->selisih,2) : '' }}</td>
                        <td class="text-center minus">{{ $d->selisih < 0 ? number_format($d->selisih,2) : '' }}</td>
                        <td class="text-right">Rp {{ number_format($d->nilai_selisih,0,',','.') }}</td>
                        <td></td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6">Total Nilai Selisih</td>
                <td class="text-right">Rp {{ number_format($data->sum(fn($d) => $d->details->sum('nilai_selisih')), 0, ',', '.') }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
    <div class="footer">Sistem ERP Gaharu · {{ auth()->user()->nama ?? '' }} · {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>