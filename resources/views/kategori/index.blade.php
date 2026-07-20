<x-app-layout>
<x-slot name="header">

        Master Kategori Barang

    </x-slot>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0 fw-bold">Master Data Kategori</h5>
            <small class="text-muted">Kelola data kategori barang</small>
        </div>

        <div class="d-flex align-items-center gap-2">
            <form action="{{ route('kategori.index') }}" method="GET" class="d-flex gap-2">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari kategori/prefix..." value="{{ request('search') }}" style="width: 200px; border-radius: 6px;">
                <button type="submit" class="btn btn-sm text-white" style="background-color: #d88656; border-radius: 6px; border: none; padding: 5px 15px;">Cari</button>
                @if(request('search'))
                    <a href="{{ route('kategori.index') }}" class="btn btn-sm btn-secondary" style="border-radius: 6px; padding: 5px 15px;">Reset</a>
                @endif
            </form>

            {{-- Tombol Tambah membuka modal --}}
            <button type="button" class="btn btn-sm text-white" style="background-color: #d88656; border: none; border-radius: 6px; padding: 5px 15px;" data-bs-toggle="modal" data-bs-target="#modalTambahKategori">
                + Tambah Kategori
            </button>
        </div>
    </div>

    <div class="card-body">

        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0">
                <thead class="text-center" style="background-color: #5a3416;">
                    <tr>
                        <th style="width: 60px; color: #fff; border: none;">No</th>
                        <th style="color: #fff; border: none;" class="text-start">Nama Kategori</th>
                        <th style="width: 160px; color: #fff; border: none;">Prefix Kode</th>
                        <th style="width: 200px; color: #fff; border: none;">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($data as $d)
                        <tr>
                            <td class="text-center">
                                {{ $loop->iteration + ($data->currentPage() - 1) * $data->perPage() }}
                            </td>

                            <td class="fw-medium">{{ $d->nama }}</td>

                            <td class="text-center">
                                <span class="badge bg-light text-dark border font-monospace px-2 py-1">{{ $d->prefix ?? '-' }}</span>
                            </td>

                            <td class="text-center">
                                {{-- Tombol Detil membuka modal detail --}}
                                <button type="button"
                                        class="btn btn-info btn-sm text-white"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalDetailKategori"
                                        data-nama="{{ $d->nama }}"
                                        data-prefix="{{ $d->prefix ?? '-' }}">
                                    Detil
                                </button>

                                {{-- Tombol Edit membuka modal edit, terisi otomatis --}}
                                <button type="button"
                                        class="btn btn-warning btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalEditKategori"
                                        data-id="{{ $d->id }}"
                                        data-nama="{{ $d->nama }}"
                                        data-prefix="{{ $d->prefix }}"
                                        data-action="{{ route('kategori.update', $d->id) }}">
                                    Edit
                                </button>

                                {{-- Tombol Hapus membuka modal konfirmasi --}}
                                <button type="button"
                                        class="btn btn-danger btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalHapusKategori"
                                        data-nama="{{ $d->nama }}"
                                        data-action="{{ route('kategori.destroy', $d->id) }}">
                                    Hapus
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
                                Data kategori belum ada.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $data->links() }}
        </div>

    </div>
</div>


{{-- ================= MODAL TAMBAH KATEGORI ================= --}}
<div class="modal fade" id="modalTambahKategori" tabindex="-1" aria-labelledby="modalTambahKategoriLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 14px; border: none; overflow: hidden;">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold" id="modalTambahKategoriLabel" style="color: #2d3748;">Tambah Kategori Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('kategori.store') }}" method="POST">
                @csrf
                <input type="hidden" name="_form" value="create">

                <div class="modal-body px-4 pt-3 pb-4">
                    <div class="mb-3">
                        <label for="nama" class="form-label custom-label">Nama Kategori</label>
                        <input type="text" name="nama" id="nama" class="form-control custom-input @error('nama') is-invalid @enderror" value="{{ old('nama') }}" placeholder="Contoh: POWDER, SYRUP, COFFEE" required autocomplete="off">
                        @error('nama')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-1">
                        <label for="prefix" class="form-label custom-label">Prefix Kode Barang (Maks. 5 Karakter)</label>
                        <input type="text" name="prefix" id="prefix" class="form-control custom-input @error('prefix') is-invalid @enderror" value="{{ old('prefix') }}" placeholder="Contoh: POW, SYR, COF" maxlength="5" autocomplete="off" style="text-transform: uppercase;">
                        @error('prefix')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0 d-flex justify-content-end gap-2">
                    <button type="button" class="btn custom-btn-batal" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn text-white custom-btn-simpan">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>


{{-- ================= MODAL DETAIL KATEGORI ================= --}}
<div class="modal fade" id="modalDetailKategori" tabindex="-1" aria-labelledby="modalDetailKategoriLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 14px; border: none; overflow: hidden;">
            <div class="modal-header text-white" style="background-color: #d88656;">
                <h5 class="modal-title fw-bold" id="modalDetailKategoriLabel">Informasi Kategori</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="custom-label">Nama Kategori</label>
                    <p class="fs-5 text-dark fw-semibold mb-0" id="detailNama"></p>
                </div>
                <div class="mb-0">
                    <label class="custom-label">Prefix Kode Barang</label>
                    <p class="fs-5 text-dark fw-semibold font-monospace mb-0" id="detailPrefix"></p>
                </div>
            </div>

            <div class="modal-footer border-0 px-4 pb-4 pt-0">
                <button type="button" class="btn custom-btn-batal w-100" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>


{{-- ================= MODAL EDIT KATEGORI ================= --}}
<div class="modal fade" id="modalEditKategori" tabindex="-1" aria-labelledby="modalEditKategoriLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 14px; border: none; overflow: hidden;">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold" id="modalEditKategoriLabel" style="color: #2d3748;">Ubah Kategori</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="formEditKategori" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="_form" value="edit">

                <div class="modal-body px-4 pt-3 pb-4">
                    <div class="mb-3">
                        <label for="editNama" class="form-label custom-label">Nama Kategori</label>
                        <input type="text" name="nama" id="editNama" class="form-control custom-input @error('nama') is-invalid @enderror" required autocomplete="off">
                        @error('nama')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-1">
                        <label for="editPrefix" class="form-label custom-label">Prefix Kode Barang (Maks. 5 Karakter)</label>
                        <input type="text" name="prefix" id="editPrefix" class="form-control custom-input @error('prefix') is-invalid @enderror" placeholder="Contoh: POW" maxlength="5" autocomplete="off" style="text-transform: uppercase;">
                        @error('prefix')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0 d-flex justify-content-end gap-2">
                    <button type="button" class="btn custom-btn-batal" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn text-white custom-btn-simpan">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>


{{-- ================= MODAL HAPUS KATEGORI ================= --}}
<div class="modal fade" id="modalHapusKategori" tabindex="-1" aria-labelledby="modalHapusKategoriLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 14px; border: none; overflow: hidden;">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold" id="modalHapusKategoriLabel" style="color: #2d3748;">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="formHapusKategori" method="POST">
                @csrf
                @method('DELETE')

                <div class="modal-body px-4 pt-2 pb-4">
                    <p class="mb-0 text-secondary">
                        Yakin ingin menghapus kategori
                        <strong id="hapusNama" class="text-dark"></strong>?
                        Tindakan ini tidak dapat dibatalkan.
                    </p>
                </div>

                <div class="modal-footer border-0 px-4 pb-4 pt-0 d-flex justify-content-end gap-2">
                    <button type="button" class="btn custom-btn-batal" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Ya, Hapus</button>
                </div>
            </form>
        </div>
    </div>
</div>


<style>
    .custom-label {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: #718096;
        font-weight: 700;
        display: block;
        margin-bottom: 6px;
    }
    .custom-input {
        border-radius: 8px !important;
        padding: 10px 12px !important;
        border: 1px solid #e2e8f0 !important;
        font-size: 14px !important;
    }
    .custom-input:focus {
        border-color: #d88656 !important;
        box-shadow: 0 0 0 3px rgba(216, 134, 86, 0.15) !important;
    }
    .custom-btn-batal {
        background-color: #f7fafc;
        border: 1px solid #e2e8f0;
        color: #4a5568;
        border-radius: 8px;
        padding: 8px 18px;
        font-size: 14px;
    }
    .custom-btn-batal:hover {
        background-color: #edf2f7;
    }
    .custom-btn-simpan {
        background-color: #d88656;
        border-radius: 8px;
        padding: 8px 18px;
        font-size: 14px;
        font-weight: 500;
        border: none;
    }
    .custom-btn-simpan:hover {
        background-color: #c87443;
    }
    .btn-close-white {
        filter: invert(1) grayscale(1) brightness(2);
    }
</style>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ---- Isi modal Detail dari data-attribute tombol yang diklik ----
    var modalDetail = document.getElementById('modalDetailKategori');
    modalDetail.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        document.getElementById('detailNama').innerText = button.getAttribute('data-nama');
        document.getElementById('detailPrefix').innerText = button.getAttribute('data-prefix');
    });

    // ---- Isi modal Edit dari data-attribute tombol yang diklik ----
    var modalEdit = document.getElementById('modalEditKategori');
    modalEdit.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        document.getElementById('editNama').value = button.getAttribute('data-nama');
        document.getElementById('editPrefix').value = button.getAttribute('data-prefix');
        document.getElementById('formEditKategori').action = button.getAttribute('data-action');
    });

    // ---- Set action form Hapus dari data-attribute tombol yang diklik ----
    var modalHapus = document.getElementById('modalHapusKategori');
    modalHapus.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        document.getElementById('hapusNama').innerText = button.getAttribute('data-nama');
        document.getElementById('formHapusKategori').action = button.getAttribute('data-action');
    });

    // ---- Auto-buka kembali modal Tambah/Edit jika ada error validasi ----
    @if ($errors->any())
        @if (old('_form') === 'edit')
            document.getElementById('editNama').value = "{{ old('nama') }}";
            document.getElementById('editPrefix').value = "{{ old('prefix') }}";
            new bootstrap.Modal(document.getElementById('modalEditKategori')).show();
        @else
            new bootstrap.Modal(document.getElementById('modalTambahKategori')).show();
        @endif
    @endif
});
</script>
@endpush

</x-app-layout>