<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Arus Kas</title>
    <style>
        body { 
            font-family: sans-serif; 
            font-size: 10px; 
            color: #333; 
            margin: 15px; 
        }
        .header { 
            text-align: center; 
            margin-bottom: 15px; 
        }
        .header h2 { 
            margin: 0; 
            font-size: 15px; 
            color: #1e3a8a; 
            text-transform: uppercase; 
        }
        .header h3 { 
            margin: 4px 0 0 0; 
            font-size: 12px; 
            color: #333; 
        }
        .header p { 
            margin: 4px 0 0 0; 
            font-size: 10px; 
            color: #666; 
        }
        .info-table { 
            width: 100%; 
            margin-bottom: 15px; 
            border-collapse: collapse; 
        }
        .info-table td { 
            padding: 3px 0; 
            font-size: 9px; 
        }
        .section-title { 
            background-color: #f1f5f9; 
            padding: 5px 8px; 
            font-weight: bold; 
            font-size: 10px; 
            text-transform: uppercase; 
            margin-top: 12px; 
            margin-bottom: 4px; 
            border-left: 4px solid #1e3a8a; 
        }
        .main-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 4px; 
        }
        .main-table td { 
            padding: 5px 8px; 
            border-bottom: 1px solid #e2e8f0; 
            font-size: 9px; 
        }
        .main-table tr.total-row { 
            background-color: #f8fafc; 
            font-weight: bold; 
        }
        .summary-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 15px; 
        }
        .summary-table td { 
            padding: 7px 8px; 
            font-size: 10px; 
            border-bottom: 1px solid #cbd5e1; 
        }
        .text-right { 
            text-align: right; 
        }
        .fw-bold { 
            font-weight: bold; 
        }
        .pl-4 { 
            padding-left: 18px; 
        }
        .empty-text {
            color: #94a3b8;
            font-style: italic;
        }
    </style>
</head>
<body>

    <div class="header">
        <h2>CV GAHARU AGUNG SEJAHTERA</h2>
        <h3>LAPORAN ARUS KAS (METODE LANGSUNG)</h3>
        <p>Untuk Periode yang Berakhir pada 31 {{ $namaBulan }} {{ $tahun }}</p>
    </div>

    <table class="info-table">
        <tr>
            <td width="15%"><strong>Mata Uang</strong></td>
            <td width="2%">:</td>
            <td width="30%">Rupiah (Rp)</td>
            <td width="15%"><strong>Tanggal Cetak</strong></td>
            <td width="2%">:</td>
            <td>{{ date('d-m-Y H:i') }}</td>
        </tr>
    </table>

    <!-- 1. ARUS KAS DARI AKTIVITAS OPERASIONAL -->
    <div class="section-title">ARUS KAS DARI AKTIVITAS OPERASIONAL</div>
    <table class="main-table">
        <tbody>
            <!-- Penerimaan Kas dari Pelanggan -->
            <tr class="fw-bold">
                <td colspan="2">Penerimaan Kas dari Pelanggan:</td>
            </tr>
            @forelse($penerimaanPelanggan as $item)
                <tr>
                    <td class="pl-4">{{ $item['keterangan'] }}</td>
                    <td class="text-right" style="width: 180px;">
                        {{ number_format($item['nominal'], 2, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="2" class="pl-4 empty-text">- Tidak ada penerimaan kas dari pelanggan -</td>
                </tr>
            @endforelse

            <!-- Pengeluaran Kas untuk Operasional -->
            <tr class="fw-bold">
                <td colspan="2" style="padding-top: 8px;">Pengeluaran Kas untuk Operasional:</td>
            </tr>
            @forelse($pengeluaranBahanBaku as $item)
                <tr>
                    <td class="pl-4">{{ $item['keterangan'] }}</td>
                    <td class="text-right" style="width: 180px;">
                        ({{ number_format(abs($item['nominal']), 2, ',', '.') }})
                    </td>
                </tr>
            @empty
            @endforelse

            @forelse($pengeluaranBebanOp as $item)
                <tr>
                    <td class="pl-4">{{ $item['keterangan'] }}</td>
                    <td class="text-right" style="width: 180px;">
                        ({{ number_format(abs($item['nominal']), 2, ',', '.') }})
                    </td>
                </tr>
            @empty
            @endforelse

            @if($pengeluaranBahanBaku->isEmpty() && $pengeluaranBebanOp->isEmpty())
                <tr>
                    <td colspan="2" class="pl-4 empty-text">- Tidak ada pengeluaran kas operasional -</td>
                </tr>
            @endif

            <tr class="total-row">
                <td style="padding-top: 8px;">Arus Kas Bersih Dari Aktivitas Operasional</td>
                <td class="text-right" style="padding-top: 8px;">
                    {{ $kasBersihOperasional < 0 ? '(' . number_format(abs($kasBersihOperasional), 2, ',', '.') . ')' : number_format($kasBersihOperasional, 2, ',', '.') }}
                </td>
            </tr>
        </tbody>
    </table>

    <!-- 2. ARUS KAS DARI AKTIVITAS INVESTASI -->
    <div class="section-title">ARUS KAS DARI AKTIVITAS INVESTASI</div>
    <table class="main-table">
        <tbody>
            @forelse($investasi as $item)
                <tr>
                    <td>{{ $item['keterangan'] }}</td>
                    <td class="text-right" style="width: 180px;">
                        {{ $item['nominal'] < 0 ? '(' . number_format(abs($item['nominal']), 2, ',', '.') . ')' : number_format($item['nominal'], 2, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="empty-text">Tidak ada transaksi kas dari aktivitas investasi selama periode ini</td>
                    <td class="text-right">0,00</td>
                </tr>
            @endforelse
            <tr class="total-row">
                <td>Arus Kas Bersih Dari Aktivitas Investasi</td>
                <td class="text-right">
                    {{ $kasBersihInvestasi < 0 ? '(' . number_format(abs($kasBersihInvestasi), 2, ',', '.') . ')' : number_format($kasBersihInvestasi, 2, ',', '.') }}
                </td>
            </tr>
        </tbody>
    </table>

    <!-- 3. ARUS KAS DARI AKTIVITAS PENDANAAN -->
    <div class="section-title">ARUS KAS DARI AKTIVITAS PENDANAAN</div>
    <table class="main-table">
        <tbody>
            @forelse($pendanaan as $item)
                <tr>
                    <td>{{ $item['keterangan'] }}</td>
                    <td class="text-right" style="width: 180px;">
                        {{ $item['nominal'] < 0 ? '(' . number_format(abs($item['nominal']), 2, ',', '.') . ')' : number_format($item['nominal'], 2, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="empty-text">Tidak ada transaksi kas dari aktivitas pendanaan selama periode ini</td>
                    <td class="text-right">0,00</td>
                </tr>
            @endforelse
            <tr class="total-row">
                <td>Arus Kas Bersih Dari Aktivitas Pendanaan</td>
                <td class="text-right">
                    {{ $kasBersihPendanaan < 0 ? '(' . number_format(abs($kasBersihPendanaan), 2, ',', '.') . ')' : number_format($kasBersihPendanaan, 2, ',', '.') }}
                </td>
            </tr>
        </tbody>
    </table>

    <!-- REKONSILIASI KAS DAN BANK -->
    <table class="summary-table">
        <tr style="background-color: #1e3a8a; color: white; font-weight: bold;">
            <td>KENAIKAN (PENURUNAN) BERSIH KAS DAN BANK</td>
            <td class="text-right" style="width: 180px;">
                {{ $kenaikanPenurunanKas < 0 ? '(' . number_format(abs($kenaikanPenurunanKas), 2, ',', '.') . ')' : number_format($kenaikanPenurunanKas, 2, ',', '.') }}
            </td>
        </tr>
        <tr>
            <td>KAS DAN BANK AWAL PERIODE (01/{{ $bulan }}/{{ $tahun }})</td>
            <td class="text-right fw-bold">{{ number_format($saldoAwalKas, 2, ',', '.') }}</td>
        </tr>
        <tr style="background-color: #f1f5f9; font-weight: bold;">
            <td>KAS DAN BANK AKHIR PERIODE (31/{{ $bulan }}/{{ $tahun }})</td>
            <td class="text-right">{{ number_format($saldoAkhirKas, 2, ',', '.') }}</td>
        </tr>
    </table>

</body>
</html>