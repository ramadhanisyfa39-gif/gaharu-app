<x-app-layout>
<div class="container py-4">
    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('wo.index') }}">Work Order</a></li>
                    <li class="breadcrumb-item active">Detail</li>
                </ol>
            </nav>
            <h4 class="fw-bold text-dark">Detail Work Order: <span class="text-primary">{{ $wo->kode_wo }}</span></h4>
        </div>
        <a href="{{ route('wo.index') }}" class="btn btn-secondary btn-sm shadow-sm">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="row">
    {{-- PANEL INFORMASI KIRI --}}
<div class="col-md-4">
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white fw-bold border-bottom py-3">
            <i class="bi bi-info-circle me-2 text-primary"></i>Informasi WO
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label class="text-muted small d-block">Tanggal Dibuat</label>
                <span class="fw-bold">{{ \Carbon\Carbon::parse($wo->tanggal_wo)->format('d M Y H:i') }}</span>
            </div>
            <div class="mb-0">
                <label class="text-muted small d-block">Status Produksi</label>
                @if($wo->status_wo == 'Draft')
                    <span class="badge bg-secondary px-3">Draft</span>
                @else
                    <span class="badge bg-success px-3">{{ $wo->status_wo }}</span>
                @endif
            </div>

            {{-- TAMBAHKAN KODE TOMBOL OTOMATISASI DI SINI --}}
            <hr>
            @if($wo->status_wo == 'Draft')
                <form action="{{ route('wo.kirim_produksi', $wo->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-success w-100 shadow-sm fw-bold" 
                            onclick="return confirm('Kirim kalkulasi resep sebagai permintaan bahan ke gudang?')">
                        <i class="bi bi-send-check me-2"></i>Kirim Permintaan Bahan
                    </button>
                    <small class="text-muted d-block mt-2 text-center" style="font-size: 0.75rem;">
                        Klik untuk menghitung resep secara otomatis ke tabel permintaan temanmu.
                    </small>
                </form>
            @else
                <div class="alert alert-info border-0 py-2 small mb-0">
                    <i class="bi bi-check-circle-fill me-1"></i> Permintaan bahan sudah diproses ke gudang utama.
                </div>
            @endif
            {{-- SELESAI PENAMBAHAN --}}

        </div>
    </div>

    <div class="d-grid gap-2">
        <button class="btn btn-outline-primary btn-sm" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Cetak Surat Jalan Produksi
        </button>
    </div>
</div>

        {{-- TABEL ITEM PRODUKSI KANAN --}}
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white fw-bold py-3">
                    <i class="bi bi-list-check me-2"></i>Item Produksi
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Customer / Pesanan</th>
                                    <th>Produk</th>
                                    <th class="text-center pe-4">Qty Rencana</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($wo->details as $detail)
                                <tr>
                                    <td class="ps-4">
                                        {{-- Menampilkan nama customer per item untuk WO Massal --}}
                                        <div class="fw-bold text-dark">{{ $detail->pesanan->customer->nama ?? 'Multi Customer' }}</div>
                                        <small class="text-muted">{{ $detail->pesanan->kode_pesanan ?? '-' }}</small>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-primary">{{ $detail->produk->nama ?? 'Produk' }}</div>
                                        {{-- Tombol Resep dengan ID Unik --}}
                                        <button type="button" class="btn btn-sm btn-info text-white py-0 px-2 mt-1 shadow-sm" 
                                                data-bs-toggle="modal" data-bs-target="#modalResep{{ $detail->id }}">
                                            <i class="bi bi-journal-text me-1"></i> Resep
                                        </button>
                                    </td>
                                    <td class="text-center pe-4">
                                        <span class="fs-5 fw-bold text-dark">{{ number_format($detail->qty_rencana, 0) }}</span>
                                        <small class="text-muted d-block">{{ $detail->produk->satuan ?? 'Unit' }}</small>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL RESEP - Diletakkan di luar loop utama untuk performa --}}
@foreach($wo->details as $detail)
<div class="modal fade" id="modalResep{{ $detail->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-receipt me-2"></i>Resep: {{ $detail->produk->nama ?? '' }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="p-3 bg-light border-bottom">
                    <small class="text-muted d-block">Estimasi Bahan Baku untuk Produksi:</small>
                    <span class="fw-bold fs-5 text-dark">{{ number_format($detail->qty_rencana, 0) }} {{ $detail->produk->satuan }}</span>
                </div>
                <ul class="list-group list-group-flush">
                    @php
                        // Menggunakan relasi 'resep' dan 'bahan' yang sudah diperbaiki
                        $resepData = $detail->produk->resep ?? collect();
                    @endphp

                    @forelse($resepData as $r)
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <div>
                                <span class="fw-bold d-block text-dark">{{ $r->bahan->nama ?? 'Bahan Baku' }}</span>
                                <small class="text-muted">Standar: {{ $r->qty_bahan }} {{ $r->satuan }} / {{ $detail->produk->satuan }}</small>
                            </div>
                            <div class="text-end">
                                {{-- Kalkulasi: Qty Bahan per resep * Qty Rencana Produksi --}}
                                <span class="badge bg-primary fs-6 rounded-pill">
                                    {{ number_format(($r->qty_bahan ?? 0) * $detail->qty_rencana, 2) }} {{ $r->satuan }}
                                </span>
                            </div>
                        </li>
                    @empty
                        <li class="list-group-item text-center py-5 text-muted">
                            <i class="bi bi-exclamation-triangle fs-2 d-block mb-2"></i>
                            Data resep atau bahan baku belum diatur di master data.
                        </li>
                    @endforelse
                </ul>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endforeach

{{-- SCRIPT PENUNJANG (Hanya jika belum ada di Layout Utama) --}}
@push('scripts')
<script>
    // Memastikan modal Bootstrap terinisialisasi dengan benar
    document.addEventListener('DOMContentLoaded', function () {
        var modalLinks = document.querySelectorAll('[data-bs-toggle="modal"]');
        modalLinks.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var target = btn.getAttribute('data-bs-target');
                var modal = new bootstrap.Modal(document.querySelector(target));
                modal.show();
            });
        });
    });
</script>
@endpush
</x-app-layout>