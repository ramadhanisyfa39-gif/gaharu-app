<x-app-layout>
    <div class="container-fluid py-4">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-success text-white p-3">
                <h5 class="mb-0 font-weight-bold"><i class="fas fa-calculator mr-2"></i> Laporan Harga Pokok Produksi / HPP (Akuntansi)</h5>
            </div>
            <div class="card-body bg-white text-dark">
                <form action="{{ route('laporan.hpp') }}" method="GET" class="row align-items-end g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label font-weight-bold text-secondary">Tanggal Mulai</label>
                        <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label font-weight-bold text-secondary">Tanggal Selesai</label>
                        <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                    </div>
                    <div class="col-md-6 d-flex gap-2">
                        <button type="submit" class="btn btn-success shadow-sm px-4">
                            <i class="fas fa-search-dollar mr-1"></i> Filter Keuangan
                        </button>
                        <a href="{{ route('laporan.hpp', array_merge(request()->all(), ['format' => 'excel'])) }}" class="btn btn-success shadow-sm px-4">
                            📊 Export Excel
                        </a>
                        <a href="{{ route('laporan.hpp', array_merge(request()->all(), ['format' => 'pdf'])) }}" class="btn btn-danger shadow-sm px-4">
                            📕 Export PDF
                        </a>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle text-center mb-0" style="color: #212529;">
                        <thead class="bg-light text-dark font-weight-bold">
                            <tr style="background-color: #f8f9fa;">
                                <th style="width: 15%;">Kode Barang</th>
                                <th class="text-left">Nama Produk Jadi</th>
                                <th style="width: 20%;">Total Qty Produksi</th>
                                <th style="width: 25%;">Total Nilai HPP (BBB + BTKL + BOP)</th>
                                <th style="width: 25%;">Rata-rata HPP / Satuan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $grandTotalHpp = 0; @endphp
                            @forelse($laporanHpp as $row)
                            @php 
                                $hppPerSatuan = $row->total_qty > 0 ? ($row->total_hpp / $row->total_qty) : 0;
                                $grandTotalHpp += $row->total_hpp;
                            @endphp
                            <tr>
                                <td class="align-middle font-weight-bold text-secondary">{{ $row->kode_barang }}</td>
                                <td class="align-middle text-left text-dark font-weight-bold">{{ $row->nama_produk }}</td>
                                <td class="align-middle text-dark font-weight-bold">
                                    {{ number_format($row->total_qty, 0, ',', '.') }} {{ $row->satuan ?? 'Pcs' }}
                                </td>
                                <td class="align-middle text-right text-danger font-weight-bold" style="font-size: 1.05em;">
                                    Rp {{ number_format($row->total_hpp, 2, ',', '.') }}
                                </td>
                                <td class="align-middle text-right text-info font-weight-bold" style="font-size: 1.05em;">
                                    Rp {{ number_format($hppPerSatuan, 2, ',', '.') }} / {{ $row->satuan ?? 'Pcs' }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-muted text-center py-4">
                                    <i class="fas fa-exclamation-circle mr-1"></i> Tidak ada perputaran HPP produksi pada periode ini.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if($laporanHpp->count() > 0)
                        <tfoot>
                            <tr style="background-color: #f8f9fa;">
                                <th colspan="3" class="text-right text-dark font-weight-bold align-middle">GRAND TOTAL BIAYA PRODUKSI:</th>
                                <th class="text-right text-danger font-weight-bold align-middle" style="font-size: 1.15em;">
                                    Rp {{ number_format($grandTotalHpp, 2, ',', '.') }}
                                </th>
                                <th></th>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>