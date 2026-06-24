<x-app-layout>
<div class="container py-4">
    {{-- JUDUL HALAMAN --}}
    <div class="mb-4">
        <h4 class="fw-bold text-dark">Laporan Penjualan B2B</h4>
        <p class="text-muted small">Pantau ringkasan omzet dan performa penjualan berkala.</p>
    </div>

    {{-- KARTU FILTER TANGGAL --}}
    <div class="card shadow-sm border-0 p-4 mb-4">
        <form action="{{ route('laporan.penjualan') }}" method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-bold text-dark small">Dari Tanggal</label>
                <input type="date" name="tanggal_mulai" class="form-control" value="{{ $tanggal_mulai }}">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold text-dark small">Sampai Tanggal</label>
                <input type="date" name="tanggal_selesai" class="form-control" value="{{ $tanggal_selesai }}">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100 fw-bold shadow-sm">
                    <i class="bi bi-filter me-1"></i> Filter Data
                </button>
                <a href="{{ route('laporan.penjualan') }}" class="btn btn-light border w-50">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- KOTAK STATISTIK / WIDGET RINGKASAN --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 p-3 bg-primary text-white">
                <div class="small opacity-75 fw-bold text-uppercase">Total Omzet Penjualan</div>
                <div class="fs-3 fw-bold mt-1">Rp {{ number_format($total_omzet, 0, ',', '.') }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 p-3 bg-white border-start border-success border-4">
                <div class="small text-muted fw-bold text-uppercase">Pesanan Selesai</div>
                <div class="fs-3 fw-bold text-success mt-1">{{ $pesanan_selesai }} <span class="fs-6 text-muted fw-normal">Transaksi</span></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 p-3 bg-white border-start border-warning border-4">
                <div class="small text-muted fw-bold text-uppercase">Pesanan Pending / Proses</div>
                <div class="fs-3 fw-bold text-warning mt-1">{{ $pesanan_pending }} <span class="fs-6 text-muted fw-normal">Transaksi</span></div>
            </div>
        </div>
    </div>

    {{-- TABEL DETAIL TRANSAKSI --}}
    <div class="card shadow-sm border-0 p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold text-dark mb-0"><i class="bi bi-list-stars me-2 text-primary"></i>Daftar Transaksi Masuk</h6>
            <span class="badge bg-light text-dark border py-2 px-3 small shadow-sm">Total: {{ $pesanans->count() }} Pesanan</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle border text-center">
                <thead class="table-light text-uppercase small font-monospace">
                    <tr>
                        <th>Kode</th>
                        <th>Customer</th>
                        <th>Tanggal</th>
                        <th>Status Pesanan</th>
                        <th>Status Bayar</th>
                        <th class="text-end pe-4">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pesanans as $p)
                        <tr>
                            <td class="fw-bold text-dark">{{ $p->kode_pesanan }}</td>
                            <td>{{ $p->customer->nama ?? 'N/A' }}</td>
                            <td>{{ date('d M Y', strtotime($p->tanggal)) }}</td>
                            <td>
                                {{-- Pengecekan status menggunakan strtolower agar aman dari beda huruf kapital --}}
                                @if(strtolower($p->status_pesanan) == 'selesai')
                                    <span class="badge bg-success rounded-pill px-3 py-1">Selesai</span>
                                @elseif(strtolower($p->status_pesanan) == 'siap kirim' || strtolower($p->status_pesanan) == 'siap_kirim')
                                    <span class="badge bg-info text-white rounded-pill px-3 py-1">Siap kirim</span>
                                @else
                                    <span class="badge bg-warning rounded-pill px-3 py-1 text-dark">Pending</span>
                                @endif
                            </td>
                            <td>
                                @if(isset($p->status_bayar) && strtolower($p->status_bayar) == 'lunas')
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success rounded-pill px-3 py-1">Lunas</span>
                                @else
                                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning rounded-pill px-3 py-1 text-dark">
                                        {{ $p->status_bayar ?? 'DP 30%' }}
                                    </span>
                                @endif
                            </td>
                            <td class="text-end pe-4 fw-bold text-dark">
                                Rp {{ number_format($p->details_sum_subtotal ?? 0, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-muted py-5">
                                <i class="bi bi-folder-x fs-1 d-block mb-2 text-secondary"></i>
                                Tidak ada data penjualan ditemukan pada periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</x-app-layout>