<x-app-layout>
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold text-primary">Tambah Work Order</h5>
                            <a href="{{ route('wo.index') }}" class="btn btn-sm btn-outline-secondary">Kembali</a>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <form action="{{ route('wo.store') }}" method="POST">
                            @csrf

                            {{-- Hidden Pesanan ID - Cukup satu kali di luar loop --}}
                            <input type="hidden" name="pesanan_id" value="{{ $pesanan->id }}">

                            {{-- HEADER WO --}}
                            <div class="row mb-4">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Kode WO</label>
                                    <input type="text" name="kode_wo" class="form-control bg-light" value="WO-{{ time() }}" readonly>
                                    <small class="text-muted">Kode diatur otomatis oleh sistem.</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Tanggal WO</label>
                                    <input type="datetime-local" name="tanggal_wo" class="form-control" value="{{ date('Y-m-d\TH:i') }}" required>
                                </div>
                            </div>

                            {{-- INFORMASI PESANAN --}}
                            <div class="row mb-4 p-3 bg-light rounded mx-1">
                                <div class="col-md-4 mb-2">
                                    <label class="text-muted small d-block">Customer</label>
                                    <span class="fw-bold text-dark">{{ $pesanan->customer->nama }}</span>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="text-muted small d-block">Estimasi Pengiriman</label>
                                    <span class="fw-bold text-dark">{{ \Carbon\Carbon::parse($pesanan->estimasi_kirim)->format('d M Y H:i') }}</span>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="text-muted small d-block">Status Pembayaran</label>
                                    <span class="badge {{ $pesanan->status_pembayaran == 'Belum Bayar' ? 'bg-danger' : 'bg-success' }}">
                                        {{ $pesanan->status_pembayaran }}
                                    </span>
                                </div>
                            </div>

                            {{-- DETAIL PRODUK --}}
                            <div class="card mb-4 border-0 shadow-sm">
                                <div class="card-header bg-primary text-white py-2">
                                    <span class="small fw-bold">Detail Pesanan & Rencana Produksi</span>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr class="small text-uppercase">
                                                <th class="ps-3">Produk</th>
                                                <th class="text-center">Qty Pesanan</th>
                                                <th class="text-center">Sudah WO</th>
                                                <th class="text-center">Sisa</th>
                                                <th width="200" class="pe-3">Qty WO Sekarang</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($pesanan->details as $detail)
                                            @php
                                                // Hitung qty yang sudah dibuatkan WO untuk produk ini di pesanan ini
                                                $qtySudahWO = \App\Models\WorkOrderDetail::where('pesanan_id', $pesanan->id)
                                                                ->where('produk_id', $detail->produk_id)
                                                                ->sum('qty_rencana');
                                                $sisaQty = $detail->qty - $qtySudahWO;
                                            @endphp

                                            <tr>
                                                <td class="ps-3">
                                                    <div class="fw-bold">{{ $detail->produk->nama }}</div>
                                                    <small class="text-muted">ID: {{ $detail->produk->kode_barang }}</small>
                                                </td>
                                                <td class="text-center">{{ $detail->qty }}</td>
                                                <td class="text-center text-muted">{{ $qtySudahWO }}</td>
                                                <td class="text-center">
                                                    @if($sisaQty > 0)
                                                        <span class="badge bg-soft-danger text-danger border border-danger px-2">{{ $sisaQty }}</span>
                                                    @else
                                                        <span class="badge bg-success px-2"><i class="bi bi-check"></i> Terpenuhi</span>
                                                    @endif
                                                </td>
                                                <td class="pe-3">
                                                    @if($sisaQty > 0)
                                                        <div class="input-group input-group-sm">
                                                            <input type="number" 
                                                                   name="qty_rencana[]" 
                                                                   class="form-control border-primary" 
                                                                   value="{{ $sisaQty }}" 
                                                                   min="1" 
                                                                   max="{{ $sisaQty }}" 
                                                                   required>
                                                            <span class="input-group-text bg-white">{{ $detail->produk->satuan ?? 'pcs' }}</span>
                                                        </div>
                                                        {{-- Kirim produk_id sesuai urutan qty_rencana --}}
                                                        <input type="hidden" name="produk_id[]" value="{{ $detail->produk_id }}">
                                                    @else
                                                        <span class="text-muted fst-italic small">Sudah Terproses</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            {{-- CATATAN --}}
                            <div class="mb-4">
                                <label class="form-label fw-semibold">Catatan Produksi</label>
                                <textarea name="catatan" rows="3" class="form-control" placeholder="Tambahkan instruksi khusus untuk tim produksi jika ada..."></textarea>
                            </div>

                            <hr class="my-4 text-muted">

                            {{-- BUTTON --}}
                            <div class="d-flex justify-content-between align-items-center">
                                <p class="small text-muted mb-0">* Pastikan data rencana produksi sudah sesuai sebelum menyimpan.</p>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('wo.index') }}" class="btn btn-light px-4">Batal</a>
                                    <button type="submit" class="btn btn-primary px-4 shadow-sm">
                                        <i class="bi bi-check2-circle me-1"></i> Simpan sebagai Draft
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .bg-soft-danger { background-color: #f8d7da; }
        .form-control:focus { border-color: #0d6efd; box-shadow: none; }
    </style>
</x-app-layout>