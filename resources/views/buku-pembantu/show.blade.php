<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Detail Buku Besar Pembantu - {{ $entity->nama }}
        </h2>
    </x-slot>

    <style>
        :root {
            --brand: #e07a5f;
            --brand-soft: #fdf2f0;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #ffffff;
            color: #334155;
            border: 1px solid #cbd5e1;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            margin-bottom: 20px;
        }

        .btn-back:hover { background: #f8fafc; }

        .breadcrumb { font-size: 13px; color: #64748b; margin-bottom: 20px; }

        .ledger-container {
            display: grid;
            grid-template-columns: 2.5fr 1fr;
            gap: 20px;
        }

        .ledger-table-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .ledger-header {
            background: var(--brand);
            color: white;
            padding: 18px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .ledger-header h3 { font-size: 18px; font-weight: 700; margin: 0; }

        table.ledger-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        table.ledger-table th, table.ledger-table td { padding: 12px 18px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        table.ledger-table th { background: #f8fafc; font-weight: 600; color: #64748b; }
        .text-right { text-align: right; }
        .font-mono { font-family: monospace; }

        .ledger-footer {
            padding: 16px 24px;
            background: #f8fafc;
            display: flex;
            justify-content: space-between;
            font-weight: 700;
            border-top: 1px solid #e2e8f0;
            font-size: 15px;
        }

        .info-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            height: fit-content;
        }

        .status-icon-check {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 16px auto;
            font-size: 24px;
        }

        .icon-lunas { background: #dcfce7; color: #16a34a; }
        .icon-pending { background: #fee2e2; color: #dc2626; }
    </style>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <a href="{{ route('buku-pembantu.index', ['jenis' => $jenis]) }}" class="btn-back">
                ← Kembali ke Daftar Akun
            </a>

            <div class="breadcrumb">
                Home / 
                @if(in_array($jenis, ['utang', 'um-pembelian'])) Pembelian @else Penjualan @endif / 
                Buku Pembantu / 
                {{ $entity->nama }}
            </div>

            <div class="ledger-container">
                <div class="ledger-table-card">
                    <div class="ledger-header">
                        <div>
                            <div style="font-size: 11px; opacity: 0.85; text-transform: uppercase;">Buku Besar Pembantu</div>
                            <h3>{{ $entity->nama }}</h3>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 11px; opacity: 0.85;">Kode Entitas</div>
                            <strong>NO. {{ $entity->id }}</strong>
                        </div>
                    </div>

                    <div style="overflow-x: auto;">
                        <table class="ledger-table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Keterangan</th>
                                    <th>No. Ref</th>
                                    <th class="text-right">Debet</th>
                                    <th class="text-right">Kredit</th>
                                    <th class="text-right">Saldo</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($mutasi as $row)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($row->tanggal)->format('d/m/Y') }}</td>
                                        <td>{{ $row->keterangan }}</td>
                                        <td><span class="font-mono text-xs text-gray-600">{{ $row->ref }}</span></td>
                                        <td class="text-right font-mono">{{ $row->debit > 0 ? 'Rp ' . number_format($row->debit, 0, ',', '.') : '-' }}</td>
                                        <td class="text-right font-mono">{{ $row->kredit > 0 ? 'Rp ' . number_format($row->kredit, 0, ',', '.') : '-' }}</td>
                                        <td class="text-right font-mono font-semibold">Rp {{ number_format($row->saldo_akumulasi, 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 32px; color: #64748b;">
                                            Belum ada riwayat transaksi jurnal untuk entitas ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="ledger-footer">
                        <span>Saldo Akhir (Sisa Kewajiban/Hak):</span>
                        <span style="color: var(--brand);">Rp {{ number_format($saldoAkhir, 0, ',', '.') }}</span>
                    </div>
                </div>

                <div class="info-card">
                    <h4 style="text-align: left; color: #334155; font-size: 14px; font-weight: 700;">Status Transaksi</h4>
                    
                    @if($saldoAkhir <= 0)
                        <div class="status-icon-check icon-lunas">✓</div>
                        <strong style="color: #16a34a; font-size: 15px;">Semua Transaksi Lunas</strong>
                        <p style="font-size: 12px; color: #64748b; margin-top: 6px;">
                            Tidak ada kewajiban atau hak gantung yang belum diselesaikan.
                        </p>
                    @else
                        <div class="status-icon-check icon-pending">!</div>
                        <strong style="color: #dc2626; font-size: 15px;">Belum Lunas / Aktif</strong>
                        <p style="font-size: 12px; color: #64748b; margin-top: 6px;">
                            Masih terdapat saldo berjalan sebesar <strong>Rp {{ number_format($saldoAkhir, 0, ',', '.') }}</strong>.
                        </p>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>