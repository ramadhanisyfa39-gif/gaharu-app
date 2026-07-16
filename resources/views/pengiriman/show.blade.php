<x-app-layout>
<div class="container py-4">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-0 fw-bold"><i class="bi bi-file-earmark-text me-2"></i> Detail Surat Jalan</h4>
                <small class="text-white-50">Rincian pengiriman produk ke customer.</small>
            </div>
            <div>
                @if($pengiriman->status_pengiriman === 'Selesai')
                    <span class="badge bg-success fs-6 px-3 py-2 shadow-sm">Selesai</span>
                @else
                    <span class="badge bg-warning text-dark fs-6 px-3 py-2 shadow-sm">Draft</span>
                @endif
            </div>
        </div>

        <div class="card-body p-4">
            <div class="row mb-4 bg-light p-3 rounded border">
                <div class="col-12 col-md-6">
                    <table class="table table-borderless table-sm mb-0">
                        <tr>
                            <td width="150" class="text-muted">No Surat Jalan</td>
                            <td>: <strong class="text-primary fs-5">{{ $pengiriman->no_pengiriman }}</strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Tanggal Kirim</td>
                            <td>: {{ \Carbon\Carbon::parse($pengiriman->tanggal_pengiriman)->translatedFormat('d F Y') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Ekspedisi / Kurir</td>
                            <td>: <strong>{{ $pengiriman->kurir }}</strong></td>
                        </tr>
                    </table>
                </div>
                <div class="col-12 col-md-6 border-start-0 border-md-start mt-3 mt-md-0">
                    <table class="table table-borderless table-sm mb-0">
                        <tr>
                            <td width="150" class="text-muted">Kode Pesanan</td>
                            <td>: <span class="badge bg-secondary fs-6">{{ $pengiriman->pesanan->kode_pesanan ?? '-' }}</span></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Nama Customer</td>
                            <td>: <strong>{{ $pengiriman->pesanan->customer->nama ?? 'Tidak Diketahui' }}</strong></td>
                        </tr>
                    </table>
                </div>
            </div>

            <h5 class="fw-bold border-bottom pb-2 mb-3">Item Yang Dikirim</h5>
            <div class="table-responsive mb-4">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-dark text-center">
                        <tr>
                            <th width="5%">No</th>
                            <th class="text-start ps-3">Nama Produk / Barang</th>
                            <th width="25%">Jumlah Dikirim</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pengiriman->details as $index => $detail)
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td class="text-start ps-3 fw-bold">{{ $detail->barang->nama ?? 'Produk Tidak Diketahui' }}</td>
                            <td class="text-center fw-bold text-success fs-5">
                                {{ number_format($detail->qty_kirim, 0, ',', '.') }} {{ $detail->barang->satuan ?? 'Unit' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <hr class="text-muted my-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <a href="{{ route('pengiriman.index') }}" class="btn btn-secondary px-4">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
    
                <div class="d-flex gap-2 flex-wrap">
                    @if($pengiriman->status_pengiriman === 'Draft')
                        <a href="{{ route('pengiriman.edit', $pengiriman->id) }}" class="btn btn-warning text-dark fw-bold">
                            <i class="bi bi-pencil-square"></i> Edit Draft
                        </a>
                        
                        <form action="{{ route('pengiriman.approve', $pengiriman->id) }}" method="POST" id="form-approve">
                            @csrf
                            <button type="submit" class="btn btn-success fw-bold" onclick="return confirm('Setujui Pengiriman ini? Stok barang jadi akan dipotong otomatis secara permanen.')">
                                <i class="bi bi-check2-all"></i> Approve & Kurangi Stok
                            </button>
                        </form>
                    @else
                        <button onclick="window.print()" class="btn btn-outline-dark px-4 shadow-sm">
                            <i class="bi bi-printer"></i> Cetak Surat Jalan
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        body { background-color: #fff; }
        .btn, nav, header, footer, form { display: none !important; }
        .card { border: none !important; box-shadow: none !important; }
        .card-header { background-color: #fff !important; color: #000 !important; border-bottom: 2px solid #000; }
    }
</style>
</x-app-layout>