<x-app-layout>
    <x-slot name="header">Laporan Pengeluaran Bahan Baku</x-slot>

    <div class="container-fluid">

        {{-- ── FILTER ── --}}
        <div class="card mb-4 shadow-sm">
            <div class="card-body">
                <form method="GET" action="{{ route('laporan.pengeluaran-bahan-baku') }}" class="row g-3 align-items-end">
                    <div class="col-12 col-md-2">
                        <label class="form-label fw-semibold text-muted" style="font-size:12px;">DARI TANGGAL</label>
                        <input type="date" name="dari" class="form-control" value="{{ request('dari') }}">
                    </div>
                    <div class="col-12 col-md-2">
                        <label class="form-label fw-semibold text-muted" style="font-size:12px;">SAMPAI TANGGAL</label>
                        <input type="date" name="sampai" class="form-control" value="{{ request('sampai') }}">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label fw-semibold text-muted" style="font-size:12px;">GUDANG</label>
                        <select name="gudang_id" class="form-select">
                            <option value="">Semua Gudang</option>
                            @foreach($gudangs as $g)
                                <option value="{{ $g->id }}" {{ request('gudang_id') == $g->id ? 'selected' : '' }}>
                                    {{ $g->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <label class="form-label fw-semibold text-muted" style="font-size:12px;">STATUS</label>
                        <select name="status" class="form-select">
                            <option value="">Semua Status</option>
                            <option value="draft"    {{ request('status') === 'draft'    ? 'selected' : '' }}>Draft</option>
                            <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-3 d-flex gap-2 align-items-end">
                        <button type="submit" class="btn text-white px-3" style="background-color: #d88656; border: none;">
                            <i class="bi bi-search me-1"></i> Tampilkan
                        </button>
                        <a href="{{ route('laporan.pengeluaran-bahan-baku', array_merge(request()->all(), ['format'=>'excel'])) }}"
                           class="btn text-white" style="background-color: #606060; border: none;" title="Export Excel">
                            <i class="bi bi-file-earmark-spreadsheet"></i>
                        </a>
                        <a href="{{ route('laporan.pengeluaran-bahan-baku', array_merge(request()->all(), ['format'=>'pdf'])) }}"
                           class="btn text-white" style="background-color: #606060; border: none;" title="Export PDF">
                            <i class="bi bi-file-earmark-pdf"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- ── SUMMARY CARDS ── --}}
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-md-3">
                <div class="card h-100 border-0 shadow-sm" style="background:#d88656; color:white;">
                    <div class="card-body">
                        <div style="font-size:11px; opacity:.9; text-transform:uppercase; letter-spacing:1px;">Total Transaksi</div>
                        <div class="fw-bold mt-1" style="font-size:28px;">{{ $totalTransaksi }}</div>
                        <div style="font-size:12px; opacity:.9;">pengeluaran tercatat</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="card h-100 border-0 shadow-sm" style="background:#f8f9fa;">
                    <div class="card-body">
                        <div style="font-size:11px; color:#6c757d; text-transform:uppercase; letter-spacing:1px;">Total Nilai HPP</div>
                        <div class="fw-bold mt-1" style="font-size:20px; color:#d88656;">
                            Rp {{ number_format($totalNilaiHpp, 0, ',', '.') }}
                        </div>
                        <div style="font-size:12px; color:#6c757d;">nilai bahan keluar</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="card h-100 border-0 shadow-sm" style="background:#f8f9fa;">
                    <div class="card-body">
                        <div style="font-size:11px; color:#6c757d; text-transform:uppercase; letter-spacing:1px;">Total Qty Keluar</div>
                        <div class="fw-bold mt-1" style="font-size:28px; color:#d88656;">
                            {{ number_format($totalQty, 0, ',', '.') }}
                        </div>
                        <div style="font-size:12px; color:#6c757d;">unit bahan baku</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="card h-100 border-0 shadow-sm" style="background:#d1e7dd;">
                    <div class="card-body">
                        <div style="font-size:11px; color:#0a3622; text-transform:uppercase; letter-spacing:1px;">Sudah Approved</div>
                        <div class="fw-bold mt-1" style="font-size:28px; color:#0a3622;">{{ $totalApproved }}</div>
                        <div style="font-size:12px; color:#0a3622;">dari {{ $totalTransaksi }} transaksi</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── TABEL UTAMA ── --}}
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="d-flex justify-content-between align-items-center px-4 py-3" style="border-bottom:1px solid #eadfd4;">
                    <span class="fw-bold" style="color:#d88656;">Rincian Pengeluaran Bahan Baku</span>
                    <span class="text-muted ms-2" style="font-size:13px;">{{ $data->count() }} dokumen</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size:13px;">
                        <thead style="background-color: #d88656; color: white;">
                            <tr>
                                <th style="background-color: #d88656; color: white;" class="px-4">Kode</th>
                                <th style="background-color: #d88656; color: white;">Tanggal</th>
                                <th style="background-color: #d88656; color: white;">Gudang</th>
                                <th style="background-color: #d88656; color: white;" class="text-center">Jml Item</th>
                                <th style="background-color: #d88656; color: white;" class="text-end">Nilai HPP</th>
                                <th style="background-color: #d88656; color: white;" class="text-center">Status</th>
                                <th style="background-color: #d88656; color: white;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($data as $row)
                                <tr>
                                    <td class="px-4 fw-semibold font-monospace" style="font-size:12px; color:#d88656;">
                                        {{ $row->kode_pengeluaran }}
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($row->tanggal)->format('d M Y') }}</td>
                                    <td class="text-muted">{{ $row->gudang->nama ?? '-' }}</td>
                                    <td class="text-center">{{ $row->details->count() }}</td>
                                    <td class="text-end fw-semibold">
                                        Rp {{ number_format($row->details->sum('hpp_total'), 0, ',', '.') }}
                                    </td>
                                    <td class="text-center">
                                        @if($row->status === 'approved')
                                            <span class="badge bg-success">Approved</span>
                                        @else
                                            <span class="badge bg-secondary">Draft</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        {{-- Tombol Detail memicu Modal di bawahnya --}}
                                        <button type="button" class="btn btn-sm text-white" style="background-color: #d88656; border: none;"
                                                data-bs-toggle="modal" data-bs-target="#modalDetail{{ $row->id }}">
                                            <i class="bi bi-eye"></i> Detail
                                        </button>

                                        {{-- MODAL DETAIL PER BARIS --}}
                                        <div class="modal fade" id="modalDetail{{ $row->id }}" tabindex="-1" aria-labelledby="modalDetailLabel{{ $row->id }}" aria-hidden="true">
                                            <div class="modal-dialog modal-lg text-start">
                                                <div class="modal-content">
                                                    <div class="modal-header text-white" style="background-color: #d88656;">
                                                        <h5 class="modal-title" id="modalDetailLabel{{ $row->id }}">Rincian Pengeluaran #{{ $row->kode_pengeluaran }}</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body p-0">
                                                        <div class="bg-light px-4 py-3 border-bottom text-muted" style="font-size:13px;">
                                                            <strong>Tanggal:</strong> {{ \Carbon\Carbon::parse($row->tanggal)->format('d M Y') }} &nbsp;|&nbsp;
                                                            <strong>Gudang:</strong> {{ $row->gudang->nama ?? '-' }} &nbsp;|&nbsp;
                                                            <strong>Status:</strong> <span class="badge {{ $row->status === 'approved' ? 'bg-success' : 'bg-secondary' }}">{{ ucfirst($row->status) }}</span>
                                                        </div>
                                                        <div class="table-responsive">
                                                            <table class="table align-middle mb-0" style="font-size:13px;">
                                                                <thead class="table-light">
                                                                    <tr>
                                                                        <th class="px-4">Nama Barang</th>
                                                                        <th class="text-center">Qty</th>
                                                                        <th>Satuan</th>
                                                                        <th class="text-end pe-4">HPP Total</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @forelse($row->details as $detail)
                                                                        <tr>
                                                                            <td class="px-4 fw-semibold" style="color: #d88656;">{{ $detail->barang->nama ?? '-' }}</td>
                                                                            <td class="text-center">{{ number_format($detail->qty ?? $detail->jumlah ?? 0, 2, ',', '.') }}</td>
                                                                            <td class="text-muted">{{ $detail->barang->satuan ?? '-' }}</td>
                                                                            <td class="text-end pe-4">Rp {{ number_format($detail->hpp_total ?? 0, 0, ',', '.') }}</td>
                                                                        </tr>
                                                                    @empty
                                                                        <tr>
                                                                            <td colspan="4" class="text-center py-3 text-muted">Tidak ada rincian barang</td>
                                                                        </tr>
                                                                    @endforelse
                                                                </tbody>
                                                                <tfoot>
                                                                    <tr style="background:#fdf3ec; font-weight:bold;">
                                                                        <td colspan="3" class="text-end" style="color:#d88656;">Total HPP Keseluruhan:</td>
                                                                        <td class="text-end pe-4" style="color:#d88656;">
                                                                            Rp {{ number_format($row->details->sum('hpp_total'), 0, ',', '.') }}
                                                                        </td>
                                                                    </tr>
                                                                </tfoot>
                                                            </table>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn text-white btn-sm px-4" style="background-color: #606060; border: none;" data-bs-dismiss="modal">Tutup</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        {{-- AKHIR MODAL DETAIL --}}

                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                        Tidak ada data pengeluaran bahan baku.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    {{-- Style Tambahan untuk Tombol Close Putih di Modal --}}
    <style>
        .btn-close-white {
            filter: invert(1) grayscale(1) brightness(2);
        }
    </style>
</x-app-layout>