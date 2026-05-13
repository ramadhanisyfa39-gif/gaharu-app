<x-app-layout>

    <div class="container mx-auto">

        <!-- HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-3">

            <h3 class="fw-bold">
                Pengeluaran Bahan Baku
            </h3>

            <div class="d-flex gap-2">

                <!-- KEMBALI -->
                <a href="{{ route('dashboard') }}"
                   class="btn btn-secondary">

                    ← Menu Utama

                </a>

                <!-- TAMBAH -->
                <a href="{{ route('pengeluaran-bahan-baku.create') }}"
                   class="btn btn-primary">

                    + Tambah Pengeluaran

                </a>

            </div>

        </div>

        <!-- ALERT -->
        @if(session('success'))

            <div class="alert alert-success">

                {{ session('success') }}

            </div>

        @endif

        <!-- CARD -->
        <div class="card shadow-sm">

            <div class="card-body">

                <table class="table table-bordered table-hover align-middle">

                    <thead class="table-dark">

                        <tr>

                            <th>No</th>
                            <th>Kode</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th width="220">Aksi</th>

                        </tr>

                    </thead>

                    <tbody>

                        @forelse($data as $item)

                            <tr>

                                <td>
                                    {{ $loop->iteration }}
                                </td>

                                <td>
                                    {{ $item->kode_pengeluaran }}
                                </td>

                                <td>
                                    {{ $item->tanggal }}
                                </td>

                                <td>

                                    @if($item->status == 'disetujui')

                                        <span class="badge bg-success">

                                            Disetujui

                                        </span>

                                    @else

                                        <span class="badge bg-warning text-dark">

                                            {{ ucfirst($item->status) }}

                                        </span>

                                    @endif

                                </td>

                                <td>

                                    <a href="{{ route('pengeluaran-bahan-baku.show', $item->id) }}"
                                       class="btn btn-sm btn-info">

                                        Detail

                                    </a>

                                    @if($item->status !== 'disetujui')

                                        <a href="{{ route('pengeluaran-bahan-baku.edit', $item->id) }}"
                                           class="btn btn-sm btn-warning">

                                            Edit

                                        </a>

                                        <a href="{{ route('pengeluaran-bahan-baku.approve', $item->id) }}"
                                           class="btn btn-sm btn-success"
                                           onclick="return confirm('Approve pengeluaran ini?')">

                                            Approve

                                        </a>

                                    @endif

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="5"
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

</x-app-layout>