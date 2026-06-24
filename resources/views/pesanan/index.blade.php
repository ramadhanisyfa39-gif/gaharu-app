<x-app-layout>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

<div class="container mt-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Data Pesanan</h2>
            <small class="text-muted">Manajemen pesanan customer & pembayaran</small>
        </div>
        <a href="{{ route('pesanan.create') }}" class="btn btn-primary shadow-sm rounded-3">
            <i class="bi bi-plus-circle"></i> Tambah Pesanan
        </a>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0"> 
            <div class="table-responsive">
                <table class="table align-middle table-hover mb-0">
                    <thead class="bg-light">
                        <tr class="text-secondary">
                            <th class="ps-4">Kode</th>
                            <th>Customer</th>
                            <th>Tanggal</th>
                            <th>Total</th>
                            <th class="text-center">Status Pesanan</th>
                            <th class="text-center">Status Bayar</th>
                            <th class="text-center pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pesanan as $p)
                        <tr>
                            <td class="ps-4 fw-bold text-dark">{{ $p->kode_pesanan }}</td>
                            <td>
                                <div class="fw-semibold">{{ $p->customer->nama ?? $p->customer->name ?? '-' }}</div>
                                <small class="text-muted">{{ $p->customer->telepon ?? '' }}</small>
                            </td>
                            <td>
                                <small>{{ date('d M Y', strtotime($p->tanggal ?? $p->created_at)) }}</small>
                            </td>
                            <td class="fw-bold text-success">
                                Rp {{ number_format($p->total_pesanan, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                            @php
                                $statusClasses = [
                                    'pending'    => 'bg-warning text-dark',
                                    'siap kirim' => 'bg-primary text-white',
                                    'selesai'    => 'bg-success text-white',
                                ];
                                // Ditambahkan strtolower() agar huruf besar di database otomatis terbaca oleh array
                                $class = $statusClasses[strtolower($p->status_pesanan)] ?? 'bg-secondary text-white';
                             @endphp
                            <span class="badge rounded-pill {{ $class }} px-3 py-2">{{ ucfirst($p->status_pesanan) }}</span>
                            </td>
                            <td class="text-center">
                                @if($p->status_pembayaran == 'Belum Bayar')
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3">Belum Bayar</span>
                                @elseif($p->status_pembayaran == 'DP')
                                    <span class="badge bg-warning-subtle text-warning-dark border border-warning-subtle px-3">DP 30%</span>
                                @else
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3">Lunas</span>
                                @endif
                            </td>
                            <td class="text-center pe-4">
                                <div class="d-flex justify-content-center gap-2">
                                    @if($p->status_pembayaran != 'Lunas')
                                        <button type="button" class="btn btn-sm btn-success rounded-3 px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalBayar{{ $p->id }}">
                                            <i class="bi bi-cash-coin"></i> Bayar
                                        </button>
                                    @endif

                                    <div class="dropdown">
                                        <button class="btn btn-light btn-sm border shadow-sm" type="button" data-bs-toggle="dropdown">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0">
                                            <li><a class="dropdown-item" href="{{ route('pesanan.show', $p->id) }}"><i class="bi bi-eye me-2 text-info"></i> Detail Pesanan</a></li>
                                            <li><a class="dropdown-item" href="{{ route('pesanan.edit', $p->id) }}"><i class="bi bi-pencil me-2 text-warning"></i> Edit Data</a></li>
                                            
                                            <li><hr class="dropdown-divider"></li>
                                            
                                            @php
                                                $noHp = $p->customer->telepon ?? '';
                                                if (str_starts_with($noHp, '0')) { $noHp = '62' . substr($noHp, 1); }
                                                
                                                $pesanWA = "Halo *" . ($p->customer->nama ?? 'Pelanggan') . "*,\n\n";
                                                $pesanWA .= "Terima kasih telah memesan di *Gaharu App*.\n";
                                                $pesanWA .= "No. Pesanan: *" . $p->kode_pesanan . "*\n";
                                                $pesanWA .= "Total Tagihan: *Rp " . number_format($p->total_pesanan, 0, ',', '.') . "*\n";
                                                $pesanWA .= "Status Bayar: *" . $p->status_pembayaran . "*\n\n";
                                                $pesanWA .= "Lihat detail kwitansi digital Anda di sini:\n";
                                                $pesanWA .= route('pesanan.kwitansi', $p->id) . "\n\n";
                                                $pesanWA .= "Salam hangat!";
                                            @endphp
                                            <li>
                                                <a class="dropdown-item" href="https://api.whatsapp.com/send?phone={{ $noHp }}&text={{ urlencode($pesanWA) }}" target="_blank">
                                                    <i class="bi bi-whatsapp me-2 text-success"></i> Kirim Kwitansi WA
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('pesanan.kwitansi', $p->id) }}" target="_blank">
                                                    <i class="bi bi-printer me-2 text-secondary"></i> Cetak Kwitansi
                                                </a>
                                            </li>

                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form action="{{ route('pesanan.destroy', $p->id) }}" method="POST" onsubmit="return confirm('Hapus pesanan ini?')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash me-2"></i> Hapus</button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </div>

                                @if($p->status_pembayaran != 'Lunas')
                                <div class="modal fade text-start" id="modalBayar{{ $p->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <form action="{{ route('pesanan.bayar', $p->id) }}" method="POST">
                                            @csrf
                                            <div class="modal-content border-0 shadow">
                                                <div class="modal-header bg-success text-white border-0">
                                                    <h5 class="modal-title"><i class="bi bi-wallet2 me-2"></i> Pembayaran {{ $p->kode_pesanan }}</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body p-4">
                                                    @php
                                                        // Hitung yang sudah dibayar dan sisa tagihan
                                                        $sudahDibayar = $p->pembayaran->sum('jumlah_bayar') ?? 0;
                                                        $sisaTagihan = $p->total_pesanan - $sudahDibayar;

                                                        $minimalDP = $p->total_pesanan * 0.30;
                                                        $minInput = ($p->status_pembayaran == 'Belum Bayar') ? $minimalDP : 1;
                                                    @endphp

                                                    <div class="card bg-light border-0 mb-3">
                                                        <div class="card-body">
                                                            <small class="text-muted d-block">Total Tagihan:</small>
                                                            <h4 class="fw-bold text-dark">Rp {{ number_format($p->total_pesanan, 0, ',', '.') }}</h4>
                                                            @if($p->status_pembayaran == 'Belum Bayar')
                                                                <span class="text-danger small"><i class="bi bi-info-circle"></i> Minimal DP 30%: <strong>Rp {{ number_format($minimalDP, 0, ',', '.') }}</strong></span>
                                                            @endif
                                                        </div>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Jumlah Bayar (Rp)</label>
                                                        <div class="input-group">
                                                            <span class="input-group-text bg-white">Rp</span>
                                                            <input type="number" name="jumlah_bayar" class="form-control" 
                                                                   min="{{ $minInput }}" 
                                                                   max="{{ $sisaTagihan }}" 
                                                                   placeholder="Maks: {{ number_format($sisaTagihan, 0, ',', '.') }}" 
                                                                   required>
                                                        </div>
                                                        <div class="d-flex justify-content-between mt-1">
                                                            @if($p->status_pembayaran == 'Belum Bayar')
                                                                <small class="text-danger">Min. DP: Rp {{ number_format($minimalDP, 0, ',', '.') }}</small>
                                                            @else
                                                                <small class="text-muted">Sisa Tagihan: Rp {{ number_format($sisaTagihan, 0, ',', '.') }}</small>
                                                            @endif
                                                            <small class="text-muted">Maks: Rp {{ number_format($sisaTagihan, 0, ',', '.') }}</small>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label fw-semibold">Tanggal</label>
                                                            <input type="date" name="tanggal_bayar" class="form-control" value="{{ date('Y-m-d') }}" required>
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label fw-semibold">Metode</label>
                                                            <select name="metode_pembayaran" class="form-select" required>
                                                                <option value="Cash">Cash</option>
                                                                <option value="Transfer">Transfer</option>
                                                                <option value="QRIS">QRIS</option>
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <div class="mb-0">
                                                        <label class="form-label fw-semibold">Catatan</label>
                                                        <textarea name="catatan" class="form-control" rows="2" placeholder="Nama bank / pengirim..."></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer border-0 p-4 pt-0">
                                                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-success px-4 shadow-sm">Simpan Pembayaran</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Belum ada data pesanan
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    .table thead th { font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: none; }
    .table tbody td { border-bottom-color: #f8f9fa; }
    .badge { font-weight: 500; padding: 0.5em 1em; }
    .text-warning-dark { color: #856404; }
    .dropdown-item { padding: 0.6rem 1.2rem; font-size: 0.9rem; }
    .dropdown-item i { width: 20px; }
    .btn-success-subtle { background-color: #d1e7dd; color: #0f5132; }
    .bg-light { background-color: #f8f9fa !important; }
</style>
</x-app-layout>