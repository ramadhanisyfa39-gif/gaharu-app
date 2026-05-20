<x-app-layout>
    <style>
        .container-laporan {
            width: 100%;
            max-width: 1100px;
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
        }

        .t-account-table {
            width: 100%;
            border-collapse: collapse;
            border: 2px solid #000;
            table-layout: fixed;
        }

        .t-account-table td {
            vertical-align: top;
            padding: 15px;
            border: 1px solid #000;
        }

        .judul-sisi {
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 15px;
            background: #f4f4f4;
        }

        .text-right {
            text-align: right;
        }

        .total-box {
            margin-top: 30px;
            padding: 10px;
            border-top: 4px double #000;
            font-weight: bold;
            background: #eee;
            display: flex;
            justify-content: space-between;
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
                <h3 style="margin-bottom: 15px; font-weight: bold;">Filter Laporan Neraca</h3>
                <form action="{{ route('laporan.neraca.index') }}" method="GET" class="filter-group">
                    <div>
                        <label>Bulan</label><br>
                        <select name="bulan" style="padding: 8px; border-radius: 4px; border: 1px solid #ccc;">
                            @foreach(range(1, 12) as $m)
                            <option value="{{ sprintf('%02d', $m) }}" {{ $bulan == $m ? 'selected' : '' }}>
                                {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>Tahun</label><br>
                        <input type="number" name="tahun" value="{{ $tahun }}" style="padding: 8px; border-radius: 4px; border: 1px solid #ccc;">
                    </div>
                    <button type="submit" style="background: #1a56db; color: white; padding: 9px 20px; border: none; border-radius: 4px; cursor: pointer;">
                        Tampilkan Neraca
                    </button>
                    <button type="button" onclick="window.print()" style="background: #333; color: white; padding: 9px 20px; border: none; border-radius: 4px; cursor: pointer;">
                        Cetak
                    </button>
                </form>
            </div>

            <div class="card">
                <div style="text-align: center; margin-bottom: 20px;">
                    <h1 style="margin:0;">CV GAHARU AGUNG SEJAHTERA</h1>
                    <h2 style="margin:5px 0;">LAPORAN NERACA</h2>
                    <p>Periode: {{ date('F', mktime(0, 0, 0, $bulan, 1)) }} {{ $tahun }}</p>
                </div>

                <table class="t-account-table">
                    <tr>
                        <td>
                            <div class="judul-sisi">Aktiva</div>
                            <table width="100%">
                                @foreach($aktiva as $item)
                                <tr>
                                    <td style="border:none; padding: 5px 0;">{{ $item->nama }}</td>
                                    <td style="border:none; text-align: right;">Rp {{ number_format($item->saldo, 0, ',', '.') }}</td>
                                </tr>
                                @endforeach
                            </table>
                            <div class="total-box">
                                <span>TOTAL AKTIVA</span>
                                <span>Rp {{ number_format($aktiva->sum('saldo'), 0, ',', '.') }}</span>
                            </div>
                        </td>

                        <td>
                            <div class="judul-sisi">Passiva</div>
                            <table width="100%">
                                @foreach($passiva as $item)
                                <tr>
                                    <td style="border:none; padding: 5px 0;">{{ $item->nama }}</td>
                                    <td style="border:none; text-align: right;">Rp {{ number_format($item->saldo, 0, ',', '.') }}</td>
                                </tr>
                                @endforeach
                                <tr>
                                    <td style="border:none; padding: 5px 0; font-style: italic; color: blue;">Laba Tahun Berjalan</td>
                                    <td style="border:none; text-align: right; color: blue;">Rp {{ number_format($labaBerjalan, 0, ',', '.') }}</td>
                                </tr>
                            </table>
                            <div class="total-box">
                                <span>TOTAL PASSIVA</span>
                                <span>Rp {{ number_format($passiva->sum('saldo') + $labaBerjalan, 0, ',', '.') }}</span>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>