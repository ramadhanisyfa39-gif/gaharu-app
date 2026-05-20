<x-app-layout>
    <style>
        .container-laporan {
            width: 100%;
            max-width: 1000px;
            margin: 0 auto;
            font-family: Arial, sans-serif;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .filter-group {
            display: flex;
            gap: 15px;
            align-items: flex-end;
            margin-bottom: 10px;
        }

        .section-title {
            background: #f8fafc;
            padding: 12px;
            font-weight: bold;
            border-left: 5px solid #1e293b;
            margin: 30px 0 10px 0;
            text-transform: uppercase;
            font-size: 14px;
            color: #1e293b;
        }

        .table-arus {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }

        .table-arus td {
            padding: 10px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 14px;
        }

        .text-right {
            text-align: right;
        }

        .font-mono {
            font-family: 'Courier New', monospace;
            font-weight: bold;
        }

        .row-subtotal {
            background: #f1f5f9;
            font-weight: bold;
        }

        .net-box {
            margin-top: 30px;
            padding: 20px;
            color: white;
            background: #0f172a;
            display: flex;
            justify-content: space-between;
            border-radius: 6px;
            font-size: 18px;
        }

        @media print {
            .no-print {
                display: none;
            }

            .card {
                box-shadow: none;
                border: 1px solid #eee;
            }
        }
    </style>

    <div class="py-12">
        <div class="container-laporan">

            <div class="card no-print">
                <h3 style="margin-bottom: 15px; font-weight: bold;">Filter Arus Kas</h3>
                <form action="{{ route('laporan.arus-kas.index') }}" method="GET" class="filter-group">
                    <div>
                        <label style="font-size: 12px; color: #666;">Bulan</label><br>
                        <select name="bulan" style="padding: 8px; border-radius: 4px; border: 1px solid #cbd5e1; width: 150px;">
                            @foreach(range(1, 12) as $m)
                            <option value="{{ sprintf('%02d', $m) }}" {{ $bulan == $m ? 'selected' : '' }}>
                                {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="font-size: 12px; color: #666;">Tahun</label><br>
                        <input type="number" name="tahun" value="{{ $tahun }}" style="padding: 8px; border-radius: 4px; border: 1px solid #cbd5e1; width: 100px;">
                    </div>
                    <button type="submit" style="background: #2563eb; color: white; padding: 9px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
                        Tampilkan
                    </button>
                    <button type="button" onclick="window.print()" style="background: #475569; color: white; padding: 9px 20px; border: none; border-radius: 4px; cursor: pointer;">
                        Cetak
                    </button>
                </form>
            </div>

            <div class="card">
                <div style="text-align: center; margin-bottom: 30px;">
                    <h1 style="margin:0; font-size: 22px;">CV GAHARU AGUNG SEJAHTERA</h1>
                    <h2 style="margin:5px 0; font-size: 18px; color: #475569;">LAPORAN ARUS KAS</h2>
                    <p style="color: #94a3b8;">Periode: {{ date('F', mktime(0, 0, 0, $bulan, 1)) }} {{ $tahun }}</p>
                </div>

                @php
                $totalOp = 0;
                $totalInv = 0;
                $totalPen = 0;
                @endphp

                <div class="section-title">Aktivitas Operasional</div>
                <table class="table-arus">
                    @foreach($operasional as $item)
                    @php $val = $item->kredit - $item->debit; @endphp
                    <tr>
                        <td>{{ $item->coa->nama }}</td>
                        <td class="text-right font-mono">Rp {{ number_format($val, 0, ',', '.') }}</td>
                    </tr>
                    @php $totalOp += $val; @endphp
                    @endforeach
                    <tr class="row-subtotal">
                        <td style="padding: 12px;">Kas Bersih dari Aktivitas Operasional</td>
                        <td class="text-right font-mono">Rp {{ number_format($totalOp, 0, ',', '.') }}</td>
                    </tr>
                </table>

                <div class="section-title">Aktivitas Investasi</div>
                <table class="table-arus">
                    @foreach($investasi as $item)
                    @php $val = $item->kredit - $item->debit; @endphp
                    <tr>
                        <td>{{ $item->coa->nama }}</td>
                        <td class="text-right font-mono">Rp {{ number_format($val, 0, ',', '.') }}</td>
                    </tr>
                    @php $totalInv += $val; @endphp
                    @endforeach
                    <tr class="row-subtotal">
                        <td style="padding: 12px;">Kas Bersih dari Aktivitas Investasi</td>
                        <td class="text-right font-mono">Rp {{ number_format($totalInv, 0, ',', '.') }}</td>
                    </tr>
                </table>

                <div class="section-title">Aktivitas Pendanaan</div>
                <table class="table-arus">
                    @foreach($pendanaan as $item)
                    @php $val = $item->kredit - $item->debit; @endphp
                    <tr>
                        <td>{{ $item->coa->nama }}</td>
                        <td class="text-right font-mono">Rp {{ number_format($val, 0, ',', '.') }}</td>
                    </tr>
                    @php $totalPen += $val; @endphp
                    @endforeach
                    <tr class="row-subtotal">
                        <td style="padding: 12px;">Kas Bersih dari Aktivitas Pendanaan</td>
                        <td class="text-right font-mono">Rp {{ number_format($totalPen, 0, ',', '.') }}</td>
                    </tr>
                </table>

                <div class="net-box">
                    <span style="font-weight: bold;">KENAIKAN (PENURUNAN) KAS BERSIH</span>
                    <span class="font-mono">Rp {{ number_format($totalOp + $totalInv + $totalPen, 0, ',', '.') }}</span>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>