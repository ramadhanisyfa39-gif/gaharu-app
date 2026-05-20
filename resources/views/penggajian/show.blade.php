<x-app-layout>
    <style>
        .slip-wrapper {
            background: #f4f4f5;
            padding: 40px 0;
            min-height: 100vh;
        }

        .slip-card {
            width: 100%;
            max-width: 700px;
            margin: auto;
            background: white;
            padding: 40px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            font-family: 'Courier New', Courier, monospace;
            /* Font khas slip gaji */
        }

        .header-center {
            text-align: center;
            border-bottom: 2px double #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .section-title {
            font-weight: bold;
            margin-top: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }

        .row-data {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
        }

        .total-box {
            border-top: 1px solid #333;
            margin-top: 10px;
            font-weight: bold;
        }

        .thp-highlight {
            margin-top: 30px;
            padding: 15px;
            background: #f8fafc;
            border: 2px solid #1e293b;
            display: flex;
            justify-content: space-between;
            font-size: 20px;
            font-weight: bold;
        }

        @media print {
            .no-print {
                display: none;
            }

            .slip-wrapper {
                padding: 0;
                background: white;
            }

            .slip-card {
                box-shadow: none;
                border: none;
                width: 100%;
                max-width: 100%;
            }
        }
    </style>

    <div class="slip-wrapper">
        <div class="no-print" style="max-width: 700px; margin: 0 auto 20px auto; display: flex; gap: 10px;">
            <a href="{{ route('penggajian.index') }}" style="background: #64748b; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none;">← Kembali</a>
            <button onclick="window.print()" style="background: #2563eb; color: white; padding: 10px 20px; border-radius: 5px; border: none; cursor: pointer;">Cetak Slip Gaji</button>
        </div>

        <div class="slip-card">
            <div class="header-center">
                <h1 style="margin: 0; font-size: 24px;">CV GAHARU AGUNG SEJAHTERA</h1>
                <p style="margin: 5px 0;">SLIP GAJI KARYAWAN</p>
                <p style="margin: 0; font-size: 14px;">Periode: {{ $payroll->periode }}</p>
            </div>

            <div style="display: flex; justify-content: space-between; margin-bottom: 30px;">
                <div>
                    <strong>Nama:</strong> {{ $payroll->karyawan->nama }}<br>
                    <strong>Jabatan:</strong> {{ $payroll->karyawan->jabatan ?? '-' }}
                </div>
                <div style="text-align: right;">
                    <strong>ID Slip:</strong> PAY-{{ $payroll->id }}-{{ date('Y') }}<br>
                    <strong>Tgl Cetak:</strong> {{ date('d/m/Y') }}
                </div>
            </div>

            <div class="section-title">A. PENERIMAAN TETAP</div>
            <div class="row-data"><span>Gaji Pokok</span> <span>Rp {{ number_format($payroll->gaji_pokok, 0, ',', '.') }}</span></div>
            <div class="row-data"><span>Tunjangan Transport</span> <span>Rp {{ number_format($payroll->tunjangan_transport, 0, ',', '.') }}</span></div>
            <div class="row-data"><span>Tunjangan Makan</span> <span>Rp {{ number_format($payroll->tunjangan_makan, 0, ',', '.') }}</span></div>
            <div class="row-data total-box"><span>Subtotal A</span> <span>Rp {{ number_format($total_tetap, 0, ',', '.') }}</span></div>

            <div class="section-title">B. PENERIMAAN TIDAK TETAP</div>
            <div class="row-data"><span>Lembur</span> <span>Rp {{ number_format($payroll->lembur, 0, ',', '.') }}</span></div>
            <div class="row-data"><span>Bonus Target</span> <span>Rp {{ number_format($payroll->bonus_target, 0, ',', '.') }}</span></div>
            <div class="row-data"><span>Bonus Tanggal Merah</span> <span>Rp {{ number_format($payroll->bonus_tanggal_merah, 0, ',', '.') }}</span></div>
            <div class="row-data"><span>Bonus Birthday Service</span> <span>Rp {{ number_format($payroll->bonus_birthday, 0, ',', '.') }}</span></div>
            <div class="row-data"><span>Bonus Lain-lain</span> <span>Rp {{ number_format($payroll->bonus_dll, 0, ',', '.') }}</span></div>
            <div class="row-data total-box"><span>Subtotal B</span> <span>Rp {{ number_format($total_tidak_tetap, 0, ',', '.') }}</span></div>

            <div class="section-title" style="color: #be123c;">C. POTONGAN</div>
            <div class="row-data"><span>Kerusakan Inventaris</span> <span>(Rp {{ number_format($payroll->potongan_inventaris, 0, ',', '.') }})</span></div>
            <div class="row-data"><span>Keterlambatan</span> <span>(Rp {{ number_format($payroll->potongan_terlambat, 0, ',', '.') }})</span></div>
            <div class="row-data total-box" style="color: #be123c;"><span>Subtotal C (Potongan)</span> <span>Rp {{ number_format($total_potongan, 0, ',', '.') }}</span></div>

            <div class="thp-highlight">
                <span>TOTAL DITERIMA (THP)</span>
                <span>Rp {{ number_format($total_gaji_bersih, 0, ',', '.') }}</span>
            </div>

            <div style="margin-top: 50px; display: flex; justify-content: space-between; text-align: center;">
                <div style="width: 200px;">
                    Penerima,<br><br><br><br>
                    ( ________________ )
                </div>
                <div style="width: 200px;">
                    Semarang, {{ date('d F Y') }}<br>
                    Bendahara,<br><br><br><br>
                    ( ________________ )
                </div>
            </div>
        </div>
    </div>
</x-app-layout>