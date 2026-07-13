<!DOCTYPE html>
<html>
<head>
    <title>Laporan Pengeluaran Bahan Baku</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #333; margin: 20px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 0; font-size: 18px; color: #1e3a8a; }
        .header p { margin: 5px 0 0 0; font-size: 12px; color: #666; }
        .info-table { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
        .info-table td { padding: 4px 0; font-size: 11px; }
        .main-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .main-table th { background-color: #1e3a8a; color: #ffffff; text-align: left; padding: 8px; font-weight: bold; font-size: 11px; }
        .main-table td { padding: 8px; border-bottom: 1px solid #ddd; font-size: 10px; }
        .main-table tr:nth-child(even) { background-color: #fcfcfc; }
        .main-table tr.sub td { background-color: #f9f9f9; color: #666; font-size: 9px; }
        .main-table tfoot td { background-color: #f8f9fa; font-weight: bold; padding: 8px; border-top: 2px solid #333; font-size: 11px; color: #333; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 9px; font-weight: bold; text-transform: uppercase; }
        .bs { background-color: #d1e7dd; color: #0a3622; }
        .bd { background-color: #e2e3e5; color: #41464b; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
    </style>
</head>
<body>

    <div class="header">
        <h2>LAPORAN PENGELUARAN BAHAN BAKU</h2>
        <p>CV Gaharu App</p>
    </div>

    <table class="info-table">
        <tr>
            <td width="15%"><strong>Periode Laporan</strong></td>
            <td width="3%">:</td>
            <td>
                @if(request('dari') || request('sampai'))
                    {{ request('dari') ?? '—' }} s/d {{ request('sampai') ?? '—' }}
                @else
                    Semua Periode
                @endif
            </td>
            <td width="15%" class="text-right"><strong>Tanggal Cetak</strong></td>
            <td width="3%">:</td>
            <td width="20%">{{ date('d-m-Y H:i') }}</td>
        </tr>
    </table>

    <table class="main-table">
        <thead>
            <tr>
                <th width="100">Kode</th>
                <th>Tanggal</th>
                <th>Gudang</th>
                <th class="text-center" width="60">Item</th>
                <th class="text-right" width="120">Nilai HPP</th>
                <th class="text-center" width="80">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $row)
                <tr>
                    <td class="fw-bold">{{ $row->kode_pengeluaran }}</td>
                    <td>{{ \Carbon\Carbon::parse($row->tanggal)->format('d/m/Y') }}</td>
                    <td>{{ $row->gudang->nama ?? '-' }}</td>
                    <td class="text-center">{{ $row->details->count() }}</td>
                    <td class="text-right fw-bold">Rp {{ number_format($row->details->sum('hpp_total'), 0, ',', '.') }}</td>
                    <td class="text-center">
                        <span class="badge {{ $row->status === 'approved' ? 'bs' : 'bd' }}">{{ $row->status }}</span>
                    </td>
                </tr>
                @foreach($row->details as $d)
                    <tr class="sub">
                        <td style="padding-left:18px;">↳ {{ $d->barang->nama ?? '-' }}</td>
                        <td colspan="2" class="text-center">{{ number_format($d->qty, 2) }} {{ $d->satuan }}</td>
                        <td></td>
                        <td class="text-right">Rp {{ number_format($d->hpp_total, 0, ',', '.') }}</td>
                        <td></td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="text-right">TOTAL HPP:</td>
                <td class="text-right text-danger">Rp {{ number_format($data->sum(fn($d) => $d->details->sum('hpp_total')), 0, ',', '.') }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

</body>
</html>