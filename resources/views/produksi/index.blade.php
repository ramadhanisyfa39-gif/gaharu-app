<x-app-layout>

    <div class="container mt-4 mb-5">
        <div class="card shadow-sm border-0">

            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-3">
                <div>
                    <h5 class="mb-0">
                        <i class="bi bi-clock-history me-2"></i>
                        Riwayat Hasil Produksi
                    </h5>
                    <small class="text-white-50">
                        Data hasil produksi yang telah disimpan.
                    </small>
                </div>

                <a href="{{ route('produksi.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i>
                    Tambah Produksi
                </a>
            </div>

            <div class="card-body p-4">

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                        <strong>Berhasil!</strong> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0">

                        <thead class="table-dark">
                            <tr>
                                <th width="5%" class="text-center">No</th>
                                <th>Tanggal</th>
                                <th>Kode Produksi</th>
                                <th>No. WO</th>
                                <th>Produk Jadi</th>
                                <th class="text-center">Qty Hasil</th>
                                <th class="text-end">Total HPP</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($riwayatProduksi as $index => $rp)
                                <tr>
                                    <td class="text-center">
                                        {{ $index + 1 }}
                                    </td>

                                    <td>
                                        {{ date('d-m-Y', strtotime($rp->tanggal)) }}
                                    </td>

                                    <td>
                                        <code class="text-danger">
                                            {{ $rp->kode_produksi }}
                                        </code>
                                    </td>

                                    <td>
                                        <span class="badge bg-secondary">
                                            {{ $rp->kode_wo ?? 'N/A' }}
                                        </span>
                                    </td>

                                    <td>
                                        <strong>
                                            {{ $rp->nama_produk }}
                                        </strong>
                                    </td>

                                    <td class="text-center">
                                        <span class="badge bg-success fs-6">
                                            {{ (int) $rp->qty }} Unit
                                        </span>
                                    </td>

                                    <td class="text-end fw-semibold">
                                        Rp {{ number_format($rp->hpp_total ?? 0, 0, ',', '.') }}
                                    </td>
                                </tr>

                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-5">
                                        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                        Belum ada riwayat produksi.
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