<!DOCTYPE html>
<html>
<head>
    <title>Laporan Arus Kas</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; color: #333; margin: 20px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 0; font-size: 16px; color: #1e3a8a; }
        .header h3 { margin: 5px 0 0 0; font-size: 13px; color: #333; }
        .header p { margin: 5px 0 0 0; font-size: 11px; color: #666; }
        .info-table { width: 100%; margin-bottom: 15px; border-collapse: collapse; }
        .info-table td { padding: 4px 0; font-size: 10px; }
        .section-title { background-color: #f1f5f9; padding: 6px; font-weight: bold; font-size: 11px; text-transform: uppercase; margin-top: 15px; margin-bottom: 5px; border-left: 4px solid #1e3a8a; }
        .main-table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        .main-table td { padding: 6px; border-bottom: 1px solid #ddd; font-size: 9px; }
        .main-table tr.total-row { background-color: #fafafa; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
    </style>
</head>
<body>

    <div class="header">
        <h2>CV GAHARU AGUNG SEJAHTERA</h2>
        <h3>LAPORAN ARUS KAS</h3>
        <p>Periode: {{ date('F', mktime(0, 0, 0, $bulan, 1)) }} {{ $tahun }}</p>
    </div>

    <table class="info-table">
        <tr>
            <td width="15%"><strong>Tanggal Cetak</strong></td>
            <td width="3%">:</td>
            <td>{{ date('d-m-Y H:i') }}</td>
        </tr>
    </table>

    @php
        $totalOp = 0;
        $totalInv = 0;
        $totalPen = 0;
    @endphp

    <div class="section-title">Aktivitas Operasional</div>
    <table class="main-table">
        <tbody>
            @foreach($operasional as $item)
                @php $val = $item->kredit - $item->debit; @endphp
                <tr>
                    <td>{{ $item->coa->nama }}</td>
                    <td class="text-right" style="width: 200px;">Rp {{ number_format($val, 0, ',', '.') }}</td>
                </tr>
                @php $totalOp += $val; @endphp
            @endforeach
            <tr class="total-row">
                <td>Kas Bersih dari Aktivitas Operasional</td>
                <td class="text-right">Rp {{ number_format($totalOp, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">Aktivitas Investasi</div>
    <table class="main-table">
        <tbody>
            @foreach($investasi as $item)
                @php $val = $item->kredit - $item->debit; @endphp
                <tr>
                    <td>{{ $item->coa->nama }}</td>
                    <td class="text-right" style="width: 200px;">Rp {{ number_format($val, 0, ',', '.') }}</td>
                </tr>
                @php $totalInv += $val; @endphp
            @endforeach
            <tr class="total-row">
                <td>Kas Bersih dari Aktivitas Investasi</td>
                <td class="text-right">Rp {{ number_format($totalInv, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">Aktivitas Pendanaan</div>
    <table class="main-table">
        <tbody>
            @foreach($pendanaan as $item)
                @php $val = $item->kredit - $item->debit; @endphp
                <tr>
                    <td>{{ $item->coa->nama }}</td>
                    <td class="text-right" style="width: 200px;">Rp {{ number_format($val, 0, ',', '.') }}</td>
                </tr>
                @php $totalPen += $val; @endphp
            @endforeach
            <tr class="total-row">
                <td>Kas Bersih dari Aktivitas Pendanaan</td>
                <td class="text-right">Rp {{ number_format($totalPen, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    @php $netKas = $totalOp + $totalInv + $totalPen; @endphp
    <table width="100%" style="margin-top: 20px; border-collapse: collapse;">
        <tr>
            <td style="padding: 10px; background-color: #1e3a8a; color: white; font-weight: bold; font-size: 11px;">
                KENAIKAN (PENURUNAN) KAS BERSIH
            </td>
            <td class="text-right" style="padding: 10px; background-color: #1e3a8a; color: white; font-weight: bold; font-size: 12px; width: 200px;">
                Rp {{ number_format($netKas, 0, ',', '.') }}
            </td>
        </tr>
    </table>

</body>
</html>
