<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Modul Jurnal Khusus Pembelian
        </h2>
    </x-slot>

    <div class="container py-4">

        @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm mb-4">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        </div>
        @endif

        <div class="card shadow border-0 rounded-3 mb-5">
            <div class="card-header bg-warning text-dark py-3 d-flex align-items-center justify-content-between">
                <h5 class="mb-0 fw-bold"><i class="fas fa-clock me-2"></i>1. Antrean Invoice Pembelian (Belum Dijurnal)</h5>
                <span class="badge bg-dark text-white">{{ count($pembeliansBelum) }} Invoice</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-secondary small text-uppercase fw-bold">
                            <tr>
                                <th class="py-3 ps-4">Tanggal Nota</th>
                                <th class="py-3">No. Invoice (Kode)</th>
                                <th class="py-3">Nama Supplier</th>
                                <th class="py-3 text-end">Total Belanja</th>
                                <th class="py-3 text-center" style="width: 180px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pembeliansBelum as $p)
                            <tr>
                                <td class="py-3 ps-4 text-secondary">{{ \Carbon\Carbon::parse($p->tanggal)->format('d/m/Y') }}</td>
                                <td><span class="badge bg-light text-dark border font-monospace px-2.5 py-1.5 fs-6">{{ $p->kode_pembelian }}</span></td>
                                <td class="fw-semibold text-dark">{{ $p->supplier->nama ?? 'Supplier Tidak Terdaftar' }}</td>
                                <td class="text-end fw-bold text-dark">Rp {{ number_format($p->total, 2, ',', '.') }}</td>
                                <td class="text-center">
                                    <a href="{{ route('jurnal-pembelian.create', $p->id) }}" class="btn btn-sm btn-primary fw-bold px-3 shadow-sm">
                                        <i class="fas fa-edit me-1"></i> Input Jurnal
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="fas fa-check-double text-success me-2"></i>Semua transaksi pembelian dari gudang sudah selesai dijurnal.
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
                <h5 class="mb-0 fw-bold"><i class="fas fa-folder-open me-2"></i>2. Riwayat Buku Jurnal Khusus Pembelian (Sudah Disimpan)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0">
                        <thead class="table-light text-secondary small text-uppercase fw-bold">
                            <tr>
                                <th class="py-3 ps-4" style="width: 12%">Tanggal Jurnal</th>
                                <th class="py-3" style="width: 18%">No. Jurnal (Ref)</th>
                                <th class="py-3" style="width: 25%">Keterangan / Deskripsi</th>
                                <th class="py-3" style="width: 25%">Akun Terikat (COA)</th>
                                <th class="py-3 text-end" style="width: 10%">Debit</th>
                                <th class="py-3 text-end" style="width: 10%">Kredit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($jurnalsSudah as $j)
                            @foreach($j->details as $index => $detail)
                            <tr>
                                @if($index === 0)
                                <td rowspan="{{ count($j->details) }}" class="py-3 ps-4 fw-medium text-secondary align-top">
                                    {{ \Carbon\Carbon::parse($j->tanggal)->format('d/m/Y') }}
                                </td>
                                <td rowspan="{{ count($j->details) }}" class="font-monospace text-dark small align-top fw-bold">
                                    {{ $j->no_ref }}
                                </td>
                                <td rowspan="{{ count($j->details) }}" class="text-muted small align-top">
                                    {{ $j->deskripsi }}
                                </td>
                                @endif

                                <td class="{{ $detail->kredit > 0 ? 'ps-5 text-secondary' : 'fw-medium text-dark' }}">
                                    {{ $detail->coa->kode }} - {{ $detail->coa->nama }}
                                </td>
                                <td class="text-end font-monospace">
                                    {{ $detail->debit > 0 ? 'Rp ' . number_format($detail->debit, 0, ',', '.') : '-' }}
                                </td>
                                <td class="text-end font-monospace">
                                    {{ $detail->kredit > 0 ? 'Rp ' . number_format($detail->kredit, 0, ',', '.') : '-' }}
                                </td>
                            </tr>
                            @endforeach
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    Belum ada riwayat transaksi pembelian yang tersimpan di dalam buku jurnal khusus.
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