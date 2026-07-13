<x-app-layout>
    <x-slot name="header">

        Master Customer

    </x-slot>
<div class="card">
    <div class="card-header">
        <h4>Data Customer</h4>
    </div>

    <div class="card-body">

        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <a href="{{ route('customer.create') }}" class="btn btn-primary mb-0">
                + Tambah Customer
            </a>

            <form action="{{ route('customer.index') }}" method="GET" class="d-flex gap-2">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari nama/jenis/hp..." value="{{ request('search') }}" style="width: 220px; border-radius: 6px;">
                <button type="submit" class="btn btn-sm btn-primary" style="border-radius: 6px; border: none; padding: 5px 15px;">Cari</button>
                @if(request('search'))
                    <a href="{{ route('customer.index') }}" class="btn btn-sm btn-secondary" style="border-radius: 6px; padding: 5px 15px;">Reset</a>
                @endif
            </form>
        </div>

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>Jenis</th>
                    <th>No HP</th>
                    <th>Alamat</th>
                    <th width="150">Aksi</th>
                </tr>
            </thead>

            <tbody>
                @forelse($data as $item)
                <tr>
                    <td>{{ $loop->iteration + ($data->currentPage() - 1) * $data->perPage() }}</td>
                    <td>{{ $item->nama }}</td>
                    <td>{{ $item->jenis }}</td>
                    <td>{{ $item->no_hp }}</td>
                    <td>{{ $item->alamat }}</td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('customer.show', $item->id) }}" class="btn btn-info btn-sm text-white">Detil</a>
                            <a href="{{ route('customer.edit', $item->id) }}" class="btn btn-warning btn-sm">Edit</a>
                            <form action="{{ route('customer.destroy', $item->id) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger btn-sm" onclick="return confirm('Yakin hapus data ini?')">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">Data customer belum ada.</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-3">
            {{ $data->links() }}
        </div>

    </div>
</div>

</x-app-layout>