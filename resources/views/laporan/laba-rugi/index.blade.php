<x-app-layout>
    <style>
        .container-laporan {
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
            font-family: Arial, sans-serif;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .filter-group {
            display: flex;
            gap: 15px;
            align-items: flex-end;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .laporan-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .kategori-header {
            background: #f4f4f4;
            padding: 12px;
            font-weight: bold;
            border-bottom: 2px solid #333;
            text-align: left;
        }
        .text-right {
            text-align: right;
            font-family: 'Courier New', monospace;
            font-weight: bold;
        }
        .row-total {
            background: #eee;
            font-weight: bold;
        }
        .footer-laba {
            margin-top: 30px;
            padding: 20px;
            color: white;
            display: flex;
            justify-content: space-between;
            border-radius: 4px;
        }
        @media print {
            .no-print {
                display: none;
            }
            .card {
                box-shadow: none;
                border: none;
            }
        }
    </style>

    <div class="py-12">
        <div class="container-laporan">

            <div class="card no-print">
                <h3 style="margin-bottom: 15px; font-weight: bold;">Filter Laporan Laba Rugi</h3>
                <form action="{{ route('laporan.laba-rugi.index') }}" method="GET" class="filter-group">
                    <div>
                        <label>Bulan</label><br>
                        <select name="bulan" style="padding: 8px; border-radius: 4px; border: 1px solid #ccc; width: 150px;">
                            @foreach(range(1, 12) as $m)
                            <option value="{{ sprintf('%02d', $m) }}" {{ $bulan == $m ? 'selected' : '' }}>
                                {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>Tahun</label><br>
                        <input type="number" name="tahun" value="{{ $tahun }}" style="padding: 8px; border-radius: 4px; border: 1px solid #ccc; width: 100px;">
                    </div>
                    <button type="submit" style="background: #1a56db; color: white; padding: 9px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
                        Tampilkan
                    </button>
                    <a href="{{ route('laporan.laba-rugi.index', array_merge(request()->all(), ['format' => 'excel'])) }}" class="btn btn-success text-white" style="padding: 9px 20px; border-radius: 4px; font-weight: bold; text-decoration: none; background-color: #198754; border: none; cursor: pointer;">
                        📊 Export Excel
                    </a>
                    <a href="{{ route('laporan.laba-rugi.index', array_merge(request()->all(), ['format' => 'pdf'])) }}" class="btn btn-danger text-white" style="padding: 9px 20px; border-radius: 4px; font-weight: bold; text-decoration: none; background-color: #dc3545; border: none; cursor: pointer;">
                        📕 Export PDF
                    </a>
                    <button type="button" onclick="window.print()" style="background: #333; color: white; padding: 9px 20px; border: none; border-radius: 4px; cursor: pointer;">
                        Cetak
                    </button>
                </form>
            </div>

            <div class="card">
                <div style="text-align: center; margin-bottom: 30px; border-bottom: 2px solid #eee; padding-bottom: 20px;">
                    <h1 style="margin:0; font-size: 22px;">CV GAHARU AGUNG SEJAHTERA</h1>
                    <h2 style="margin:5px 0; font-size: 18px; color: #555;">LAPORAN LABA RUGI</h2>
                    <p style="color: #888;">Periode: {{ date('F', mktime(0, 0, 0, $bulan, 1)) }} {{ $tahun }}</p>
                </div>

                <table class="laporan-table">
                    <tr>
                        <th colspan="2" class="kategori-header">PENDAPATAN</th>
                    </tr>
                    @foreach($detailsPendapatan as $item)
                    <tr>
                        <td style="padding: 10px; border-bottom: 1px solid #eee;">{{ $item->kode }} - {{ $item->nama }}</td>
                        <td class="text-right" style="border-bottom: 1px solid #eee;">Rp {{ number_format($item->saldo, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                    <tr class="row-total">
                        <td style="padding: 12px;">TOTAL PENDAPATAN</td>
                        <td class="text-right">Rp {{ number_format($totalPendapatan, 0, ',', '.') }}</td>
                    </tr>

                    <tr>
                        <td colspan="2" style="padding: 15px;"></td>
                    </tr>

                    <tr>
                        <th colspan="2" class="kategori-header" style="color: #c53030;">BEBAN OPERASIONAL</th>
                    </tr>
                    @foreach($detailsBeban as $item)
                    <tr>
                        <td style="padding: 10px; border-bottom: 1px solid #eee;">{{ $item->kode }} - {{ $item->nama }}</td>
                        <td class="text-right" style="border-bottom: 1px solid #eee;">Rp {{ number_format($item->saldo, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                    <tr class="row-total">
                        <td style="padding: 12px; color: #c53030;">TOTAL BEBAN</td>
                        <td class="text-right" style="color: #c53030;">(Rp {{ number_format($totalBeban, 0, ',', '.') }})</td>
                    </tr>
                </table>

                @php $labaBersih = $totalPendapatan - $totalBeban; @endphp
                <div class="footer-laba" style="background: {{ $labaBersih >= 0 ? '#1a202c' : '#c53030' }};">
                    <span style="font-weight: bold; font-size: 18px;">LABA (RUGI) BERSIH</span>
                    <span style="font-weight: bold; font-size: 20px;">Rp {{ number_format($labaBersih, 0, ',', '.') }}</span>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>