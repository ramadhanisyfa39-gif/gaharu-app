<x-app-layout>

<div class="container-fluid">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="fw-bold mb-0">
                Pengeluaran Bahan Baku
            </h2>

            <small class="text-muted">
                Manajemen pengeluaran stok bahan baku produksi
            </small>

        </div>

        <div>

            <a href="{{ route('dashboard') }}"
               class="btn btn-secondary">

                <i class="bi bi-arrow-left"></i>
                Dashboard

            </a>

            <a href="{{ route('pengeluaran-bahan-baku.create') }}"
               class="btn btn-primary">

                <i class="bi bi-plus-circle"></i>
                Tambah

            </a>

        </div>

    </div>

    <!-- STATISTIK -->
    <div class="row mb-4">

        <div class="col-md-4">

            <div class="card shadow-sm">

                <div class="card-body">

                    <h6>Total Pengeluaran</h6>

                    <h2 class="fw-bold">
                        {{ $data->count() }}
                    </h2>

                </div>

            </div>

        </div>

        <div class="col-md-4">

            <div class="card shadow-sm">

                <div class="card-body">

                    <h6>Draft</h6>

                    <h2 class="fw-bold text-warning">
                        {{ $data->where('status','draft')->count() }}
                    </h2>

                </div>

            </div>

        </div>

        <div class="col-md-4">

            <div class="card shadow-sm">

                <div class="card-body">

                    <h6>Approved</h6>

                    <h2 class="fw-bold text-success">
                        {{
                            $data->whereIn(
                                'status',
                                ['approved','disetujui']
                            )->count()
                        }}
                    </h2>

                </div>

            </div>

        </div>

    </div>

    @if(session('success'))

        <div class="alert alert-success">

            {{ session('success') }}

        </div>

    @endif

    <!-- TABEL -->
    <div class="card shadow-sm">

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead class="table-dark">

                        <tr>

                            <th>No</th>
                            <th>Kode</th>
                            <th>Gudang</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th width="180">
                                Aksi
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                    @forelse($data as $item)

                        <tr>

                            <td>
                                {{ $loop->iteration }}
                            </td>

                            <td class="fw-semibold">
                                {{ $item->kode_pengeluaran }}
                            </td>

                            <td>
                                {{ $item->nama_gudang }}
                            </td>

                            <td>
                                {{ $item->tanggal }}
                            </td>

                            <td>

                                @if(
                                    strtolower($item->status) == 'approved'
                                    ||
                                    strtolower($item->status) == 'disetujui'
                                )

                                    <span class="badge bg-success">
                                        Approved
                                    </span>

                                @else

                                    <span class="badge bg-warning text-dark">
                                        Draft
                                    </span>

                                @endif

                            </td>

                            <td>

                                <div class="btn-group">

                                    <a
                                        href="{{ route('pengeluaran-bahan-baku.show',$item->id) }}"
                                        class="btn btn-info btn-sm">

                                        <i class="bi bi-eye"></i>

                                    </a>

                                    @if(
                                        strtolower($item->status) == 'draft'
                                    )

                                        @if(

    strtolower($item->status) !== 'approved'

    &&

    strtolower($item->status) !== 'disetujui'

    &&

    !str_contains(
        strtolower($item->keterangan ?? ''),
        'permintaan bahan baku untuk'
    )

)

    <a
        href="{{ route('pengeluaran-bahan-baku.edit', $item->id) }}"
        class="btn btn-warning btn-sm">

        Edit

    </a>

@endif

                                        <a
                                            href="{{ route('pengeluaran-bahan-baku.approve',$item->id) }}"
                                            class="btn btn-success btn-sm"
                                            onclick="return confirm('Approve pengeluaran ini?')">

                                            <i class="bi bi-check-circle"></i>

                                        </a>

                                    @endif

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="6"
                                class="text-center text-muted">

                                Belum ada data pengeluaran bahan baku.

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