<!DOCTYPE html>
<html>
<head>
    <title>Laporan Rekapitulasi Hasil Produksi</title>
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
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
    </style>
</head>
<body>

    <div class="header">
        <h2>LAPORAN REKAPITULASI HASIL PRODUKSI (OPERASIONAL)</h2>
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
                <th width="80">Tanggal</th>
                <th>Kode Produksi</th>
                <th>Kode WO</th>
                <th>Nama Produk</th>
                <th>Gudang Tujuan</th>
                <th class="text-center" width="80">Target WO</th>
                <th class="text-center" width="80">Realisasi Output</th>
                <th class="text-center" width="80">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rekapitulasi as $row)
            <tr>
                <td>{{ \Carbon\Carbon::parse($row->tanggal)->format('d-M-Y') }}</td>
                <td class="fw-bold">{{ $row->kode_produksi }}</td>
                <td class="fw-bold">{{ $row->kode_wo ?? '-' }}</td>
                <td>{{ $row->nama_produk }}</td>
                <td>{{ $row->nama_gudang ?? 'Gudang B2B' }}</td>
                <td class="text-center">{{ number_format($row->qty_target, 0, ',', '.') }}</td>
                <td class="text-center fw-bold">{{ number_format($row->qty_hasil, 0, ',', '.') }}</td>
                <td class="text-center">{{ strtoupper($row->status_produksi ?? 'SELESAI') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center text-muted">Tidak ada data produksi pada periode ini.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>
