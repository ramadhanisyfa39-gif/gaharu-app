<x-app-layout>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8f9fa; }
        .table-custom-header th { background-color: #6a4126 !important; color: #ffffff !important; font-weight: 600; border-bottom: none; font-size: 0.85rem; }
        .table-custom-body td { font-size: 0.85rem; } /* Mengecilkan teks tabel */
        .btn-custom-orange { background-color: #db7946; color: white; border: none; }
        .btn-custom-orange:hover { background-color: #c06535; color: white; }
        .summary-card { border-radius: 12px; border: 1px solid #eaeaea; background: #ffffff; padding: 16px 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    </style>

    <div class="container-fluid py-4 mb-5">
        
        {{-- HEADER SECTION --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-1 fw-bold text-dark">Manajemen Hasil Produksi</h4>
                <p class="text-muted mb-0 small">Manajemen penyelesaian produksi dan alokasi stok produk jadi</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('dashboard') }}" class="btn btn-secondary text-white rounded-3 shadow-sm px-3 btn-sm d-flex align-items-center gap-2">
                    <i class="bi bi-arrow-left"></i> Dashboard
                </a>
                <a href="{{ route('produksi.create') }}" class="btn btn-custom-orange rounded-3 shadow-sm px-3 btn-sm d-flex align-items-center gap-2">
                    <i class="bi bi-plus-circle"></i> Tambah
                </a>
            </div>
        </div>

        {{-- SUMMARY CARDS --}}
        @php
            $totalData = $riwayatProduksi->count();
            $totalDraft = $riwayatProduksi->where('status_produksi', 'Draft')->count();
            $totalApproved = $riwayatProduksi->where('status_produksi', 'Selesai')->count();
        @endphp
        <div class="row mb-4 g-3">
            <div class="col-md-4">
                <div class="summary-card">
                    <span class="text-dark mb-1 d-block fw-medium small">Total Produksi</span>
                    <h4 class="fw-bold text-dark mb-0">{{ $totalData }}</h4>
                </div>
            </div>
            <div class="col-md-4">
                <div class="summary-card">
                    <span class="text-dark mb-1 d-block fw-medium small">Draft</span>
                    <h4 class="fw-bold text-warning mb-0">{{ $totalDraft }}</h4>
                </div>
            </div>
            <div class="col-md-4">
                <div class="summary-card">
                    <span class="text-dark mb-1 d-block fw-medium small">Approved</span>
                    <h4 class="fw-bold text-success mb-0">{{ $totalApproved }}</h4>
                </div>
            </div>
        </div>

        {{-- ALERTS --}}
        @if(session('success'))
            <div class="alert alert-success d-flex align-items-center rounded-3 shadow-sm border-0 mb-4 small" role="alert" style="background-color: #d1e7dd; color: #0f5132;">
                <i class="bi bi-check-circle-fill me-2 flex-shrink-0"></i>
                <div>{{ session('success') }}</div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger d-flex align-items-center rounded-3 shadow-sm border-0 mb-4 small" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2 flex-shrink-0"></i>
                <div>{{ session('error') }}</div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- MAIN TABLE CARD --}}
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-custom-body">
                    <thead class="table-custom-header text-center">
                        <tr>
                            <th width="5%" class="py-3">No</th>
                            <th width="15%" class="text-start">Kode Produksi</th>
                            <th width="12%">Kode WO</th>
                            <th width="10%">Tanggal</th>
                            <th class="text-start">Nama Produk</th>
                            <th width="10%">Qty Hasil</th>
                            <th width="12%" class="text-end">HPP Total</th>
                            <th width="12%">Status</th>
                            <th width="15%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        @php $no = 1; @endphp
                        @forelse($riwayatProduksi as $p)
                            @php
                                $kodeWo = DB::table('work_order')
                                    ->join('work_order_detail', 'work_order.id', '=', 'work_order_detail.work_order_id')
                                    ->where('work_order_detail.pesanan_id', $p->pesanan_id)
                                    ->value('work_order.kode_wo') ?? '-';
                                    
                                $rowCount = $p->details->count() ?: 1;
                            @endphp

                            @if($rowCount > 0)
                                @foreach($p->details as $index => $detail)
                                    <tr>
                                        @if($index === 0)
                                            <td rowspan="{{ $rowCount }}" class="text-center text-secondary">{{ $no++ }}</td>
                                            <td rowspan="{{ $rowCount }}" class="text-start fw-bold text-dark">{{ $p->kode_produksi }}</td>
                                            <td rowspan="{{ $rowCount }}" class="text-center text-secondary">{{ $kodeWo }}</td>
                                            <td rowspan="{{ $rowCount }}" class="text-center text-secondary">
                                                {{ $p->tanggal_mulai ? \Carbon\Carbon::parse($p->tanggal_mulai)->format('d/m/Y') : '-' }}
                                            </td>
                                        @endif

                                        <td class="text-start text-dark fw-semibold">{{ $detail->produk->nama ?? 'Produk Tidak Diketahui' }}</td>
                                        <td class="text-center"><span class="badge bg-info text-dark">{{ (int) $detail->qty }} Unit</span></td>
                                        <td class="text-end fw-medium text-success">
                                            @if($p->status_produksi === 'Draft')
                                                <span class="text-muted fst-italic">Menunggu</span>
                                            @else
                                                Rp {{ number_format($detail->hpp_total ?? 0, 0, ',', '.') }}
                                            @endif
                                        </td>

                                        @if($index === 0)
                                            <td rowspan="{{ $rowCount }}" class="text-center">
                                                {{-- KEMBALI KE STATUS LAMA --}}
                                                @if($p->status_produksi === 'Draft')
                                                    <span class="badge bg-warning text-dark px-2 py-1"><i class="bi bi-file-earmark-text"></i> Draft</span>
                                                @else
                                                    <span class="badge bg-success px-2 py-1"><i class="bi bi-check-all"></i> Selesai</span>
                                                @endif
                                            </td>
                                            <td rowspan="{{ $rowCount }}" class="text-center">
                                                <div class="d-flex justify-content-center gap-1">
                                                    
                                                    {{-- Tombol Detail (Selalu Muncul) --}}
                                                    <a href="{{ route('produksi.show', $p->id) }}" class="btn btn-sm btn-info text-white shadow-sm" title="Lihat Detail">
                                                        <i class="bi bi-eye"></i>
                                                    </a>

                                                    @if($p->status_produksi === 'Draft')
                                                        {{-- Tombol Approve --}}
                                                        <form action="{{ route('produksi.approve', $p->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Approve produksi ini? Sistem akan memotong stok bahan baku dan mengunci data.')">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-success shadow-sm" title="Approve">
                                                                <i class="bi bi-check-lg"></i>
                                                            </button>
                                                        </form>

                                                        {{-- Tombol Edit --}}
                                                        <a href="{{ route('produksi.edit', $p->id) }}" class="btn btn-sm btn-warning text-dark shadow-sm" title="Edit Qty Hasil">
                                                            <i class="bi bi-pencil-square"></i>
                                                        </a>

                                                        {{-- Tombol Hapus --}}
                                                        <form action="{{ route('produksi.destroy', $p->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus draft produksi ini secara permanen?')">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-danger shadow-sm" title="Hapus">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            @endif
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                        Belum ada riwayat atau draft produksi.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>