<!DOCTYPE html>
<html>
<head>
    <title>Laporan Penjualan POS</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #333; margin: 20px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 0; font-size: 18px; color: #1e3a8a; }
        .header p { margin: 5px 0 0 0; font-size: 12px; color: #666; }
        .info-table { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
        .info-table td { padding: 4px 0; font-size: 11px; }
        .summary-box { width: 100%; margin-bottom: 25px; border-collapse: collapse; }
        .summary-box td { width: 33.33%; padding: 10px; border: 1px solid #ddd; background-color: #fafafa; text-align: center; }
        .summary-box td span { display: block; font-size: 10px; text-transform: uppercase; color: #666; font-weight: bold; }
        .summary-box td strong { display: block; font-size: 14px; margin-top: 5px; color: #333; }
        .summary-box td.laba strong { color: #198754; }
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
        <h2>LAPORAN PENJUALAN POS</h2>
        <p>CV Gaharu App</p>
    </div>

    <table class="info-table">
        <tr>
            <td width="15%"><strong>Periode Laporan</strong></td>
            <td width="3%">:</td>
            <td>{{ date('d-m-Y', strtotime($tanggal_mulai)) }} s/d {{ date('d-m-Y', strtotime($tanggal_selesai)) }}</td>
            <td width="15%" class="text-right"><strong>Tanggal Cetak</strong></td>
            <td width="3%">:</td>
            <td width="20%">{{ date('d-m-Y H:i') }}</td>
        </tr>
    </table>

    <table class="summary-box">
        <tr>
            <td>
                <span>Total Omzet</span>
                <strong>Rp {{ number_format($total_omzet, 0, ',', '.') }}</strong>
            </td>
            <td>
                <span>Total HPP</span>
                <strong>Rp {{ number_format($total_hpp, 0, ',', '.') }}</strong>
            </td>
            <td class="laba">
                <span>Laba Kotor</span>
                <strong>Rp {{ number_format($total_laba, 0, ',', '.') }}</strong>
            </td>
        </tr>
    </table>

    <table class="main-table">
        <thead>
            <tr>
                <th width="30" class="text-center">No</th>
                <th width="100">No. Transaksi</th>
                <th>Tanggal & Waktu</th>
                <th>Gudang / Outlet</th>
                <th class="text-right">Total Omzet</th>
                <th class="text-right">Total HPP</th>
                <th class="text-right">Laba Kotor</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data_penjualan as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="fw-bold">{{ $item->kode_transaksi }}</td>
                <td>{{ date('d-m-Y H:i', strtotime($item->tanggal)) }}</td>
                <td>{{ $item->gudang->nama ?? '-' }}</td>
                <td class="text-right">Rp {{ number_format($item->total, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($item->calculated_hpp, 0, ',', '.') }}</td>
                <td class="text-right fw-bold">Rp {{ number_format($item->calculated_laba, 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center text-muted">Tidak ada data transaksi POS ditemukan pada periode ini.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>
