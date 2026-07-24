<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center py-0.5">
            <div>
                <h4 class="fw-bold m-0" style="color:#9c4f18; font-size: 1.25rem;">Dashboard Produksi</h4>
                <small class="text-muted" style="font-size: 0.7rem;">Ringkasan aktivitas manufaktur & work order CV Gaharu</small>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('laporan.produksi.dashboard', ['format' => 'excel']) }}" class="btn btn-success btn-sm fw-bold">
                    📊 Export WO Excel
                </a>
                <a href="{{ route('laporan.produksi.dashboard', ['format' => 'pdf']) }}" class="btn btn-danger btn-sm fw-bold">
                    📕 Export WO PDF
                </a>
            </div>
        </div>
    </x-slot>

    <div class="container-fluid px-3 py-2">

        {{-- FILTER TANGGAL PERIODE --}}
        <div class="card border-0 shadow-sm mb-3" style="background: #fff; border-radius: 8px;">
            <div class="card-body p-2 px-3">
                <form method="GET" action="{{ route('laporan.produksi.dashboard') }}" class="row g-2 align-items-center">
                    <div class="col-auto d-flex align-items-center gap-1 text-secondary fw-bold" style="font-size: 0.75rem;">
                        <i class="bi bi-funnel-fill text-primary"></i> Filter Periode Produksi:
                    </div>
                    <div class="col-auto d-flex align-items-center gap-1">
                        <label for="tgl_mulai" class="form-label m-0 text-muted" style="font-size: 0.7rem;">Dari:</label>
                        <input type="date" id="tgl_mulai" name="tgl_mulai" class="form-control form-control-sm" value="{{ $startDate }}" style="font-size: 0.75rem; width: 140px;">
                    </div>
                    <div class="col-auto d-flex align-items-center gap-1">
                        <label for="tgl_selesai" class="form-label m-0 text-muted" style="font-size: 0.7rem;">Sampai:</label>
                        <input type="date" id="tgl_selesai" name="tgl_selesai" class="form-control form-control-sm" value="{{ $endDate }}" style="font-size: 0.75rem; width: 140px;">
                    </div>
                    <div class="col-auto d-flex gap-1">
                        <button type="submit" class="btn btn-sm text-white fw-bold" style="background:#9c4f18; font-size: 0.75rem; padding: 4px 12px;">
                            <i class="bi bi-search me-1"></i> Terapkan
                        </button>
                        <a href="{{ route('laporan.produksi.dashboard') }}" class="btn btn-sm btn-outline-secondary" style="font-size: 0.75rem; padding: 4px 10px;" title="Reset Filter">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- BARIS 1: MINI SUMMARY CARDS --}}
        <div class="row gx-3 mb-3">
            {{-- Card 1: Work Order Aktif --}}
            <div class="col-md-3 col-sm-6 mb-2 mb-md-0">
                <div class="card border-0 shadow-sm" style="border-left: 3px solid #0d6efd !important; background: #fff;">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">Work Order Aktif</div>
                                <h3 class="fw-bold m-0 mt-1 text-dark" style="font-size: 1.5rem;">{{ $woAktif }}</h3>
                            </div>
                            <div class="bg-primary-subtle text-primary rounded p-2" style="font-size: 1.25rem; line-height: 1;">
                                <i class="bi bi-file-earmark-text"></i>
                            </div>
                        </div>
                        <small class="text-muted" style="font-size: 0.65rem;">Sedang diproses di pabrik</small>
                    </div>
                </div>
            </div>

            {{-- Card 2: Produksi Selesai Tahun Ini --}}
            <div class="col-md-3 col-sm-6 mb-2 mb-md-0">
                <div class="card border-0 shadow-sm" style="border-left: 3px solid #198754 !important; background: #fff;">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">Selesai Tahun Ini</div>
                                <h3 class="fw-bold m-0 mt-1 text-dark" style="font-size: 1.5rem;">{{ $produksiSelesaiTahunIni }}</h3>
                            </div>
                            <div class="bg-success-subtle text-success rounded p-2" style="font-size: 1.25rem; line-height: 1;">
                                <i class="bi bi-check-circle"></i>
                            </div>
                        </div>
                        <small class="text-muted" style="font-size: 0.65rem;">Tahun berjalan ({{ date('Y') }})</small>
                    </div>
                </div>
            </div>

            {{-- Card 3: Total Qty Hasil --}}
            <div class="col-md-3 col-sm-6 mb-2 mb-md-0">
                <div class="card border-0 shadow-sm" style="border-left: 3px solid #0dcaf0 !important; background: #fff;">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">Total Qty Hasil</div>
                                <h3 class="fw-bold m-0 mt-1 text-dark" style="font-size: 1.5rem;">{{ number_format($totalQtyHasil, 0, ',', '.') }}</h3>
                            </div>
                            <div class="bg-info-subtle text-info rounded p-2" style="font-size: 1.25rem; line-height: 1;">
                                <i class="bi bi-box-seam"></i>
                            </div>
                        </div>
                        <small class="text-muted" style="font-size: 0.65rem;">Unit barang jadi diproduksi</small>
                    </div>
                </div>
            </div>

            {{-- Card 4: Rata-rata Capaian Target --}}
            <div class="col-md-3 col-sm-6">
                <div class="card border-0 shadow-sm" style="border-left: 3px solid #ffc107 !important; background: #fff;">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">Capaian Target</div>
                                <h3 class="fw-bold m-0 mt-1 text-dark" style="font-size: 1.5rem;">{{ number_format($rataRataCapaian, 1) }}%</h3>
                            </div>
                            <div class="bg-warning-subtle text-warning rounded p-2" style="font-size: 1.25rem; line-height: 1;">
                                <i class="bi bi-graph-up-arrow"></i>
                            </div>
                        </div>
                        <small class="text-muted" style="font-size: 0.65rem;">Rata-rata realisasi WO aktif</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- BARIS 2: GRAFIK TREN PRODUKSI 7 HARI TERAKHIR --}}
        <div class="row gx-3 mb-3">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-3">
                        <h6 class="fw-bold mb-2 text-dark" style="font-size: 0.85rem; color:#9c4f18 !important;">Tren Kuantitas Hasil Produksi (7 Hari Terakhir)</h6>
                        <div style="position: relative; height: 180px; width:100%;">
                            <canvas id="grafikProduksi"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- BARIS 3: TABEL WORK ORDER & BAHAN BAKU KRITIS --}}
        <div class="row gx-3">
            {{-- Sektor Kiri: Status Work Order Terakhir (col-lg-8) --}}
            <div class="col-lg-8 mb-3 mb-lg-0">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3">
                        <h6 class="fw-bold mb-2 text-dark" style="font-size: 0.85rem;">📋 Status Work Order Terbaru</h6>
                        <div class="table-responsive rounded border">
                            <table class="table table-hover align-middle mb-0" style="font-size: 0.75rem;">
                                <thead class="table-light text-secondary small text-uppercase">
                                    <tr>
                                        <th class="ps-3 py-2 fw-bold">Kode WO / Tanggal</th>
                                        <th class="py-2 fw-bold">Pembuat</th>
                                        <th class="py-2 fw-bold text-center">Progress Realisasi</th>
                                        <th class="py-2 fw-bold text-center">Status</th>
                                        <th class="pe-3 py-2 fw-bold text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($workOrderStatus as $wo)
                                        <tr>
                                            <td class="ps-3 py-2">
                                                <span class="fw-bold text-dark font-monospace">{{ $wo->kode_wo }}</span>
                                                <div class="text-muted" style="font-size: 0.65rem;">{{ date('d-m-Y', strtotime($wo->tanggal_wo)) }}</div>
                                            </td>
                                            <td class="py-2 text-dark">{{ $wo->pembuat->nama ?? 'Sistem' }}</td>
                                            <td class="py-2">
                                                <div class="d-flex align-items-center justify-content-center gap-2">
                                                    <div class="progress w-100" style="height: 6px; max-width: 120px; border-radius: 4px;">
                                                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $wo->persentase }}%" aria-valuenow="{{ $wo->persentase }}" aria-valuemin="0" aria-valuemax="100"></div>
                                                    </div>
                                                    <span class="font-monospace fw-bold text-dark" style="font-size: 0.7rem;">
                                                        {{ number_format($wo->total_realisasi, 0) }}/{{ number_format($wo->total_rencana, 0) }} ({{ number_format($wo->persentase, 0) }}%)
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="py-2 text-center">
                                                @if($wo->status_wo == 'Selesai')
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1">Selesai</span>
                                                @elseif($wo->status_wo == 'Diproses')
                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1">Diproses</span>
                                                @else
                                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2.5 py-1">Draft</span>
                                                @endif
                                            </td>
                                            <td class="pe-3 py-2 text-end">
                                                <a href="{{ route('wo.show', $wo->id) }}" class="btn btn-outline-secondary btn-sm py-0.5 px-2" style="font-size: 0.65rem;">Detail</a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">Tidak ada data Work Order.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sektor Kanan: Bahan Baku Kritis & Produk Teratas (col-lg-4) --}}
            <div class="col-lg-4 d-flex flex-column gap-3">
                {{-- 1. Bahan Baku Masuk Batas Minimum --}}
                <div class="card border-0 shadow-sm flex-fill">
                    <div class="card-body p-3">
                        <h6 class="fw-bold mb-2 text-danger" style="font-size: 0.85rem;">
                            <i class="bi bi-exclamation-triangle"></i> Bahan Baku Batas Minimum
                        </h6>
                        <div class="table-responsive rounded border" style="max-height: 180px; overflow-y: auto;">
                            <table class="table table-hover align-middle mb-0" style="font-size: 0.72rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-2 py-1.5 fw-bold text-secondary">Bahan Baku</th>
                                        <th class="py-1.5 fw-bold text-secondary text-end">Min</th>
                                        <th class="pe-2 py-1.5 fw-bold text-secondary text-end">Stok Rill</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($bahanBakuMinimum as $item)
                                        <tr>
                                            <td class="ps-2 py-1.5 text-dark fw-medium text-truncate" style="max-width: 140px;" title="{{ $item->nama }}">{{ $item->nama }}</td>
                                            <td class="py-1.5 text-end text-muted">{{ number_format($item->minimum_stock, 0) }} {{ $item->satuan }}</td>
                                            <td class="pe-2 py-1.5 text-end">
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold font-monospace">
                                                    {{ number_format($item->total_stok, 0) }} {{ $item->satuan }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center py-3 text-success fw-medium">
                                                ✨ Semua stok bahan baku aman
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- 2. Produk Teratas Diproduksi --}}
                <div class="card border-0 shadow-sm flex-fill">
                    <div class="card-body p-3">
                        <h6 class="fw-bold mb-2 text-dark" style="font-size: 0.85rem;">🏆 Produk Teratas Diproduksi</h6>
                        <div class="table-responsive rounded border" style="max-height: 180px; overflow-y: auto;">
                            <table class="table table-hover align-middle mb-0" style="font-size: 0.72rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-2 py-1.5 fw-bold text-secondary">Nama Produk Jadi</th>
                                        <th class="pe-2 py-1.5 fw-bold text-secondary text-end">Total Produksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($produkTeratas as $produk)
                                        <tr>
                                            <td class="ps-2 py-1.5 text-dark fw-medium text-truncate" style="max-width: 170px;" title="{{ $produk->nama }}">{{ $produk->nama }}</td>
                                            <td class="pe-2 py-1.5 text-end fw-bold text-dark">
                                                {{ number_format($produk->total_qty, 0, ',', '.') }} <span class="text-muted fw-normal" style="font-size: 0.65rem;">{{ $produk->satuan }}</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="text-center py-3 text-muted">Belum ada riwayat produksi disetujui.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- SCRIPTS GRAPH --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('grafikProduksi');
            if (ctx) {
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: @json($labelsProduksi),
                        datasets: [{
                            label: 'Quantity Produksi',
                            data: @json($dataProduksi),
                            borderColor: '#0d6efd',
                            backgroundColor: 'rgba(13, 110, 253, 0.75)',
                            borderRadius: 4,
                            borderWidth: 1,
                            barThickness: 24
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { 
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.parsed.y.toLocaleString('id-ID') + ' unit';
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { font: { size: 9.5 } },
                                grid: { color: 'rgba(0, 0, 0, 0.03)' }
                            },
                            x: {
                                ticks: { font: { size: 9.5 } },
                                grid: { display: false }
                            }
                        }
                    }
                });
            }
        });
    </script>
</x-app-layout>
