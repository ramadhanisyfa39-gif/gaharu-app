<x-app-layout>
<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="fw-bold text-dark">Resep: {{ $resep->produk->nama }}</h3>
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
        $btkl_per_produk = $resep->btkl_per_batch / $resep->output_qty;
        $bop_per_produk  = $resep->bop_per_batch / $resep->output_qty;
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
                    @foreach($bahan as $b)
                    <tr>
                        <td class="ps-4 fw-semibold text-dark">{{ $b->bahan->nama }}</td>
                        <td class="text-center">{{ (int) $b->qty_bahan }}</td>
                        <td class="text-center">
                            <span class="badge bg-secondary opacity-75 px-3">{{ $b->satuan }}</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4 text-muted small">
        <p><i class="fas fa-info-circle me-1"></i> Data di atas menampilkan estimasi biaya tenaga kerja (BTKL) dan biaya operasional (BOP) tanpa menyertakan harga bahan baku.</p>
    </div>

</div>
</x-app-layout>