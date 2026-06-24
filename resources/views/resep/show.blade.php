<x-app-layout>
<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="fw-bold text-dark">Resep: {{ $resep->produk->nama ?? 'Produk Tidak Diketahui' }}</h3>
        <a href="{{ route('resep.index') }}" class="btn btn-secondary rounded-3">
            Kembali
        </a>
    </div>

    {{-- INFO OUTPUT & BIAYA OPERASIONAL --}}
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body bg-info text-white rounded-3">
                    <small class="text-uppercase fw-bold opacity-75">Target Produksi</small>
                    <h4 class="mb-0 fw-bold">{{ (int) $resep->output_qty }} {{ $resep->satuan_output }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body bg-success text-white rounded-3">
                    <div class="row">
                        <div class="col-6 border-end">
                            <small class="text-uppercase fw-bold opacity-75">BTKL / Batch</small>
                            <div class="fw-bold">Rp {{ number_format($resep->btkl_per_batch) }}</div>
                        </div>
                        <div class="col-6 ps-3">
                            <small class="text-uppercase fw-bold opacity-75">BOP / Batch</small>
                            <div class="fw-bold">Rp {{ number_format($resep->bop_per_batch) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- HITUNG PER PRODUK (Hanya untuk Biaya Operasional) --}}
    @php
        // Ditambahkan validasi pembagi nol agar sistem aman jika output_qty belum diisi
        $btkl_per_produk = $resep->output_qty > 0 ? $resep->btkl_per_batch / $resep->output_qty : 0;
        $bop_per_produk  = $resep->output_qty > 0 ? $resep->bop_per_batch / $resep->output_qty : 0;
    @endphp

    <div class="alert alert-light border shadow-sm d-flex justify-content-around text-center mb-4">
        <div>
            <span class="text-muted small text-uppercase d-block">BTKL / Produk</span>
            <strong class="text-dark">Rp {{ number_format($btkl_per_produk) }}</strong>
        </div>
        <div class="vr"></div>
        <div>
            <span class="text-muted small text-uppercase d-block">BOP / Produk</span>
            <strong class="text-dark">Rp {{ number_format($bop_per_produk) }}</strong>
        </div>
    </div>

    {{-- TABEL BAHAN BAKU (Fokus pada Kuantitas) --}}
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold"><i class="fas fa-flask me-2 text-primary"></i>Komposisi Bahan</h5>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4 py-3">Nama Bahan</th>
                        <th class="text-center py-3">Qty / Produk</th>
                        <th class="text-center py-3">Satuan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($resep->bahanbaku as $b)
                    <tr>
                        <td class="ps-4 fw-semibold text-dark">{{ $b->bahan->nama ?? 'Bahan Tidak Diketahui' }}</td>
                        <td class="text-center">{{ $b->qty_bahan }}</td>
                        <td class="text-center">
                            <span class="badge bg-secondary opacity-75 px-3">{{ $b->satuan ?? '-' }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="text-center text-muted py-4">Tidak ada komponen bahan baku yang terdaftar.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
</x-app-layout>