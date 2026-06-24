<x-app-layout>
<x-slot name="header">

        Master Supplier

    </x-slot>
<div class="card shadow-sm border-0">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0 fw-bold">Master Data Supplier</h5>
            <small class="text-muted">Kelola data supplier perusahaan</small>
        </div>

        <button type="button" class="btn text-white" style="background-color: #d88656; border: none;" data-bs-toggle="modal" data-bs-target="#modalTambahSupplier">
    + Tambah Supplier
</button>
    </div>

    <div class="card-body">

        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0">
                <thead class="table-dark text-center">
                    <tr>
                        <th style="width: 60px;">No</th>
                        <th>Nama Supplier</th>
                        <th>No HP</th>
                        <th>Alamat</th>
                        <th style="width: 180px;">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($suppliers as $supplier)
                        <tr>
                            <td class="text-center">
                                {{ $loop->iteration + ($suppliers->currentPage() - 1) * $suppliers->perPage() }}
                            </td>

                            <td>{{ $supplier->nama }}</td>

                            <td>{{ $supplier->no_hp ?? '-' }}</td>

                            <td>{{ $supplier->alamat ?? '-' }}</td>

                            <td class="text-center">
                                <a href="{{ route('suppliers.edit', $supplier->id) }}" class="btn btn-warning btn-sm">
                                    Edit
                                </a>

                                <form action="{{ route('suppliers.destroy', $supplier->id) }}"
                                      method="POST"
                                      class="d-inline"
                                      onsubmit="return confirm('Yakin ingin menghapus data supplier ini?')">
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
                            <td colspan="5" class="text-center text-muted py-4">
                                Data supplier belum ada.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $suppliers->links() }}
        </div>

    </div>
    <div class="modal fade" id="modalTambahSupplier" tabindex="-1" aria-labelledby="modalTambahSupplierLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header text-white" style="background-color: #d88656;">
                <h5 class="modal-title" id="modalTambahSupplierLabel">Tambah Supplier Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form action="{{ route('suppliers.store') }}" method="POST">
                @csrf
                <div class="modal-body text-start">
                    
                    <div class="mb-3">
                        <label class="fw-semibold">Nama Supplier</label>
                        <input type="text" name="nama_supplier" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="fw-semibold">No HP / Telepon</label>
                        <input type="text" name="no_hp" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="fw-semibold">Alamat</label>
                        <textarea name="alamat" class="form-control" rows="3" required></textarea>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kembali</button>
                    <button type="submit" class="btn text-white" style="background-color: #d88656;">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Memastikan tombol X (close) berwarna putih */
.btn-close-white {
    filter: invert(1) grayscale(1) brightness(2);
}
</style>
</div>

</x-app-layout>