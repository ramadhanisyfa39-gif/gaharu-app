<x-app-layout>
    <x-slot name="header">Laporan Stock Opname</x-slot>

    <div class="container-fluid">

        {{-- ── FILTER ── --}}
        <div class="card mb-4 shadow-sm">
            <div class="card-body">
                <form method="GET" action="{{ route('laporan.stock-opname') }}" class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label fw-semibold text-muted" style="font-size:12px;">DARI TANGGAL</label>
                        <input type="date" name="dari" class="form-control" value="{{ request('dari') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold text-muted" style="font-size:12px;">SAMPAI TANGGAL</label>
                        <input type="date" name="sampai" class="form-control" value="{{ request('sampai') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold text-muted" style="font-size:12px;">GUDANG</label>
                        <select name="gudang_id" class="form-select">
                            <option value="">Semua Gudang</option>
                            @foreach($gudangs as $g)
                                <option value="{{ $g->id }}" {{ request('gudang_id') == $g->id ? 'selected' : '' }}>{{ $g->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold text-muted" style="font-size:12px;">STATUS</label>
                        <select name="status" class="form-select">
                            <option value="">Semua Status</option>
                            <option value="draft"    {{ request('status') === 'draft'    ? 'selected' : '' }}>Draft</option>
                            <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn text-white px-4" style="background-color: #d88656; border: none;">
                            <i class="bi bi-search me-1"></i> Tampilkan
                        </button>
                        <a href="{{ route('laporan.stock-opname', array_merge(request()->all(), ['format'=>'excel'])) }}"
                           class="btn text-white" style="background-color: #606060; border: none;">
                            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Excel
                        </a>
                        <a href="{{ route('laporan.stock-opname', array_merge(request()->all(), ['format'=>'pdf'])) }}"
                           class="btn text-white" style="background-color: #606060; border: none;">
                            <i class="bi bi-file-earmark-pdf me-1"></i> PDF
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- ── SUMMARY CARDS ── --}}
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card h-100 border-0 shadow-sm" style="background:#d88656; color:white;">
                    <div class="card-body">
                        <div style="font-size:11px; opacity:.9; text-transform:uppercase; letter-spacing:1px;">Total Opname</div>
                        <div class="fw-bold mt-1" style="font-size:28px;">{{ $totalOpname }}</div>
                        <div style="font-size:12px; opacity:.9;">dokumen opname</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100 border-0 shadow-sm" style="background:#d1e7dd;">
                    <div class="card-body">
                        <div style="font-size:11px; color:#0a3622; text-transform:uppercase; letter-spacing:1px;">Sudah Approved</div>
                        <div class="fw-bold mt-1" style="font-size:28px; color:#0a3622;">{{ $totalApproved }}</div>
                        <div style="font-size:12px; color:#0a3622;">dokumen final</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100 border-0 shadow-sm" style="background:#fff3cd;">
                    <div class="card-body">
                        <div style="font-size:11px; color:#856404; text-transform:uppercase; letter-spacing:1px;">Total Selisih Qty</div>
                        <div class="fw-bold mt-1" style="font-size:28px; color:#856404;">{{ number_format($totalSelisih, 2) }}</div>
                        <div style="font-size:12px; color:#856404;">unit (absolut)</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100 border-0 shadow-sm" style="background:#f8d7da;">
                    <div class="card-body">
                        <div style="font-size:11px; color:#842029; text-transform:uppercase; letter-spacing:1px;">Nilai Selisih</div>
                        <div class="fw-bold mt-1" style="font-size:20px; color:#842029;">
                            Rp {{ number_format($totalNilaiSelisih, 0, ',', '.') }}
                        </div>
                        <div style="font-size:12px; color:#842029;">estimasi kerugian/keuntungan</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── TABEL OPNAME ── --}}
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="px-4 py-3" style="border-bottom:1px solid #eadfd4;">
                    <span class="fw-bold" style="color:#d88656;">Rincian Stock Opname</span>
                    <span class="text-muted ms-2" style="font-size:13px;">{{ $data->count() }} baris barang</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size:13px;">
                        <thead style="background-color: #d88656; color: white;">
                            <tr>
                                <th style="background-color: #d88656; color: white;" class="px-4">Dokumen</th>
                                <th style="background-color: #d88656; color: white;">Barang</th>
                                <th style="background-color: #d88656; color: white;" class="text-center">Sistem</th>
                                <th style="background-color: #d88656; color: white;" class="text-center">Fisik</th>
                                <th style="background-color: #d88656; color: white;" class="text-center">Selisih</th>
                                <th style="background-color: #d88656; color: white;" class="text-end">HPP</th>
                                <th style="background-color: #d88656; color: white;" class="text-end">Nilai Selisih</th>
                                <th style="background-color: #d88656; color: white;" class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($data as $opname)
                                @foreach($opname->details as $detail)
                                    <tr>
                                        <td class="px-4">
                                            <div class="fw-semibold" style="color:#d88656; font-size:12px;">{{ $opname->kode_opname }}</div>
                                            <div class="text-muted" style="font-size:11px;">{{ \Carbon\Carbon::parse($opname->tanggal)->format('d M Y') }}</div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold">{{ $detail->barang->nama ?? '-' }}</div>
                                            <div class="text-muted" style="font-size:11px;">{{ $opname->gudang->nama ?? '-' }}</div>
                                        </td>
                                        <td class="text-center">{{ number_format($detail->stok_sistem, 2) }}</td>
                                        <td class="text-center fw-semibold">{{ number_format($detail->stok_fisik, 2) }}</td>
                                        <td class="text-center">
                                            @if($detail->selisih < 0)
                                                <span class="text-danger fw-bold">{{ number_format($detail->selisih, 2) }}</span>
                                            @elseif($detail->selisih > 0)
                                                <span class="text-success fw-bold">+{{ number_format($detail->selisih, 2) }}</span>
                                            @else
                                                <span class="text-muted">0</span>
                                            @endif
                                        </td>
                                        <td class="text-end text-muted">Rp {{ number_format($detail->hpp_satuan, 0, ',', '.') }}</td>
                                        <td class="text-end fw-semibold {{ $detail->nilai_selisih < 0 ? 'text-danger' : ($detail->nilai_selisih > 0 ? 'text-success' : '') }}">
                                            Rp {{ number_format($detail->nilai_selisih, 0, ',', '.') }}
                                        </td>
                                        <td class="text-center">
                                            @if($opname->status === 'approved')
                                                <span class="badge bg-success">Approved</span>
                                            @else
                                                <span class="badge bg-secondary">Draft</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                        Tidak ada data stock opname.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($data->count() > 0)
                        <tfoot>
                            <tr style="background:#fdf3ec; font-weight:600;">
                                <td colspan="6" class="px-4" style="color:#d88656;">Total Nilai Selisih</td>
                                <td class="text-end" style="color:#d88656;">
                                    Rp {{ number_format($totalNilaiSelisih, 0, ',', '.') }}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>