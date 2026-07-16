<!DOCTYPE html>
<html>
<head>
    <title>Laporan Neraca</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; color: #333; margin: 20px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 0; font-size: 16px; color: #1e3a8a; }
        .header h3 { margin: 5px 0 0 0; font-size: 13px; color: #333; }
        .header p { margin: 5px 0 0 0; font-size: 11px; color: #666; }
        .info-table { width: 100%; margin-bottom: 15px; border-collapse: collapse; }
        .info-table td { padding: 4px 0; font-size: 10px; }
        .t-account-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .t-account-table > tr > td { vertical-align: top; width: 50%; padding: 10px; border: 1px solid #ddd; }
        .judul-sisi { text-align: center; font-weight: bold; background-color: #1e3a8a; color: #ffffff; padding: 6px; font-size: 11px; text-transform: uppercase; margin-bottom: 10px; }
        .sub-table { width: 100%; border-collapse: collapse; }
        .sub-table td { padding: 5px 0; font-size: 9px; }
        .total-box { margin-top: 20px; padding: 8px 0; border-top: 2px solid #333; border-bottom: 2px solid #333; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
    </style>
</head>
<body>

    <div class="header">
        <h2>CV GAHARU AGUNG SEJAHTERA</h2>
        <h3>LAPORAN NERACA</h3>
        <p>Periode: {{ date('F', mktime(0, 0, 0, $bulan, 1)) }} {{ $tahun }}</p>
    </div>

    <table class="info-table">
        <tr>
            <td width="15%"><strong>Tanggal Cetak</strong></td>
            <td width="3%">:</td>
            <td>{{ date('d-m-Y H:i') }}</td>
        </tr>
    </table>

    <table class="t-account-table">
        <tr>
            <!-- AKTIVA -->
            <td>
                <div class="judul-sisi">Aktiva</div>
                <table class="sub-table">
                    @foreach($aktiva as $item)
                        <tr>
                            <td>{{ $item->nama }}</td>
                            <td class="text-right">Rp {{ number_format($item->saldo, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </table>
                <div class="total-box">
                    <table width="100%">
                        <tr>
                            <td>TOTAL AKTIVA</td>
                            <td class="text-right">Rp {{ number_format($aktiva->sum('saldo'), 0, ',', '.') }}</td>
                        </tr>
                    </table>
                </div>
            </td>

            <!-- PASSIVA -->
            <td>
                <div class="judul-sisi">Passiva</div>
                
                <div style="font-weight: bold; margin-bottom: 5px; text-decoration: underline;">KEWAJIBAN (LIABILITAS)</div>
                <table class="sub-table" style="margin-bottom: 15px;">
                    @php $totalKewajiban = 0; @endphp
                    @foreach($passiva->where('tipe', 'Liabilitas') as $item)
                        @php $totalKewajiban += $item->saldo; @endphp
                        <tr>
                            <td>{{ $item->nama }}</td>
                            <td class="text-right">Rp {{ number_format($item->saldo, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    @if($passiva->where('tipe', 'Liabilitas')->count() == 0)
                        <tr>
                            <td style="font-style: italic; color: #888;">Tidak ada kewajiban</td>
                            <td class="text-right">Rp 0</td>
                        </tr>
                    @endif
                    <tr style="border-top: 1px solid #ccc; font-weight: bold;">
                        <td>Total Kewajiban</td>
                        <td class="text-right">Rp {{ number_format($totalKewajiban, 0, ',', '.') }}</td>
                    </tr>
                </table>

                <div style="font-weight: bold; margin-bottom: 5px; text-decoration: underline;">EKUITAS (MODAL)</div>
                <table class="sub-table">
                    @php $totalEkuitasTabel = 0; @endphp
                    @foreach($passiva->where('tipe', 'Ekuitas') as $item)
                        @php $totalEkuitasTabel += $item->saldo; @endphp
                        <tr>
                            <td>{{ $item->nama }}</td>
                            <td class="text-right">Rp {{ number_format($item->saldo, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td style="color: blue; font-style: italic;">Laba Tahun Berjalan</td>
                        <td class="text-right" style="color: blue;">Rp {{ number_format($labaBerjalan, 0, ',', '.') }}</td>
                    </tr>
                    @if(isset($totalPrive) && $totalPrive != 0)
                        <tr>
                            <td style="color: red; font-style: italic;">Prive (Pengurangan Modal)</td>
                            <td class="text-right" style="color: red;">(Rp {{ number_format($totalPrive, 0, ',', '.') }})</td>
                        </tr>
                    @endif
                    <tr style="border-top: 1px solid #ccc; font-weight: bold;">
                        <td>Total Ekuitas</td>
                        <td class="text-right">Rp {{ number_format($totalEkuitasTabel + $labaBerjalan - $totalPrive, 0, ',', '.') }}</td>
                    </tr>
                </table>

                <div class="total-box" style="margin-top: 15px;">
                    <table width="100%">
                        <tr>
                            <td>TOTAL PASSIVA</td>
                            <td class="text-right">Rp {{ number_format($totalKewajiban + $totalEkuitasTabel + $labaBerjalan - $totalPrive, 0, ',', '.') }}</td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

</body>
</html>
