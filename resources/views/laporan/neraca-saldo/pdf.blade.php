<!DOCTYPE html>
<html>
<head>
    <title>Laporan Neraca Saldo</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; color: #333; margin: 20px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 0; font-size: 16px; color: #1e3a8a; }
        .header h3 { margin: 5px 0 0 0; font-size: 13px; color: #333; }
        .header p { margin: 5px 0 0 0; font-size: 11px; color: #666; }
        .info-table { width: 100%; margin-bottom: 15px; border-collapse: collapse; }
        .info-table td { padding: 4px 0; font-size: 10px; }
        .main-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .main-table th { background-color: #1e3a8a; color: #ffffff; text-align: center; padding: 6px; font-weight: bold; font-size: 10px; border: 1px solid #ddd; }
        .main-table td { padding: 6px; border: 1px solid #ddd; font-size: 9px; }
        .main-table tr.category-row { background-color: #f1f5f9; font-weight: bold; }
        .main-table tr.total-row { background-color: #e2e8f0; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
    </style>
</head>
<body>

    <div class="header">
        <h2>CV GAHARU AGUNG SEJAHTERA</h2>
        <h3>LAPORAN NERACA SALDO</h3>
        <p>Periode: {{ date('F', mktime(0, 0, 0, $bulan, 1)) }} {{ $tahun }}</p>
    </div>

    <table class="info-table">
        <tr>
            <td width="15%"><strong>Tanggal Cetak</strong></td>
            <td width="3%">:</td>
            <td>{{ date('d-m-Y H:i') }}</td>
        </tr>
    </table>

    <table class="main-table">
        <thead>
            <tr>
                <th rowspan="2">Kode Akun</th>
                <th rowspan="2">Nama Akun</th>
                <th colspan="2">Saldo Awal</th>
                <th colspan="2">Mutasi Periode</th>
                <th colspan="2">Saldo Akhir</th>
            </tr>
            <tr>
                <th class="text-right">Debit</th>
                <th class="text-right">Kredit</th>
                <th class="text-right">Debit</th>
                <th class="text-right">Kredit</th>
                <th class="text-right">Debit</th>
                <th class="text-right">Kredit</th>
            </tr>
        </thead>
        <tbody>
            @php
                $grandSA_D = 0; $grandSA_K = 0;
                $grandM_D = 0; $grandM_K = 0;
                $grandAK_D = 0; $grandAK_K = 0;
            @endphp
            @foreach($neracaSaldo->groupBy('tipe') as $tipe => $items)
                <tr class="category-row">
                    <td colspan="8">● {{ $tipe == 'Beban' ? 'BEBAN & HPP' : $tipe }}</td>
                </tr>
                @foreach($items as $row)
                    @php
                        $grandSA_D += $row->saldo_awal_debit ?? 0;
                        $grandSA_K += $row->saldo_awal_kredit ?? 0;
                        $grandM_D += $row->mutasi_debit ?? 0;
                        $grandM_K += $row->mutasi_kredit ?? 0;
                        $grandAK_D += $row->debet_akhir ?? 0;
                        $grandAK_K += $row->kredit_akhir ?? 0;
                    @endphp
                    <tr>
                        <td>{{ $row->kode }}</td>
                        <td>{{ $row->nama }}</td>
                        <td class="text-right">{{ ($row->saldo_awal_debit ?? 0) > 0 ? number_format($row->saldo_awal_debit, 0, ',', '.') : '-' }}</td>
                        <td class="text-right">{{ ($row->saldo_awal_kredit ?? 0) > 0 ? number_format($row->saldo_awal_kredit, 0, ',', '.') : '-' }}</td>
                        <td class="text-right">{{ ($row->mutasi_debit ?? 0) > 0 ? number_format($row->mutasi_debit, 0, ',', '.') : '-' }}</td>
                        <td class="text-right">{{ ($row->mutasi_kredit ?? 0) > 0 ? number_format($row->mutasi_kredit, 0, ',', '.') : '-' }}</td>
                        <td class="text-right">{{ ($row->debet_akhir ?? 0) > 0 ? number_format($row->debet_akhir, 0, ',', '.') : '-' }}</td>
                        <td class="text-right">{{ ($row->kredit_akhir ?? 0) > 0 ? number_format($row->kredit_akhir, 0, ',', '.') : '-' }}</td>
                    </tr>
                @endforeach
            @endforeach
            <tr class="total-row">
                <td colspan="2">Total Keseluruhan</td>
                <td class="text-right">Rp {{ number_format($grandSA_D, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($grandSA_K, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($grandM_D, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($grandM_K, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($grandAK_D, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($grandAK_K, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

</body>
</html>
