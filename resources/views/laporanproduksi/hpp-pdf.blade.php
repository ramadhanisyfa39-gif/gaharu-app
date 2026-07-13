<!DOCTYPE html>
<html>
<head>
    <title>Laporan Harga Pokok Produksi</title>
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
        .main-table tfoot th { background-color: #f8f9fa; padding: 8px; border-top: 2px solid #333; font-weight: bold; font-size: 11px; }
        .main-table tr:nth-child(even) { background-color: #fcfcfc; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
    </style>
</head>
<body>

    <div class="header">
        <h2>LAPORAN HARGA POKOK PRODUKSI / HPP</h2>
        <p>CV Gaharu App</p>
    </div>

    <table class="info-table">
        <tr>
            <td width="15%"><strong>Periode Laporan</strong></td>
            <td width="3%">:</td>
            <td>{{ date('d-m-Y', strtotime($startDate)) }} s/d {{ date('d-m-Y', strtotime($endDate)) }}</td>
            <td width="15%" class="text-right"><strong>Tanggal Cetak</strong></td>
            <td width="3%">:</td>
            <td width="20%">{{ date('d-m-Y H:i') }}</td>
        </tr>
    </table>

    <table class="main-table">
        <thead>
            <tr>
                <th width="120">Kode Barang</th>
                <th>Nama Produk Jadi</th>
                <th class="text-center" width="120">Total Qty Produksi</th>
                <th class="text-right" width="180">Total Nilai HPP (BBB+BTKL+BOP)</th>
                <th class="text-right" width="160">Rata-rata HPP / Satuan</th>
            </tr>
        </thead>
        <tbody>
            @php $grandTotalHpp = 0; @endphp
            @forelse($laporanHpp as $row)
            @php 
                $hppPerSatuan = $row->total_qty > 0 ? ($row->total_hpp / $row->total_qty) : 0;
                $grandTotalHpp += $row->total_hpp;
            @endphp
            <tr>
                <td class="fw-bold">{{ $row->kode_barang }}</td>
                <td>{{ $row->nama_produk }}</td>
                <td class="text-center">{{ number_format($row->total_qty, 0, ',', '.') }} {{ $row->satuan ?? 'Pcs' }}</td>
                <td class="text-right fw-bold">Rp {{ number_format($row->total_hpp, 2, ',', '.') }}</td>
                <td class="text-right text-info">Rp {{ number_format($hppPerSatuan, 2, ',', '.') }} / {{ $row->satuan ?? 'Pcs' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center text-muted">Tidak ada perputaran HPP produksi pada periode ini.</td>
            </tr>
            @endforelse
        </tbody>
        @if($laporanHpp->count() > 0)
        <tfoot>
            <tr>
                <th colspan="3" class="text-right">GRAND TOTAL BIAYA PRODUKSI:</th>
                <th class="text-right text-danger" style="font-size: 12px;">Rp {{ number_format($grandTotalHpp, 2, ',', '.') }}</th>
                <th></th>
            </tr>
        </tfoot>
        @endif
    </table>

</body>
</html>
