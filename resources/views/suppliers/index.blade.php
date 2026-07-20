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

        <div class="d-flex align-items-center gap-2">
            <form action="{{ route('suppliers.index') }}" method="GET" class="d-flex gap-2">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari nama/no_hp..." value="{{ request('search') }}" style="width: 200px; border-radius: 6px;">
                <button type="submit" class="btn btn-sm text-white" style="background-color: #d88656; border-radius: 6px; border: none; padding: 5px 15px;">Cari</button>
                @if(request('search'))
                    <a href="{{ route('suppliers.index') }}" class="btn btn-sm btn-secondary" style="border-radius: 6px; padding: 5px 15px;">Reset</a>
                @endif
            </form>

            {{-- Tombol Tambah membuka modal --}}
            <button type="button" class="btn text-white" style="background-color: #d88656; border: none;" data-bs-toggle="modal" data-bs-target="#modalTambahSupplier">
                + Tambah Supplier
            </button>
        </div>
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
                                {{-- Tombol Detil membuka modal detail --}}
                                <button type="button"
                                        class="btn btn-info btn-sm text-white"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalDetailSupplier"
                                        data-nama="{{ $supplier->nama }}"
                                        data-no_hp="{{ $supplier->no_hp ?? '-' }}"
                                        data-alamat="{{ $supplier->alamat ?? '-' }}">
                                    Detil
                                </button>

                                {{-- Tombol Edit membuka modal edit, terisi otomatis --}}
                                <button type="button"
                                        class="btn btn-warning btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalEditSupplier"
                                        data-id="{{ $supplier->id }}"
                                        data-nama="{{ $supplier->nama }}"
                                        data-no_hp="{{ $supplier->no_hp }}"
                                        data-alamat="{{ $supplier->alamat }}"
                                        data-action="{{ route('suppliers.update', $supplier->id) }}">
                                    Edit
                                </button>

                                {{-- Tombol Hapus membuka modal konfirmasi --}}
                                <button type="button"
                                        class="btn btn-danger btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalHapusSupplier"
                                        data-nama="{{ $supplier->nama }}"
                                        data-action="{{ route('suppliers.destroy', $supplier->id) }}">
                                    Hapus
                                </button>
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
</div>


{{-- ================= MODAL TAMBAH SUPPLIER ================= --}}
<div class="modal fade" id="modalTambahSupplier" tabindex="-1" aria-labelledby="modalTambahSupplierLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header text-white" style="background-color: #d88656;">
                <h5 class="modal-title" id="modalTambahSupplierLabel">Tambah Supplier Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('suppliers.store') }}" method="POST">
                @csrf
                <input type="hidden" name="_form" value="create">

                <div class="modal-body text-start">

                    <div class="mb-3">
                        <label class="fw-semibold">Nama Supplier</label>
                        <input type="text" name="nama" class="form-control @error('nama') is-invalid @enderror" value="{{ old('nama') }}" required>
                        @error('nama')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="fw-semibold">No HP / Telepon</label>
                        <input type="text" name="no_hp" class="form-control @error('no_hp') is-invalid @enderror" value="{{ old('no_hp') }}" required>
                        @error('no_hp')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="fw-semibold">Alamat</label>
                        <textarea name="alamat" class="form-control @error('alamat') is-invalid @enderror" rows="3" required>{{ old('alamat') }}</textarea>
                        @error('alamat')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
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


{{-- ================= MODAL DETAIL SUPPLIER ================= --}}
<div class="modal fade" id="modalDetailSupplier" tabindex="-1" aria-labelledby="modalDetailSupplierLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header text-white" style="background-color: #d88656;">
                <h5 class="modal-title fw-bold" id="modalDetailSupplierLabel">Informasi Supplier</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4 text-start">
                <div class="mb-3">
                    <label class="fw-bold text-muted small text-uppercase">Nama Supplier</label>
                    <p class="fs-5 text-dark fw-semibold mb-0" id="detailNama"></p>
                </div>
                <div class="mb-3">
                    <label class="fw-bold text-muted small text-uppercase">Nomor HP</label>
                    <p class="fs-6 text-dark mb-0" id="detailNoHp"></p>
                </div>
                <div class="mb-0">
                    <label class="fw-bold text-muted small text-uppercase">Alamat</label>
                    <p class="fs-6 text-dark mb-0" id="detailAlamat"></p>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>


{{-- ================= MODAL EDIT SUPPLIER ================= --}}
<div class="modal fade" id="modalEditSupplier" tabindex="-1" aria-labelledby="modalEditSupplierLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header text-white" style="background-color: #d88656;">
                <h5 class="modal-title fw-bold" id="modalEditSupplierLabel">Edit Supplier</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="formEditSupplier" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="_form" value="edit">

                <div class="modal-body text-start">

                    <div class="mb-3">
                        <label for="editNama" class="fw-semibold">Nama Supplier <span class="text-danger">*</span></label>
                        <input type="text" name="nama" id="editNama" class="form-control @error('nama') is-invalid @enderror" placeholder="Masukkan nama supplier">
                        @error('nama')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="editNoHp" class="fw-semibold">No HP / Telepon</label>
                        <input type="text" name="no_hp" id="editNoHp" class="form-control @error('no_hp') is-invalid @enderror" placeholder="Contoh: 08123456789">
                        @error('no_hp')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="editAlamat" class="fw-semibold">Alamat</label>
                        <textarea name="alamat" id="editAlamat" class="form-control @error('alamat') is-invalid @enderror" rows="3" placeholder="Masukkan alamat supplier"></textarea>
                        @error('alamat')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn text-white" style="background-color: #d88656;">Update Supplier</button>
                </div>
            </form>
        </div>
    </div>
</div>


{{-- ================= MODAL HAPUS SUPPLIER ================= --}}
<div class="modal fade" id="modalHapusSupplier" tabindex="-1" aria-labelledby="modalHapusSupplierLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalHapusSupplierLabel">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="formHapusSupplier" method="POST">
                @csrf
                @method('DELETE')

                <div class="modal-body">
                    <p class="mb-0">
                        Yakin ingin menghapus data supplier
                        <strong id="hapusNama"></strong>?
                        Tindakan ini tidak dapat dibatalkan.
                    </p>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Ya, Hapus</button>
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ---- Isi modal Detail dari data-attribute tombol yang diklik ----
    var modalDetail = document.getElementById('modalDetailSupplier');
    modalDetail.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        document.getElementById('detailNama').innerText = button.getAttribute('data-nama');
        document.getElementById('detailNoHp').innerText = button.getAttribute('data-no_hp');
        document.getElementById('detailAlamat').innerText = button.getAttribute('data-alamat');
    });

    // ---- Isi modal Edit dari data-attribute tombol yang diklik ----
    var modalEdit = document.getElementById('modalEditSupplier');
    modalEdit.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        document.getElementById('editNama').value = button.getAttribute('data-nama');
        document.getElementById('editNoHp').value = button.getAttribute('data-no_hp');
        document.getElementById('editAlamat').value = button.getAttribute('data-alamat');
        document.getElementById('formEditSupplier').action = button.getAttribute('data-action');
    });

    // ---- Set action form Hapus dari data-attribute tombol yang diklik ----
    var modalHapus = document.getElementById('modalHapusSupplier');
    modalHapus.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        document.getElementById('hapusNama').innerText = button.getAttribute('data-nama');
        document.getElementById('formHapusSupplier').action = button.getAttribute('data-action');
    });

    // ---- Auto-buka kembali modal Tambah/Edit jika ada error validasi ----
    @if ($errors->any())
        @if (old('_form') === 'edit')
            document.getElementById('editNama').value = "{{ old('nama') }}";
            document.getElementById('editNoHp').value = "{{ old('no_hp') }}";
            document.getElementById('editAlamat').value = "{{ old('alamat') }}";
            new bootstrap.Modal(document.getElementById('modalEditSupplier')).show();
        @else
            new bootstrap.Modal(document.getElementById('modalTambahSupplier')).show();
        @endif
    @endif
});
</script>
@endpush

</x-app-layout>