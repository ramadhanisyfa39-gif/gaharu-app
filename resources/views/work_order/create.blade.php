<x-app-layout>
<div class="container">
    <div class="card shadow-sm">
        <div class="card-header">
            <h4 class="mb-0">Tambah Work Order</h4>
        </div>
        
        <div class="card-body">
            <form action="{{ route('wo.store') }}" method="POST">
                @csrf

                {{-- HEADER WO --}}
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Kode WO</label>
                        <input type="text" name="kode_wo" class="form-control bg-light" value="WO-{{ time() }}" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Tanggal WO</label>
                        <input type="datetime-local" name="tanggal_wo" class="form-control" required>
                    </div>
                </div>

                {{-- INFORMASI PESANAN --}}
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Customer</label>
                        <input type="text" class="form-control bg-light" value="{{ $pesanan->customer->nama }}" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Estimasi Pengiriman</label>
                        <input type="text" class="form-control bg-light" value="{{ \Carbon\Carbon::parse($pesanan->estimasi_kirim)->format('d M Y H:i') }}" readonly>
                    </div>
                </div>

                {{-- DETAIL PRODUK --}}
                <div class="card mb-3 border-primary">
                    <div class="card-header bg-primary text-white">
                        <strong>Detail Pesanan & Rencana Produksi</strong>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Produk</th>
                                        <th width="120" class="text-center">Qty Pesanan</th>
                                        <th width="120" class="text-center">Qty Sudah WO</th>
                                        <th width="120" class="text-center">Sisa Qty</th>
                                        <th width="180">Qty WO Sekarang</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pesanan->details as $detail)
                                    @php
                                        // Asumsi relasi pesanan_detail_id masih kamu gunakan untuk melacak sisa
                                        $qtySudahWO = \App\Models\WorkOrderDetail::where(
                                            'pesanan_id', 
                                            $detail->id
                                        )->sum('qty_rencana');
                                        
                                        $sisaQty = $detail->qty - $qtySudahWO;
                                    @endphp

                                    <tr>
                                        {{-- PRODUK --}}
                                        <td>{{ $detail->produk->nama }}</td>
                                        
                                        {{-- QTY PESANAN --}}
                                        <td class="text-center">{{ $detail->qty }}</td>
                                        
                                        {{-- QTY SUDAH WO --}}
                                        <td class="text-center">{{ $qtySudahWO }}</td>
                                        
                                        {{-- SISA --}}
                                        <td class="text-center">
                                            @if($sisaQty > 0)
                                                <span class="badge bg-danger fs-6">{{ $sisaQty }}</span>
                                            @else
                                                <span class="badge bg-success fs-6"><i class="bi bi-check-circle"></i> Terpenuhi</span>
                                            @endif
                                        </td>
                                        
                                        {{-- INPUT QTY WO --}}
                                        <td>
                                            @if($sisaQty > 0)
                                                <div class="input-group input-group-sm">
                                                    <input type="number" 
                                                           name="qty_rencana[]" 
                                                           class="form-control border-primary" 
                                                           value="{{ $sisaQty }}" 
                                                           min="1" 
                                                           max="{{ $sisaQty }}" 
                                                           required>
                                                    <span class="input-group-text">{{ $detail->produk->satuan ?? 'pcs' }}</span>
                                                </div>

                                                <input type="hidden" name="pesanan_id[]" value="{{ $pesanan->id }}">
                                                <input type="hidden" name="produk_id[]" value="{{ $detail->produk_id }}">
                                                <input type="hidden" name="pesanan_id[]" value="{{ $detail->id }}">
                                            @else
                                                <span class="text-muted fst-italic">Selesai</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- CATATAN --}}
                <div class="mb-4">
                    <label class="form-label fw-bold">Catatan Produksi</label>
                    <textarea name="catatan" rows="2" class="form-control" placeholder="Contoh: Produksi batch pagi, prioritaskan packing kardus..."></textarea>
                </div>

                {{-- BUTTON --}}
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('wo.index') }}" class="btn btn-outline-secondary px-4">Batal</a>
                    <button type="submit" class="btn btn-success px-4">
                        <i class="bi bi-save"></i> Simpan WO
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
</x-app-layout>