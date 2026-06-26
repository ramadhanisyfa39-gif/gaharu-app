<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Modul Jurnal Khusus Penjualan POS</h2>
    </x-slot>

    <div class="card shadow border-0 rounded-3 mb-5">
        <div class="card-header bg-warning text-dark py-3 d-flex align-items-center justify-content-between">
            <h5 class="mb-0 fw-bold"><i class="fas fa-clock me-2"></i>1. Antrean Invoice Penjualan POS (Belum Dijurnal)</h5>
            <span class="badge bg-dark text-white">{{ count($penjualanPosBelum ?? []) }} Transaksi</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary small text-uppercase fw-bold">
                        <tr>
                            <th class="py-3 ps-4" style="width: 15%">Tanggal Pembayaran</th>
                            <th class="py-3" style="width: 25%">No. Invoice (Kode)</th>
                            <th class="py-3" style="width: 25%">Gudang</th>
                            <th class="py-3 text-end" style="width: 20%">Jumlah Uang Masuk</th>
                            <th class="py-3 text-center" style="width: 15%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($penjualanPosBelum as $p)
                        <tr>
                            <td class="py-3 ps-4 text-secondary">
                                {{ \Carbon\Carbon::parse($p->tanggal)->format('d/m/Y') }}
                            </td>

                            <td>
                                <span class="badge bg-light text-dark border font-monospace px-2.5 py-1.5 fs-6">
                                    {{ $p->kode_transaksi }}
                                </span>
                            </td>

                            <td class="fw-semibold text-dark">
                                {{ $p->nama_outlet }}
                            </td>

                            <td class="text-end fw-bold text-dark">
                                Rp {{ number_format($p->total, 2, ',', '.') }}
                            </td>

                            <td class="text-center">
                                <a href="{{ route('laporan.jurnal-penjualanpos.create', $p->id) }}" class="btn btn-sm btn-primary fw-bold px-3 shadow-sm">
                                    <i class="fas fa-edit me-1"></i> Input Jurnal
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                Semua transaksi penjualan kasir retail POS sudah selesai dijurnal.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow border-0 rounded-3">
        <div class="card-header bg-success text-white py-3">
            <h5 class="mb-0 fw-bold"><i class="fas fa-folder-open me-2"></i>2. Riwayat Buku Jurnal Khusus Penjualan POS (Sudah Disimpan)</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary small text-uppercase fw-bold">
                        <tr>
                            <th class="py-3 ps-4" style="width: 15%">Tanggal Jurnal</th>
                            <th class="py-3" style="width: 20%">No. Jurnal (Ref)</th>
                            <th class="py-3" style="width: 35%">Keterangan / Deskripsi</th>
                            <th class="py-3 text-end" style="width: 12%">Total Debit</th>
                            <th class="py-3 text-end" style="width: 12%">Total Kredit</th>
                            <th class="py-3 text-center" style="width: 6%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($jurnalsSudah as $j)
                        <tr>
                            <td class="py-3 ps-4 text-secondary">{{ \Carbon\Carbon::parse($j->tanggal)->format('d/m/Y') }}</td>
                            <td class="font-monospace text-dark small fw-bold"><span class="badge bg-light text-dark border px-2 py-1">{{ $j->no_ref }}</span></td>
                            <td class="text-muted small">{{ Str::limit($j->deskripsi, 60, '...') }}</td>
                            <td class="text-end font-monospace text-success fw-semibold">Rp {{ number_format($j->total_debit, 0, ',', '.') }}</td>
                            <td class="text-end font-monospace text-danger fw-semibold">Rp {{ number_format($j->total_kredit, 0, ',', '.') }}</td>
                            <td class="text-center">
                                <a href="{{ route('laporan.jurnal-penjualanpos.show', $j->id) }}" class="btn btn-sm btn-outline-info fw-bold px-2.5 py-1">Detail</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">Belum ada riwayat transaksi penjualan POS di dalam buku jurnal khusus.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </div>
</x-app-layout>