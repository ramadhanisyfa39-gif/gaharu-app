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

            {{-- Tombol Tambah sekarang membuka modal, bukan pindah halaman --}}
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambahGudang">
                + Tambah Gudang
            </button>
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
                                {{-- Tombol Detil membuka modal detail --}}
                                <button type="button"
                                        class="btn btn-info btn-sm text-white"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalDetailGudang"
                                        data-nama="{{ $gudang->nama }}"
                                        data-kategori="{{ $gudang->kategori }}">
                                    Detil
                                </button>

                                {{-- Tombol Edit membuka modal edit, terisi otomatis --}}
                                <button type="button"
                                        class="btn btn-warning btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalEditGudang"
                                        data-id="{{ $gudang->id }}"
                                        data-nama="{{ $gudang->nama }}"
                                        data-kategori="{{ $gudang->kategori }}"
                                        data-action="{{ route('gudangs.update', $gudang->id) }}">
                                    Edit
                                </button>

                                {{-- Tombol Hapus membuka modal konfirmasi --}}
                                <button type="button"
                                        class="btn btn-danger btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalHapusGudang"
                                        data-nama="{{ $gudang->nama }}"
                                        data-action="{{ route('gudangs.destroy', $gudang->id) }}">
                                    Hapus
                                </button>
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


{{-- ================= MODAL TAMBAH GUDANG ================= --}}
<div class="modal fade" id="modalTambahGudang" tabindex="-1" aria-labelledby="modalTambahGudangLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 12px; overflow: hidden;">
            <form action="{{ route('gudangs.store') }}" method="POST">
                @csrf
                <input type="hidden" name="_form" value="create">

                <div class="modal-header text-white" style="background-color: #d88656;">
                    <h5 class="modal-title fw-bold" id="modalTambahGudangLabel">Tambah Gudang</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="nama" class="form-label fw-semibold">
                            Nama Gudang <span class="text-danger">*</span>
                        </label>
                        <input
                            type="text"
                            name="nama"
                            id="nama"
                            class="form-control @error('nama') is-invalid @enderror"
                            value="{{ old('nama') }}"
                            placeholder="Contoh: Gudang Bahan Baku"
                        >
                        @error('nama')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-1">
                        <label for="kategori" class="form-label fw-semibold">
                            Kategori <span class="text-danger">*</span>
                        </label>
                        <select name="kategori" id="kategori" class="form-select @error('kategori') is-invalid @enderror" required>
                            <option value="">-- Pilih Kategori --</option>
                            <option value="Operasional" {{ old('kategori') == 'Operasional' ? 'selected' : '' }}>Operasional</option>
                            <option value="Utama" {{ old('kategori') == 'Utama' ? 'selected' : '' }}>Utama</option>
                            <option value="Produksi" {{ old('kategori') == 'Produksi' ? 'selected' : '' }}>Produksi</option>
                        </select>
                        @error('kategori')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Gudang</button>
                </div>
            </form>
        </div>
    </div>
</div>


{{-- ================= MODAL DETAIL GUDANG ================= --}}
<div class="modal fade" id="modalDetailGudang" tabindex="-1" aria-labelledby="modalDetailGudangLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header text-white" style="background-color: #d88656;">
                <h5 class="modal-title fw-bold" id="modalDetailGudangLabel">Informasi Gudang</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="fw-bold text-muted small text-uppercase">Nama Gudang</label>
                    <p class="fs-5 text-dark fw-semibold mb-0" id="detailNama"></p>
                </div>
                <div class="mb-0">
                    <label class="fw-bold text-muted small text-uppercase">Kategori</label>
                    <p class="fs-5 text-dark fw-semibold mb-0" id="detailKategori"></p>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>


{{-- ================= MODAL EDIT GUDANG ================= --}}
<div class="modal fade" id="modalEditGudang" tabindex="-1" aria-labelledby="modalEditGudangLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 12px; overflow: hidden;">
            <form id="formEditGudang" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="_form" value="edit">

                <div class="modal-header text-white" style="background-color: #d88656;">
                    <h5 class="modal-title fw-bold" id="modalEditGudangLabel">Edit Gudang</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="editNama" class="form-label fw-semibold">
                            Nama Gudang <span class="text-danger">*</span>
                        </label>
                        <input
                            type="text"
                            name="nama"
                            id="editNama"
                            class="form-control @error('nama') is-invalid @enderror"
                            placeholder="Contoh: Gudang Bahan Baku"
                        >
                        @error('nama')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-1">
                        <label for="editKategori" class="form-label fw-semibold">
                            Kategori <span class="text-danger">*</span>
                        </label>
                        <select name="kategori" id="editKategori" class="form-select @error('kategori') is-invalid @enderror" required>
                            <option value="">-- Pilih Kategori --</option>
                            <option value="Operasional">Operasional</option>
                            <option value="Utama">Utama</option>
                            <option value="Produksi">Produksi</option>
                        </select>
                        @error('kategori')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Update Gudang</button>
                </div>
            </form>
        </div>
    </div>
</div>


{{-- ================= MODAL HAPUS GUDANG ================= --}}
<div class="modal fade" id="modalHapusGudang" tabindex="-1" aria-labelledby="modalHapusGudangLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 12px; overflow: hidden;">
            <form id="formHapusGudang" method="POST">
                @csrf
                @method('DELETE')

                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalHapusGudangLabel">Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <p class="mb-0">
                        Yakin ingin menghapus data gudang
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


@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ---- Isi modal Detail dari data-attribute tombol yang diklik ----
    var modalDetail = document.getElementById('modalDetailGudang');
    modalDetail.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        document.getElementById('detailNama').innerText = button.getAttribute('data-nama');
        document.getElementById('detailKategori').innerText = button.getAttribute('data-kategori');
    });

    // ---- Isi modal Edit dari data-attribute tombol yang diklik ----
    var modalEdit = document.getElementById('modalEditGudang');
    modalEdit.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        document.getElementById('editNama').value = button.getAttribute('data-nama');
        document.getElementById('editKategori').value = button.getAttribute('data-kategori');
        document.getElementById('formEditGudang').action = button.getAttribute('data-action');
    });

    // ---- Set action form Hapus dari data-attribute tombol yang diklik ----
    var modalHapus = document.getElementById('modalHapusGudang');
    modalHapus.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        document.getElementById('hapusNama').innerText = button.getAttribute('data-nama');
        document.getElementById('formHapusGudang').action = button.getAttribute('data-action');
    });

    // ---- Auto-buka kembali modal Tambah/Edit jika ada error validasi ----
    @if ($errors->any())
        @if (old('_form') === 'edit')
            document.getElementById('editNama').value = "{{ old('nama') }}";
            document.getElementById('editKategori').value = "{{ old('kategori') }}";
            new bootstrap.Modal(document.getElementById('modalEditGudang')).show();
        @else
            new bootstrap.Modal(document.getElementById('modalTambahGudang')).show();
        @endif
    @endif
});
</script>
@endpush

</x-app-layout>