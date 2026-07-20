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
            <button type="button" class="btn btn-primary mb-0" data-bs-toggle="modal" data-bs-target="#createModal">
                + Tambah Customer
            </button>

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
                            <button type="button" class="btn btn-info btn-sm text-white btn-detil"
                                data-bs-toggle="modal" data-bs-target="#showModal"
                                data-nama="{{ $item->nama }}"
                                data-jenis="{{ $item->jenis }}"
                                data-no_hp="{{ $item->no_hp }}"
                                data-alamat="{{ $item->alamat }}">
                                Detil
                            </button>

                            <button type="button" class="btn btn-warning btn-sm btn-edit"
                                data-bs-toggle="modal" data-bs-target="#editModal"
                                data-id="{{ $item->id }}"
                                data-nama="{{ $item->nama }}"
                                data-jenis="{{ $item->jenis }}"
                                data-no_hp="{{ $item->no_hp }}"
                                data-alamat="{{ $item->alamat }}">
                                Edit
                            </button>

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

{{-- ==================== MODAL: TAMBAH CUSTOMER ==================== --}}
<div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('customer.store') }}" method="POST">
                @csrf
                <input type="hidden" name="form_type" value="create">

                <div class="modal-header">
                    <h5 class="modal-title">Tambah Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    @if ($errors->any() && old('form_type') === 'create')
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="mb-3">
                        <label>Nama</label>
                        <input type="text" name="nama" value="{{ old('form_type') === 'create' ? old('nama') : '' }}" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label>Jenis Pelanggan</label>
                        <select name="jenis" class="form-control">
                            <option value="">-- Pilih Jenis --</option>
                            <option value="Internal" {{ old('form_type') === 'create' && old('jenis') == 'Internal' ? 'selected' : '' }}>Internal</option>
                            <option value="Reseller" {{ old('form_type') === 'create' && old('jenis') == 'Reseller' ? 'selected' : '' }}>Reseller</option>
                            <option value="Horeca" {{ old('form_type') === 'create' && old('jenis') == 'Horeca' ? 'selected' : '' }}>Horeca</option>
                            <option value="Corporate" {{ old('form_type') === 'create' && old('jenis') == 'Corporate' ? 'selected' : '' }}>Corporate</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>No HP</label>
                        <input type="text" name="no_hp" value="{{ old('form_type') === 'create' ? old('no_hp') : '' }}" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label>Alamat</label>
                        <textarea name="alamat" class="form-control">{{ old('form_type') === 'create' ? old('alamat') : '' }}</textarea>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ==================== MODAL: EDIT CUSTOMER ==================== --}}
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="" method="POST" id="editForm">
                @csrf
                @method('PUT')
                <input type="hidden" name="form_type" value="edit">
                <input type="hidden" name="id" id="edit_id">

                <div class="modal-header">
                    <h5 class="modal-title">Edit Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    @if ($errors->any() && old('form_type') === 'edit')
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="mb-3">
                        <label>Nama</label>
                        <input type="text" name="nama" id="edit_nama" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label>Jenis Pelanggan</label>
                        <select name="jenis" id="edit_jenis" class="form-control">
                            <option value="">-- Pilih Jenis --</option>
                            <option value="Internal">Internal</option>
                            <option value="Reseller">Reseller</option>
                            <option value="Horeca">Horeca</option>
                            <option value="Corporate">Corporate</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>No HP</label>
                        <input type="text" name="no_hp" id="edit_no_hp" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label>Alamat</label>
                        <textarea name="alamat" id="edit_alamat" class="form-control"></textarea>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ==================== MODAL: DETIL CUSTOMER ==================== --}}
<div class="modal fade" id="showModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header text-white" style="background-color: #d88656;">
                <h5 class="modal-title fw-bold">Informasi Customer</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="fw-bold text-muted small text-uppercase">Nama Customer</label>
                    <p class="fs-5 text-dark fw-semibold" id="show_nama"></p>
                </div>

                <div class="mb-3">
                    <label class="fw-bold text-muted small text-uppercase">Jenis</label>
                    <p class="fs-6"><span class="badge bg-primary" id="show_jenis"></span></p>
                </div>

                <div class="mb-3">
                    <label class="fw-bold text-muted small text-uppercase">Nomor HP</label>
                    <p class="fs-6 text-dark" id="show_no_hp"></p>
                </div>

                <div class="mb-3">
                    <label class="fw-bold text-muted small text-uppercase">Alamat</label>
                    <p class="fs-6 text-dark" id="show_alamat"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Isi modal Detil dari data-* pada tombol yang diklik
    document.querySelectorAll('.btn-detil').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('show_nama').innerText = this.dataset.nama;
            document.getElementById('show_jenis').innerText = this.dataset.jenis;
            document.getElementById('show_no_hp').innerText = this.dataset.no_hp;
            document.getElementById('show_alamat').innerText = this.dataset.alamat;
        });
    });

    // Isi modal Edit dari data-* pada tombol yang diklik + set action form sesuai id
    var editUrlTemplate = "{{ route('customer.update', ':id') }}";

    document.querySelectorAll('.btn-edit').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('editForm').action = editUrlTemplate.replace(':id', this.dataset.id);
            document.getElementById('edit_id').value = this.dataset.id;
            document.getElementById('edit_nama').value = this.dataset.nama;
            document.getElementById('edit_jenis').value = this.dataset.jenis;
            document.getElementById('edit_no_hp').value = this.dataset.no_hp;
            document.getElementById('edit_alamat').value = this.dataset.alamat;
        });
    });

    // Kalau validasi gagal, buka kembali modal yang sesuai secara otomatis
    document.addEventListener('DOMContentLoaded', function () {
        @if ($errors->any() && old('form_type') === 'create')
            new bootstrap.Modal(document.getElementById('createModal')).show();
        @elseif ($errors->any() && old('form_type') === 'edit')
            document.getElementById('editForm').action = editUrlTemplate.replace(':id', "{{ old('id') }}");
            document.getElementById('edit_nama').value = "{{ old('nama') }}";
            document.getElementById('edit_jenis').value = "{{ old('jenis') }}";
            document.getElementById('edit_no_hp').value = "{{ old('no_hp') }}";
            document.getElementById('edit_alamat').value = "{{ old('alamat') }}";
            new bootstrap.Modal(document.getElementById('editModal')).show();
        @endif
    });
</script>

</x-app-layout>