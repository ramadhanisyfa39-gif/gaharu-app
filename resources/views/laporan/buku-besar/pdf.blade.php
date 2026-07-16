<!DOCTYPE html>
<html>
<head>
    <title>Laporan Buku Besar</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; color: #333; margin: 20px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 0; font-size: 16px; color: #1e3a8a; }
        .header h3 { margin: 5px 0 0 0; font-size: 13px; color: #333; }
        .header p { margin: 5px 0 0 0; font-size: 11px; color: #666; }
        .info-table { width: 100%; margin-bottom: 15px; border-collapse: collapse; }
        .info-table td { padding: 4px 0; font-size: 10px; }
        .account-section { margin-bottom: 30px; page-break-inside: avoid; }
        .account-header { background-color: #1e3a8a; color: #ffffff; padding: 6px 10px; font-weight: bold; font-size: 11px; margin-bottom: 5px; }
        .main-table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        .main-table th { background-color: #f1f5f9; color: #333; text-align: left; padding: 6px; font-weight: bold; font-size: 9px; border: 1px solid #ddd; }
        .main-table td { padding: 6px; border: 1px solid #ddd; font-size: 9px; }
        .main-table tr.total-row { background-color: #fafafa; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
    </style>
</head>
<body>

    <div class="header">
        <h2>CV GAHARU AGUNG SEJAHTERA</h2>
        <h3>LAPORAN BUKU BESAR</h3>
        <p>Periode: {{ date('F', mktime(0, 0, 0, $bulan, 1)) }} {{ $tahun }}</p>
    </div>

    <table class="info-table">
        <tr>
            <td width="15%"><strong>Tanggal Cetak</strong></td>
            <td width="3%">:</td>
            <td>{{ date('d-m-Y H:i') }}</td>
        </tr>
    </table>

    @foreach($accountsData as $account)
        <div class="account-section">
            <div class="account-header">
                {{ $account->kode }} - {{ $account->nama }}
            </div>
            <table class="main-table">
                <thead>
                    <tr>
                        <th width="80">Tanggal</th>
                        <th width="100">Referensi</th>
                        <th>Keterangan</th>
                        <th width="100" class="text-right">Debit</th>
                        <th width="100" class="text-right">Kredit</th>
                        <th width="110" class="text-right">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $saldo = $account->beginning_balance;
                        $subDebet = 0; $subKredit = 0;
                        $saldoNormal = strtolower($account->saldo_normal);
                    @endphp
                    <tr style="font-style: italic; color: #777;">
                        <td>01/{{ $bulan }}/{{ $tahun }}</td>
                        <td>-</td>
                        <td>Saldo Awal (Beginning Balance)</td>
                        <td class="text-right">-</td>
                        <td class="text-right">-</td>
                        <td class="text-right">Rp {{ number_format($account->beginning_balance, 2, ',', '.') }}</td>
                    </tr>
                    @foreach($account->items as $item)
                        @php
                            if ($saldoNormal === 'kredit') {
                                $saldo += ($item->kredit - $item->debit);
                            } else {
                                $saldo += ($item->debit - $item->kredit);
                            }
                            $subDebet += $item->debit;
                            $subKredit += $item->kredit;
                        @endphp
                        <tr>
                            <td>{{ date('d-m-Y', strtotime($item->tanggal)) }}</td>
                            <td>{{ $item->no_ref }}</td>
                            <td>{{ $item->deskripsi }}</td>
                            <td class="text-right">{{ $item->debit > 0 ? 'Rp ' . number_format($item->debit, 2, ',', '.') : '-' }}</td>
                            <td class="text-right">{{ $item->kredit > 0 ? 'Rp ' . number_format($item->kredit, 2, ',', '.') : '-' }}</td>
                            <td class="text-right">Rp {{ number_format($saldo, 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td colspan="3">Total Mutasi & Saldo Akhir</td>
                        <td class="text-right">Rp {{ number_format($subDebet, 2, ',', '.') }}</td>
                        <td class="text-right">Rp {{ number_format($subKredit, 2, ',', '.') }}</td>
                        <td class="text-right">Rp {{ number_format($saldo, 2, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endforeach

</body>
</html>
