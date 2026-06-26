<x-app-layout>
<div class="container mt-4">
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="fw-bold mb-0">Laporan Penjualan POS</h3>
        <button onclick="window.print()" class="btn btn-secondary d-print-none">
            🖨️ Cetak / Save PDF
        </button>
    </div>

    <div class="card mb-4 d-print-none shadow-sm border-0">
        <div class="card-body">
            <form action="{{ route('penjualan_pos.laporan') }}" method="GET">
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Dari Tanggal</label>
                        <input type="date" name="tanggal_mulai" class="form-control" value="{{ $tanggal_mulai }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Sampai Tanggal</label>
                        <input type="date" name="tanggal_selesai" class="form-control" value="{{ $tanggal_selesai }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Gudang / Outlet</label>
                        <select name="gudang_id" class="form-select">
                            <option value="">-- Semua Gudang --</option>
                            @foreach($gudang as $g)
                                <option value="{{ $g->id }}" {{ $gudang_id == $g->id ? 'selected' : '' }}>
                                    {{ $g->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">
                            🔍 Filter Laporan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white shadow-sm border-0">
                <div class="card-body text-center">
                    <h6 class="text-uppercase mb-1 opacity-75">Total Omzet (Penjualan)</h6>
                    <h3 class="mb-0 fw-bold">Rp {{ number_format($total_omzet, 0, ',', '.') }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-secondary text-white shadow-sm border-0">
                <div class="card-body text-center">
                    <h6 class="text-uppercase mb-1 opacity-75">Total HPP (Bahan Baku)</h6>
                    <h3 class="mb-0 fw-bold">Rp {{ number_format($total_hpp, 0, ',', '.') }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white shadow-sm border-0">
                <div class="card-body text-center">
                    <h6 class="text-uppercase mb-1 opacity-75">Total Laba Kotor</h6>
                    <h3 class="mb-0 fw-bold">Rp {{ number_format($total_laba, 0, ',', '.') }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light">
            <span class="fw-bold text-secondary">Daftar Riwayat Transaksi POS</span>
            <span class="float-end text-muted small">Periode: {{ date('d M Y', strtotime($tanggal_mulai)) }} s/d {{ date('d M Y', strtotime($tanggal_selesai)) }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-nowrap">
                    <thead class="table-dark">
                        <tr>
                            <th width="50" class="text-center">No</th>
                            <th>No. Transaksi</th>
                            <th>Tanggal & Waktu</th>
                            <th>Gudang / Outlet</th>
                            <th class="text-end">Total Omzet</th>
                            <th class="text-end">Total HPP</th>
                            <th class="text-end">Laba Kotor</th>
                            <th width="100" class="text-center d-print-none">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data_penjualan as $index => $item)
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td><strong class="text-secondary">{{ $item->kode_transaksi }}</strong></td>
                            <td>{{ date('d-m-Y H:i', strtotime($item->tanggal)) }}</td>
                            
                            <td>
                                <span class="badge bg-light text-dark border">
                                    {{ $item->gudang->nama ?? '-' }}
                                </span>
                            </td>
                            
                            <td class="text-end fw-medium text-primary">
                                Rp {{ number_format($item->total, 0, ',', '.') }}
                            </td>
                            <td class="text-end text-muted">
                                Rp {{ number_format($item->calculated_hpp, 0, ',', '.') }}
                            </td>
                            <td class="text-end fw-bold text-success">
                                Rp {{ number_format($item->calculated_laba, 0, ',', '.') }}
                            </td>
                            
                            <td class="text-center d-print-none">
                                <a href="{{ route('penjualan_pos.show', $item->id) }}" class="btn btn-sm btn-info text-white">
                                    Detail
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                Tidak ada data transaksi POS ditemukan pada periode ini.
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
    /* Mengatur tampilan agar rapi saat dicetak/print ke PDF */
    @media print {
        body { background-color: #fff; }
        .container { width: 100% !important; max-width: 100% !important; margin: 0; padding: 0; }
        .card { border: none !important; box-shadow: none !important; }
        .card-header { background-color: transparent !important; font-weight: bold; padding-left: 0; padding-right: 0; }
        .table th { background-color: #333 !important; color: #fff !important; -webkit-print-color-adjust: exact; }
        .table td { border-bottom: 1px solid #ddd; }
    }
</style>
</x-app-layout>