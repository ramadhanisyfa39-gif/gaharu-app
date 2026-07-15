<x-app-layout>
    <style>
        .closing-container {
            max-width: 900px;
            margin: 40px auto;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        .closing-card {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
            margin-bottom: 30px;
        }

        .closing-header {
            text-align: center;
            margin-bottom: 35px;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 20px;
        }

        .closing-header h2 {
            margin: 0;
            color: #1e293b;
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -0.025em;
        }

        .closing-header p {
            color: #64748b;
            font-size: 14px;
            margin-top: 5px;
        }

        .info-box {
            background-color: #fffafb;
            border-left: 4px solid #e11d48;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 8px;
        }

        .info-box h4 {
            margin: 0 0 10px 0;
            color: #991b1b;
            font-size: 16px;
        }

        .info-box ul {
            margin: 0;
            padding-left: 20px;
            font-size: 14px;
            color: #475569;
            line-height: 1.6;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #334155;
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1.5px solid #cbd5e1;
            border-radius: 8px;
            font-size: 15px;
            color: #1e293b;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }

        .btn-submit {
            width: 100%;
            background: linear-gradient(to right, #e11d48, #be123c);
            color: white;
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .btn-submit:hover {
            opacity: 0.9;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            transform: translateY(-1px);
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-weight: 500;
        }

        .alert-success {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .alert-danger {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        /* Styling Tabel Histori */
        .history-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
            border: 1px solid #e5e7eb;
        }

        .history-title {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f1f5f9;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        .custom-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 14px;
        }

        .custom-table th {
            background-color: #f8fafc;
            color: #64748b;
            padding: 12px;
            font-weight: 600;
            border-bottom: 1px solid #e2e8f0;
        }

        .custom-table td {
            padding: 14px 12px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
        }

        .badge-closed {
            background-color: #dcfce7;
            color: #166534;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }
    </style>

    <div class="py-12">
        <div class="closing-container">

            @if(session('success'))
            <div class="alert alert-success">
                ✅ <strong>Berhasil!</strong> {{ session('success') }}
            </div>
            @endif

            @if($errors->any())
            <div class="alert alert-danger">
                ⚠️ <strong>Gagal!</strong> {{ $errors->first() }}
            </div>
            @endif

            @if(session('error'))
            <div class="alert alert-danger">
                ⚠️ <strong>Gagal!</strong> {{ session('error') }}
            </div>
            @endif

            <!-- CARD FORM CLOSING -->
            <div class="closing-card">
                <div class="closing-header">
                    <h2>Finalisasi Tutup Buku</h2>
                    <p>Modul Akuntansi CV Gaharu Agung Sejahtera</p>
                </div>

                <div class="info-box">
                    <h4>Peringatan Sistem:</h4>
                    <ul>
                        <li>Proses ini akan me-nol-kan saldo akun <strong>Pendapatan</strong> dan <strong>Beban</strong>.</li>
                        <li>Laba atau Rugi akan dipindahkan ke akun <strong>Laba Ditahan</strong>.</li>
                        <li>Pastikan semua jurnal penyesuaian telah di-input sebelum melakukan closing.</li>
                    </ul>
                </div>

                {{-- Action diarahkan ke rute POST (closing.store) --}}
                <form action="{{ route('closing.store') }}" method="POST" onsubmit="return confirm('Konfirmasi Akhir: Anda yakin ingin menutup buku periode ini?')">
                    @csrf

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label for="bulan">Pilih Bulan</label>
                            <select name="bulan" id="bulan" class="form-control" required>
                                @foreach(range(1, 12) as $m)
                                {{-- Value dikirim berupa integer bersih $m --}}
                                <option value="{{ $m }}" {{ date('m') == $m ? 'selected' : '' }}>
                                    {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="tahun">Tahun</label>
                            <input type="number" name="tahun" id="tahun" class="form-control" value="{{ date('Y') }}" required>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">
                        EKSEKUSI JURNAL PENUTUP
                    </button>
                </form>
            </div>

            <!-- CARD DAFTAR RIWAYAT HISTORI -->
            <div class="history-card">
                <div class="history-title">Riwayat Penutupan Periode</div>
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Tanggal Closing</th>
                                <th>No. Referensi</th>
                                <th>Deskripsi Jurnal</th>
                                <th>Status Buku</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($closings as $closing)
                                <tr>
                                    <td>{{ date('d-m-Y', strtotime($closing->tanggal)) }}</td>
                                    <td><code>{{ $closing->no_ref }}</code></td>
                                    <td>{{ $closing->deskripsi }}</td>
                                    <td><span class="badge-closed">Closed / Approved</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" style="text-align: center; color: #94a3b8; padding: 30px 0;">
                                        Belum ada riwayat penutupan buku akuntansi yang tercatat.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>