<x-app-layout>
    <div class="container-fluid py-4">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-dark text-white p-3">
                <h5 class="mb-0 font-weight-bold"><i class="fas fa-history mr-2"></i> Laporan Rekapitulasi Hasil Produksi (Operasional)</h5>
            </div>
            <div class="card-body bg-white text-dark">
                <form action="{{ route('laporan.rekapitulasi') }}" method="GET" class="row align-items-end g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label font-weight-bold text-secondary">Tanggal Mulai</label>
                        <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label font-weight-bold text-secondary">Tanggal Selesai</label>
                        <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary btn-block shadow-sm">
                            <i class="fas fa-filter mr-1"></i> Filter Data
                        </button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle text-center mb-0" style="color: #212529;">
                        <thead class="bg-light text-dark font-weight-bold">
                            <tr style="background-color: #f8f9fa;">
                                <th style="width: 12%;">Tanggal</th>
                                <th style="width: 15%;">Kode Produksi</th>
                                <th style="width: 15%;">Kode WO</th>
                                <th class="text-left">Nama Produk</th>
                                <th>Gudang Tujuan</th>
                                <th style="width: 10%;">Target WO</th>
                                <th style="width: 10%;">Realisasi Output</th>
                                <th style="width: 13%;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rekapitulasi as $row)
                            <tr>
                                <td class="align-middle text-secondary font-weight-bold">
                                    {{ \Carbon\Carbon::parse($row->tanggal)->format('d-M-Y') }}
                                </td>
                                <td class="align-middle">
                                    <span class="badge badge-dark px-2 py-1" style="font-size: 0.9em; background-color: #343a40; color: #fff;">
                                        {{ $row->kode_produksi }}
                                    </span>
                                </td>
                                <td class="align-middle text-dark font-weight-bold">
                                    {{ $row->kode_wo ?? '-' }}
                                </td>
                                <td class="align-middle text-left text-dark font-weight-bold">
                                    {{ $row->nama_produk }}
                                </td>
                                <td class="align-middle text-muted">
                                    {{ $row->nama_gudang ?? 'Gudang B2B' }}
                                </td>
                                <td class="align-middle font-weight-bold text-dark">
                                    {{ number_format($row->qty_target, 0, ',', '.') }}
                                </td>
                                <td class="align-middle font-weight-bold text-success" style="font-size: 1.1em;">
                                    {{ number_format($row->qty_hasil, 0, ',', '.') }}
                                </td>
                                <td class="align-middle">
                                    <span class="badge badge-success px-2 py-1" style="background-color: #28a745; color: #fff;">
                                        {{ strtoupper($row->status_produksi ?? 'SELESAI') }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-muted text-center py-4">
                                    <i class="fas fa-info-circle mr-1"></i> Tidak ada data produksi pada periode ini.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>