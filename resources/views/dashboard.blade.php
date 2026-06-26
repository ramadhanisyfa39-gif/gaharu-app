<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center py-0.5">
            <div>
                <h4 class="fw-bold m-0" style="color:#9c4f18; font-size: 1.25rem;">Dashboard Operasional</h4>
                <small class="text-muted" style="font-size: 0.7rem;">Ringkasan aktivitas ERP Gaharu</small>
            </div>
        </div>
    </x-slot>

    <div class="container-fluid px-3 py-2">

        {{-- BARIS 1: MINI SUMMARY FINANSIAL --}}
        <div class="row gx-3 mb-2">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm" style="border-left: 3px solid #9c4f18 !important; background: #fff;">
                    <div class="card-body p-2 px-3">
                        <div class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">Nilai Inventory Saat Ini</div>
                        <h4 class="fw-bold m-0" style="color:#9c4f18; font-size: 1.2rem;">Rp {{ number_format($inventoryValue, 0, ',', '.') }}</h4>
                        <small class="text-muted opacity-75" style="font-size: 0.65rem;">Berdasarkan FIFO Batch</small>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card border-0 shadow-sm" style="border-left: 3px solid #d88656 !important; background: #fff;">
                    <div class="card-body p-2 px-3">
                        <div class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">Pembelian Bulan Ini</div>
                        <h4 class="fw-bold m-0" style="color:#d88656; font-size: 1.2rem;">Rp {{ number_format($pembelianBulanIni, 0, ',', '.') }}</h4>
                        <small class="text-muted" style="font-size: 0.65rem;">Total transaksi bulan berjalan</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- BARIS 2: UTAMA (GRAFIK & TABEL DI KIRI, STOK KRITIS DI KANAN) --}}
        <div class="row gx-3">
            
            {{-- SEKTOR KIRI: GRAFIK & DATA INSIGHT (COL-LG-8) --}}
            <div class="col-lg-8 d-flex flex-column gap-2">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-2.5">
                        <h6 class="fw-bold mb-1" style="color:#9c4f18; font-size: 0.8rem;">Tren Pembelian 7 Hari Terakhir</h6>
                        <div style="position: relative; height: 135px; width:100%;">
                            <canvas id="grafikPembelian"></canvas>
                        </div>
                    </div>
                </div>

                <div class="row gx-2">
                    <div class="col-sm-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body p-2.5">
                                <h6 class="fw-bold mb-1.5 text-dark" style="font-size: 0.8rem;">📦 Kuantitas Bahan Terbanyak (Top 3)</h6>
                                <div class="table-responsive rounded border">
                                    <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.72rem;">
                                        <thead style="background-color: #f8f9fa !important; border-bottom: 1px solid #dee2e6;">
                                            <tr>
                                                <th class="ps-2 py-1.5 fw-bold text-secondary" style="background: none; border: none;">Nama Bahan</th>
                                                <th class="pe-2 py-1.5 fw-bold text-secondary text-end" style="background: none; border: none;">Volume</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse((($bahanSeringDibeli ?? [])->take(3)) as $bahan)
                                                <tr>
                                                    <td class="ps-2 py-1 fw-medium text-dark text-truncate" style="max-width: 130px;">{{ $bahan->nama }}</td>
                                                    <td class="pe-2 py-1 text-end fw-bold text-dark">
                                                        {{ number_format($bahan->total_qty, 0, ',', '.') }} <span class="text-muted fw-normal" style="font-size: 0.65rem;">{{ $bahan->satuan }}</span>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="2" class="text-center py-2 text-muted" style="font-size: 0.7rem;">Tidak ada data.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body p-2.5">
                                <h6 class="fw-bold mb-1.5 text-dark" style="font-size: 0.8rem;">🏆 Supplier Teratas (Top 3)</h6>
                                <div class="table-responsive rounded border">
                                    <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.72rem;">
                                        <thead style="background-color: #f8f9fa !important; border-bottom: 1px solid #dee2e6;">
                                            <tr>
                                                <th class="ps-2 py-1.5 fw-bold text-secondary" style="background: none; border: none;">Supplier</th>
                                                <th class="pe-2 py-1.5 fw-bold text-secondary text-end" style="background: none; border: none;">Nominal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse((($supplierTeratas ?? [])->take(3)) as $supplier)
                                                <tr>
                                                    <td class="ps-2 py-1 fw-medium text-dark text-truncate" style="max-width: 120px;">{{ $supplier->nama }}</td>
                                                    <td class="pe-2 py-1 text-end fw-bold text-success">
                                                        Rp {{ number_format($supplier->total_nominal, 0, ',', '.') }}
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="2" class="text-center py-2 text-muted" style="font-size: 0.7rem;">Tidak ada data.</td>
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

            {{-- SEKTOR KANAN: BARANG HAMPIR HABIS VERTIKAL (COL-LG-4) --}}
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100 d-flex flex-column justify-content-between">
                    <div class="card-body p-2.5">
                        <div class="d-flex justify-content-between align-items-center mb-1.5">
                            <h6 class="fw-bold m-0" style="color:#dc3545; font-size: 0.8rem;">
                                ⚠ Stok Kritis (Maksimal 5)
                            </h6>
                            <span class="badge bg-danger rounded-pill" style="font-size: 0.65rem;">Penting</span>
                        </div>

                        <div class="list-group list-group-flush border rounded border-bottom-0">
                            @forelse(($barangHampirHabis ?? []) as $item)
                                <div class="d-flex justify-content-between align-items-center p-2 border-bottom bg-white">
                                    <span class="text-dark fw-medium text-truncate small" style="max-width: 170px;" title="{{ $item->nama }}">
                                        {{ $item->nama }}
                                    </span>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 font-monospace fw-bold" style="font-size: 0.7rem;">
                                        {{ number_format($item->jumlah, 0) }} {{ $item->satuan }}
                                    </span>
                                </div>
                            @empty
                                <div class="text-center py-4 text-success fw-medium small bg-white border-bottom">
                                    ✨ Semua stok barang aman
                                </div>
                            @endforelse
                        </div>
                    </div>
                    
                    <div class="card-footer bg-transparent border-0 p-2.5 pt-0">
                        <a href="{{ url('/pembelian') }}" class="btn w-100 text-white fw-semibold py-1.5 transition shadow-sm d-flex align-items-center justify-content-center gap-1" style="background-color: #9c4f18; border-radius: 6px; font-size: 0.75rem;">
                            <i class="bi bi-cart-plus"></i> Buat Pesanan Pembelian (Restock)
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- SCRIPTS GRAPH --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('grafikPembelian');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: @json($labelsPembelian),
                datasets: [{
                    data: @json($dataPembelian),
                    borderColor: '#d88656',
                    backgroundColor: 'rgba(216,134,86,0.05)',
                    tension: 0.3,
                    fill: true,
                    borderWidth: 2,
                    pointRadius: 2.5,
                    pointBackgroundColor: '#d88656'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
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
    </script>
</x-app-layout>