<x-app-layout>

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-gray-800 m-0">Detail Penjualan POS</h3>
        
        <div>
            <a href="{{ route('penjualan_pos.index') }}" class="btn btn-outline-secondary me-2 px-4">
                Kembali
            </a>
            <a href="{{ route('penjualan_pos.edit', $penjualan->id) }}" class="btn btn-warning px-4 text-dark fw-medium">
                Edit Transaksi
            </a>
        </div>
    </div>

    @php
        $totalHpp = $penjualan->details ? $penjualan->details->sum(fn($d) => $d->hpp_satuan * $d->qty) : 0;
        $labaKotor = $penjualan->total - $totalHpp;
    @endphp

    <div class="row mb-4 align-items-stretch">
        
        <div class="col-md-5">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h6 class="fw-bold text-muted mb-3 border-bottom pb-2">Informasi Transaksi</h6>
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td width="130" class="text-muted">Kode Transaksi</td>
                            <td class="fw-semibold">: {{ $penjualan->kode_transaksi }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Tanggal</td>
                            <td>: {{ \Carbon\Carbon::parse($penjualan->tanggal)->format('d-m-Y H:i') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Gudang</td>
                            <td>: {{ $penjualan->gudang->nama }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Input Oleh</td>
                            <td>: {{ $penjualan->creator->name ?? '-' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card shadow-sm border-0 h-100 border-start border-primary border-4">
                <div class="card-body d-flex flex-column justify-content-center">
                    
                    <div class="row text-center align-items-center">
                        <div class="col-12 mb-4">
                            <span class="text-muted text-uppercase" style="font-size: 0.85rem; letter-spacing: 1px;">Total Omzet (Penjualan)</span>
                            <h2 class="text-primary fw-bold mt-1 mb-0">
                                Rp {{ number_format($penjualan->total, 0, ',', '.') }}
                            </h2>
                        </div>
                        
                        <div class="col-12 mb-3">
                            <hr class="m-0 text-muted" style="opacity: 0.15;">
                        </div>

                        <div class="col-6 border-end">
                            <span class="text-muted" style="font-size: 0.85rem;">Total HPP</span>
                            <h5 class="fw-medium text-secondary mt-1 mb-0">Rp {{ number_format($totalHpp, 0, ',', '.') }}</h5>
                        </div>
                        <div class="col-6">
                            <span class="text-muted" style="font-size: 0.85rem;">Laba Kotor</span>
                            <h5 class="text-success fw-bold mt-1 mb-0">Rp {{ number_format($labaKotor, 0, ',', '.') }}</h5>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 border-bottom">
            <h6 class="mb-0 fw-bold">Rincian Produk Terjual</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle text-nowrap mb-0">

                    <thead class="table-dark">
                        <tr>
                            <th class="ps-4" width="50">No</th>
                            <th>Produk</th>
                            <th class="text-center" width="80">Qty</th>
                            <th class="text-end">Harga Jual</th>
                            <th class="text-end">HPP Satuan</th>
                            <th class="text-end">Subtotal Jual</th>
                            <th class="text-end">Total HPP</th>
                            <th class="text-end pe-4">Laba</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($penjualan->details as $key => $d)
                        @php
                            $totalHppItem = $d->hpp_satuan * $d->qty;
                            $labaItem = $d->subtotal - $totalHppItem;
                        @endphp

                        <tr>
                            <td class="ps-4 text-muted">{{ $key + 1 }}</td>
                            <td class="fw-medium">{{ $d->produk->nama }}</td>
                            <td class="text-center bg-light">{{ $d->qty }}</td>
                            
                            <td class="text-end">Rp {{ number_format($d->harga, 0, ',', '.') }}</td>
                            <td class="text-end text-muted">Rp {{ number_format($d->hpp_satuan, 0, ',', '.') }}</td>
                            <td class="text-end fw-medium">Rp {{ number_format($d->subtotal, 0, ',', '.') }}</td>
                            <td class="text-end text-muted">Rp {{ number_format($totalHppItem, 0, ',', '.') }}</td>
                            
                            <td class="text-end text-success fw-bold pe-4">
                                Rp {{ number_format($labaItem, 0, ',', '.') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>

                </table>
            </div>
        </div>
    </div>

</div>

</x-app-layout>