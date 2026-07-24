<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center py-0.5">
            <div>
                <h4 class="fw-bold m-0" style="color:#1a5c32; font-size: 1.25rem;">Dashboard Keuangan</h4>
                <small class="text-muted" style="font-size: 0.7rem;">Ringkasan pelaporan dan metrik finansial CV Gaharu Agung Sejahtera</small>
            </div>
            <a href="{{ route('dashboard') }}" class="btn text-white fw-bold d-flex align-items-center gap-1.5 transition" style="background-color: #1a5c32; border-radius: 6px; font-size: 0.75rem; padding: 6px 12px;">
                <i class="bi bi-speedometer2"></i> Buka Dashboard Operasional
            </a>
        </div>
    </x-slot>

    <div class="container-fluid px-3 py-2">

        {{-- FILTER TANGGAL PERIODE --}}
        <div class="card border-0 shadow-sm mb-3" style="background: #fff; border-radius: 8px;">
            <div class="card-body p-2 px-3">
                <form method="GET" action="{{ route('dashboard.keuangan') }}" class="row g-2 align-items-center">
                    <div class="col-auto d-flex align-items-center gap-1 text-secondary fw-bold" style="font-size: 0.75rem;">
                        <i class="bi bi-funnel-fill text-success"></i> Filter Periode Keuangan:
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
                        <button type="submit" class="btn btn-sm text-white fw-bold" style="background:#1a5c32; font-size: 0.75rem; padding: 4px 12px;">
                            <i class="bi bi-search me-1"></i> Terapkan
                        </button>
                        <a href="{{ route('dashboard.keuangan') }}" class="btn btn-sm btn-outline-secondary" style="font-size: 0.75rem; padding: 4px 10px;" title="Reset Filter">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- BARIS 1: FINANCIAL CARD SUMMARY --}}
        <div class="row gx-3 mb-3">
            <div class="col-md-3 mb-2 mb-md-0">
                <div class="card border-0 shadow-sm" style="border-left: 3px solid #1a5c32 !important; background: #fff;">
                    <div class="card-body p-2 px-3">
                        <div class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">Total Aset</div>
                        <h4 class="fw-bold m-0" style="color:#1a5c32; font-size: 1.2rem;">Rp {{ number_format($totalAssets, 0, ',', '.') }}</h4>
                        <small class="text-muted" style="font-size: 0.65rem;">Total Aktiva (Debit - Kredit)</small>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-2 mb-md-0">
                <div class="card border-0 shadow-sm" style="border-left: 3px solid #dc3545 !important; background: #fff;">
                    <div class="card-body p-2 px-3">
                        <div class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">Total Kewajiban (Utang)</div>
                        <h4 class="fw-bold m-0" style="color:#dc3545; font-size: 1.2rem;">Rp {{ number_format($totalLiabilities, 0, ',', '.') }}</h4>
                        <small class="text-muted" style="font-size: 0.65rem;">Pasiva Kewajiban (Kredit - Debit)</small>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-2 mb-md-0">
                <div class="card border-0 shadow-sm" style="border-left: 3px solid #007bff !important; background: #fff;">
                    <div class="card-body p-2 px-3">
                        <div class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">Total Ekuitas (Modal)</div>
                        <h4 class="fw-bold m-0" style="color:#007bff; font-size: 1.2rem;">Rp {{ number_format($totalEquity, 0, ',', '.') }}</h4>
                        <small class="text-muted" style="font-size: 0.65rem;">Modal Pemilik (Kredit - Debit)</small>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                @php
                    $latestNet = count($incomeData) > 0 ? (end($incomeData) - end($expenseData)) : 0;
                    $netColor = $latestNet >= 0 ? '#28a745' : '#dc3545';
                    $netBorder = $latestNet >= 0 ? '3px solid #28a745' : '3px solid #dc3545';
                @endphp
                <div class="card border-0 shadow-sm" style="border-left: {{ $netBorder }} !important; background: #fff;">
                    <div class="card-body p-2 px-3">
                        <div class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">Laba Bersih Bulan Ini</div>
                        <h4 class="fw-bold m-0" style="color:{{ $netColor }}; font-size: 1.2rem;">Rp {{ number_format($latestNet, 0, ',', '.') }}</h4>
                        <small class="text-muted" style="font-size: 0.65rem;">Pendapatan dikurangi Beban</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- BARIS 2: P&L CHART & CASH BALANCES --}}
        <div class="row gx-3 mb-3">
            {{-- CHART LABA RUGI --}}
            <div class="col-lg-8 mb-3 mb-lg-0">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3">
                        <h6 class="fw-bold mb-1" style="color:#1a5c32; font-size: 0.8rem;">📊 Tren Laba / Rugi (6 Bulan Terakhir)</h6>
                        <div style="position: relative; height: 220px; width:100%;">
                            <canvas id="grafikLabaRugi"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            {{-- DAFTAR KAS & BANK --}}
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3">
                        <h6 class="fw-bold mb-2 text-dark" style="font-size: 0.8rem;">🏦 Saldo Kas & Bank Utama</h6>
                        <div class="list-group list-group-flush border rounded" style="max-height: 220px; overflow-y: auto;">
                            @forelse($balances as $bal)
                                <div class="d-flex justify-content-between align-items-center p-2.5 border-bottom bg-white">
                                    <div>
                                        <div class="fw-bold text-dark" style="font-size: 0.72rem;">{{ $bal['nama'] }}</div>
                                        <small class="text-muted font-monospace" style="font-size: 0.6rem;">{{ $bal['kode'] }}</small>
                                    </div>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 font-monospace fw-bold" style="font-size: 0.7rem;">
                                        Rp {{ number_format($bal['saldo'], 0, ',', '.') }}
                                    </span>
                                </div>
                            @empty
                                <div class="text-center py-4 text-muted small bg-white">
                                    Tidak ada data kas/bank ditemukan.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- BARIS 3: RECENT JOURNALS & ADJUSTMENTS --}}
        <div class="row gx-3">
            {{-- JURNAL UMUM TERBARU --}}
            <div class="col-md-6 mb-3 mb-md-0">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold m-0 text-dark" style="font-size: 0.8rem;">📝 Jurnal Umum Terbaru</h6>
                            <a href="{{ url('/jurnal') }}" class="text-decoration-none fw-bold" style="color: #1a5c32; font-size: 0.7rem;">Lihat Semua</a>
                        </div>
                        <div class="table-responsive rounded border">
                            <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.7rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-2 py-1.5 text-secondary">Tanggal</th>
                                        <th class="py-1.5 text-secondary">No. Ref</th>
                                        <th class="py-1.5 text-secondary">Deskripsi</th>
                                        <th class="pe-2 py-1.5 text-secondary text-end">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recentJournals as $jr)
                                        <tr>
                                            <td class="ps-2 py-1.5 font-monospace">{{ \Carbon\Carbon::parse($jr->tanggal)->format('d-m-Y') }}</td>
                                            <td class="py-1.5 fw-bold">{{ $jr->no_ref }}</td>
                                            <td class="py-1.5 text-truncate" style="max-width: 150px;">{{ $jr->deskripsi }}</td>
                                            <td class="pe-2 py-1.5 text-end">
                                                <span class="badge {{ $jr->status === 'posted' ? 'bg-success' : 'bg-secondary' }}" style="font-size: 0.6rem;">
                                                    {{ strtoupper($jr->status) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center py-3 text-muted">Belum ada jurnal tercatat.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- JURNAL PENYESUAIAN TERBARU --}}
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold m-0 text-dark" style="font-size: 0.8rem;">🔧 Jurnal Penyesuaian Terbaru</h6>
                            <a href="{{ route('adjustment.index') }}" class="text-decoration-none fw-bold" style="color: #1a5c32; font-size: 0.7rem;">Lihat Semua</a>
                        </div>
                        <div class="table-responsive rounded border">
                            <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.7rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-2 py-1.5 text-secondary">Tanggal</th>
                                        <th class="py-1.5 text-secondary">No. Ref</th>
                                        <th class="py-1.5 text-secondary">Deskripsi</th>
                                        <th class="pe-2 py-1.5 text-secondary text-end">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recentAdjustments as $adj)
                                        <tr>
                                            <td class="ps-2 py-1.5 font-monospace">{{ \Carbon\Carbon::parse($adj->tanggal)->format('d-m-Y') }}</td>
                                            <td class="py-1.5 fw-bold">{{ $adj->no_ref }}</td>
                                            <td class="py-1.5 text-truncate" style="max-width: 150px;">{{ $adj->deskripsi }}</td>
                                            <td class="pe-2 py-1.5 text-end">
                                                <span class="badge {{ $adj->status === 'posted' ? 'bg-success' : 'bg-secondary' }}" style="font-size: 0.6rem;">
                                                    {{ strtoupper($adj->status) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center py-3 text-muted">Belum ada jurnal penyesuaian.</td>
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
        const ctxPL = document.getElementById('grafikLabaRugi');
        new Chart(ctxPL, {
            type: 'bar',
            data: {
                labels: @json($months),
                datasets: [
                    {
                        label: 'Pendapatan',
                        data: @json($incomeData),
                        backgroundColor: '#28a745',
                        borderRadius: 4,
                    },
                    {
                        label: 'Beban / Pengeluaran',
                        data: @json($expenseData),
                        backgroundColor: '#dc3545',
                        borderRadius: 4,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { boxWidth: 12, font: { size: 9.5 } }
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
    </script>
</x-app-layout>
