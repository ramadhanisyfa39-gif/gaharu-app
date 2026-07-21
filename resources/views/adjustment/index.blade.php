<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Riwayat Jurnal Penyesuaian
        </h2>
    </x-slot>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold m-0" style="color: #9c4f18; font-size: 1.5rem;">Riwayat Jurnal Penyesuaian</h2>
            <a href="{{ route('adjustment.create') }}" class="btn btn-primary shadow-sm" style="background-color: #9c4f18; border-color: #9c4f18;">
                <i class="fas fa-plus me-1"></i> Tambah Jurnal
            </a>
        </div>

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        {{-- BATCH POSTING CARD (POSTING MASSAL) --}}
        <div class="card mb-4 border-0 shadow-sm bg-light">
            <div class="card-body">
                <h5 class="fw-bold mb-3" style="color: #9c4f18;"><i class="fas fa-mail-bulk me-2 text-primary"></i> Posting Jurnal Massal</h5>
                <form action="{{ route('adjustment.approve_batch') }}" method="POST" class="row g-3 align-items-end" onsubmit="return confirm('Posting semua jurnal penyesuaian draft pada rentang tanggal ini ke Buku Besar?')">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-muted">Dari Tanggal</label>
                        <input type="date" name="start_date" class="form-control" required value="{{ request('start_date', date('Y-m-01')) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-muted">Sampai Tanggal</label>
                        <input type="date" name="end_date" class="form-control" required value="{{ request('end_date', date('Y-m-t')) }}">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-success w-100 shadow-sm">
                            <i class="fas fa-check-double me-1"></i> Posting Terpilih
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- TABLE CARD --}}
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40px;"></th>
                                <th class="ps-2">Tanggal</th>
                                <th>No. Ref</th>
                                <th>Deskripsi</th>
                                <th class="text-end">Total Debit</th>
                                <th class="text-end">Total Kredit</th>
                                <th class="text-center">Status</th>
                                <th class="text-center pe-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($adjustments as $index => $item)
                                @php
                                    $isOtomatis = in_array($item->source_type, ['pengeluaran_bahan_baku', 'stock_opname']);
                                    $totalDebit = $item->details->sum('debit');
                                    $totalKredit = $item->details->sum('kredit');
                                    $targetCollapseId = "detail-ajp-" . $item->id;
                                @endphp
                                <tr>
                                    <td class="text-center ps-3">
                                        <button class="btn btn-sm btn-outline-secondary py-0 px-1" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $targetCollapseId }}" aria-expanded="false" title="Lihat Rincian Akun">
                                            <i class="fas fa-chevron-down"></i>
                                        </button>
                                    </td>
                                    <td class="ps-2 fw-semibold">{{ \Carbon\Carbon::parse($item->tanggal)->format('d/m/Y') }}</td>
                                    <td>
                                        <span class="badge bg-light text-dark border font-monospace">{{ $item->no_ref }}</span>
                                        @if($item->source_type === 'stock_opname')
                                            <div class="mt-1">
                                                <a href="{{ route('stock-opname.show', $item->source_id) }}" 
                                                   class="badge text-decoration-none bg-info-subtle text-info border border-info-subtle small shadow-sm" 
                                                   target="_blank" 
                                                   title="Klik untuk melihat dokumen Stock Opname asli">
                                                    <i class="fas fa-boxes me-1"></i> Stock Opname (Surplus)
                                                </a>
                                            </div>
                                        @elseif($item->source_type === 'pengeluaran_bahan_baku')
                                            <div class="mt-1">
                                                <a href="{{ route('pengeluaran-bahan-baku.show', $item->source_id) }}" 
                                                   class="badge text-decoration-none bg-warning-subtle text-warning border border-warning-subtle small shadow-sm" 
                                                   target="_blank" 
                                                   title="Klik untuk melihat dokumen Pengeluaran Bahan Baku asli">
                                                    <i class="fas fa-boxes me-1"></i> Stock Opname (Shortage)
                                                </a>
                                            </div>
                                        @endif
                                    </td>
                                    <td>{{ Str::limit($item->deskripsi, 60) }}</td>
                                    <td class="text-end fw-semibold text-success">
                                        Rp {{ number_format($totalDebit, 0, ',', '.') }}
                                    </td>
                                    <td class="text-end fw-semibold text-danger">
                                        Rp {{ number_format($totalKredit, 0, ',', '.') }}
                                    </td>
                                    <td class="text-center">
                                        @if($item->status === 'approved')
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="fas fa-check-circle me-1"></i>Posted</span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1"><i class="fas fa-clock me-1"></i>Draft</span>
                                        @endif
                                    </td>
                                    <td class="text-center pe-3">
                                        @if($item->status !== 'approved' && !$isOtomatis)
                                            <form action="{{ route('adjustment.approve', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Posting jurnal penyesuaian ini ke Buku Besar?')">
                                                @csrf
                                                @method('PUT')
                                                <button type="submit" class="btn btn-sm btn-success py-0.5 px-2" style="font-size: 0.75rem;">
                                                    <i class="fas fa-check me-1"></i> Post
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                </tr>
                                {{-- EXPANDABLE ACCORDION DETAIL ROW --}}
                                <tr class="collapse bg-light" id="{{ $targetCollapseId }}">
                                    <td colspan="8" class="p-3">
                                        <div class="card border border-secondary-subtle shadow-sm">
                                            <div class="card-header bg-white py-2 fw-bold small text-muted">
                                                <i class="fas fa-list-alt me-1 text-primary"></i> Rincian Akun Jurnal Penyesuaian ({{ $item->no_ref }})
                                            </div>
                                            <div class="card-body p-0">
                                                <table class="table table-sm table-bordered mb-0 small">
                                                    <thead class="table-secondary">
                                                        <tr>
                                                            <th class="ps-3">Kode Akun</th>
                                                            <th>Nama Akun (COA)</th>
                                                            <th class="text-end">Debit (Rp)</th>
                                                            <th class="text-end pe-3">Kredit (Rp)</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($item->details as $d)
                                                        <tr>
                                                            <td class="ps-3 font-monospace fw-bold text-secondary">{{ $d->chart_of_accounts->kode ?? '-' }}</td>
                                                            <td class="fw-semibold text-dark">{{ $d->chart_of_accounts->nama ?? 'Akun #' . $d->account_id }}</td>
                                                            <td class="text-end text-success fw-bold">
                                                                {{ $d->debit > 0 ? number_format($d->debit, 0, ',', '.') : '-' }}
                                                            </td>
                                                            <td class="text-end pe-3 text-danger fw-bold">
                                                                {{ $d->kredit > 0 ? number_format($d->kredit, 0, ',', '.') : '-' }}
                                                            </td>
                                                        </tr>
                                                        @endforeach
                                                    </tbody>
                                                    <tfoot class="table-light fw-bold">
                                                        <tr>
                                                            <td colspan="2" class="text-end pe-3">TOTAL</td>
                                                            <td class="text-end text-success">Rp {{ number_format($totalDebit, 0, ',', '.') }}</td>
                                                            <td class="text-end pe-3 text-danger">Rp {{ number_format($totalKredit, 0, ',', '.') }}</td>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">Belum ada transaksi jurnal penyesuaian yang tercatat.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>