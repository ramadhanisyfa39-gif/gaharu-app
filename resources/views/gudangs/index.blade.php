<x-app-layout>
<x-slot name="header">

        Master Gudang

    </x-slot>
<div class="card shadow-sm border-0">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0 fw-bold">Master Data Gudang</h5>
            <small class="text-muted">Kelola data gudang perusahaan</small>
        </div>

        <div class="d-flex align-items-center gap-2">
            <form action="{{ route('gudangs.index') }}" method="GET" class="d-flex gap-2">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari nama/kategori..." value="{{ request('search') }}" style="width: 200px; border-radius: 6px;">
                <button type="submit" class="btn btn-sm btn-primary" style="border-radius: 6px;">Cari</button>
                @if(request('search'))
                    <a href="{{ route('gudangs.index') }}" class="btn btn-sm btn-secondary" style="border-radius: 6px;">Reset</a>
                @endif
            </form>
            <a href="{{ route('gudangs.create') }}" class="btn btn-primary btn-sm">
                + Tambah Gudang
            </a>
        </div>
    </div>

    <div class="card-body">

        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0">
                <thead class="table-dark text-center">
                    <tr>
                        <th style="width: 60px;">No</th>
                        <th>Nama Gudang</th>
                        <th>Kategori</th>
                        <th style="width: 180px;">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($gudangs as $gudang)
                        <tr>
                            <td class="text-center">
                                {{ $loop->iteration + ($gudangs->currentPage() - 1) * $gudangs->perPage() }}
                            </td>

                            <td>{{ $gudang->nama }}</td>

                            <td>{{ $gudang->kategori }}</td>

                            <td class="text-center">
                                <a href="{{ route('gudangs.show', $gudang->id) }}" class="btn btn-info btn-sm text-white">
                                    Detil
                                </a>

                                <a href="{{ route('gudangs.edit', $gudang->id) }}" class="btn btn-warning btn-sm">
                                    Edit
                                </a>

                                <form action="{{ route('gudangs.destroy', $gudang->id) }}"
                                      method="POST"
                                      class="d-inline"
                                      onsubmit="return confirm('Yakin ingin menghapus data gudang ini?')">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit" class="btn btn-danger btn-sm">
                                        Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
                                Data gudang belum ada.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $gudangs->links() }}
        </div>

    </div>
</div>

</x-app-layout>