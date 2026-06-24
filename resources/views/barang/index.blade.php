<x-app-layout>
<div class="container mt-4">

    <h3 class="mb-3">Data Barang</h3>

    <button type="button" class="btn btn-primary mb-3" id="btn-tambah" data-bs-toggle="modal" data-bs-target="#modalBarang">
        + Tambah Barang
    </button>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <table class="table table-bordered table-hover align-middle text-center">
                <thead class="table-light">
                    <tr>
                        <th>Kode</th>
                        <th>Nama</th>
                        <th>Kategori</th>
                        <th>Satuan</th>
                        <th>Jenis</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $d)
                    <tr>
                        <td>{{ $d->kode_barang }}</td>
                        <td class="text-start fw-semibold">{{ $d->nama }}</td>
                        <td>{{ $d->kategori->nama ?? '-' }}</td>
                        <td>{{ $d->satuan }}</td>
                        <td>
                            @if($d->is_bahan_baku)
                                <span class="badge bg-primary-subtle text-primary px-3 py-2">Bahan Baku</span>
                            @elseif($d->is_barang_jadi)
                                <span class="badge bg-success-subtle text-success px-3 py-2">Barang Jadi</span>
                            @elseif($d->is_operational)
                                <span class="badge bg-warning-subtle text-dark px-3 py-2">Operational</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex justify-content-center gap-1">
                                <button type="button" 
                                        class="btn btn-warning btn-sm btn-edit"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#modalBarang"
                                        data-id="{{ $d->id }}"
                                        data-kode_barang="{{ $d->kode_barang }}"
                                        data-nama="{{ $d->nama }}"
                                        data-kategori_id="{{ $d->kategori_id }}"
                                        data-satuan="{{ $d->satuan }}"
                                        data-jenis="{{ $d->is_bahan_baku ? 'BAHAN_BAKU' : ($d->is_barang_jadi ? 'BARANG_JADI' : 'OPERATIONAL') }}">
                                    Edit
                                </button>

                                <form action="{{ route('barang.destroy',$d->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button onclick="return confirm('Yakin hapus data ini?')" class="btn btn-danger btn-sm">
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">Data belum tersedia</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalBarang" data-bs-backdrop="static" tabindex="-1" aria-labelledby="modalBarangTitle" aria-hidden="true">
    <div class="modal-dialog modal-md"> <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalBarangTitle">Tambah Barang</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="form-barang" action="{{ route('barang.store') }}" method="POST">
                @csrf
                <input type="hidden" name="_method" id="form-method" value="POST">

                <div class="modal-body">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Kategori</label>
                        <select name="kategori_id" id="kategori_id" class="form-control" required>
                            <option value="">-- Pilih Kategori --</option>
                            @foreach($kategori as $k)
                                <option value="{{ $k->id }}">{{ $k->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Kode Barang</label>
                        <input type="text" name="kode_barang" id="kode_barang" class="form-control bg-light" readonly required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Barang</label>
                        <input type="text" name="nama" id="nama" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Satuan</label>
                        <input type="text" name="satuan" id="satuan" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Jenis Barang</label>
                        <select name="jenis_utama" id="jenis" class="form-control" required>
                            <option value="">-- Pilih Jenis --</option>
                            <option value="BAHAN_BAKU">Bahan Baku</option>
                            <option value="BARANG_JADI">Barang Jadi</option>
                            <option value="OPERATIONAL">Operational</option>
                        </select>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const modalElement = document.getElementById('modalBarang');
    const myModal = new bootstrap.Modal(modalElement);
    
    const form = document.getElementById('form-barang');
    const formMethod = document.getElementById('form-method');
    const modalTitle = document.getElementById('modalBarangTitle');
    
    const kategori = document.getElementById('kategori_id');
    const kodeBarangInput = document.getElementById('kode_barang');
    
    let isEditMode = false;

    // AUTO GENERATE KODE BARANG (Hanya aktif saat Tambah Data Baru)
    kategori.addEventListener('change', function () {
        if (isEditMode) return; 
        
        let kategoriId = this.value;
        if (kategoriId == '') {
            kodeBarangInput.value = '';
            return;
        }

        fetch('/barang/generate-kode/' + kategoriId)
            .then(response => response.json())
            .then(data => {
                kodeBarangInput.value = data.kode_barang;
            });
    });

    // TRIGGER MODAL: TAMBAH DATA
    document.getElementById('btn-tambah').addEventListener('click', function() {
        isEditMode = false;
        modalTitle.innerText = "Tambah Barang";
        form.action = "{{ route('barang.store') }}";
        formMethod.value = "POST";
        
        form.reset();
    });

    // TRIGGER MODAL: EDIT DATA
    document.querySelectorAll('.btn-edit').forEach(button => {
        button.addEventListener('click', function() {
            isEditMode = true;
            modalTitle.innerText = "Edit Barang";
            
            const id = this.dataset.id;
            form.action = `/barang/${id}`; 
            formMethod.value = "PUT";

            // Pasang data lama langsung ke input form modal
            document.getElementById('kategori_id').value = this.dataset.kategori_id;
            document.getElementById('kode_barang').value = this.dataset.kode_barang;
            document.getElementById('nama').value = this.dataset.nama;
            document.getElementById('satuan').value = this.dataset.satuan;
            document.getElementById('jenis').value = this.dataset.jenis;
        });
    });

    // Jika ada error validasi dari server, buka kembali modal secara otomatis
    @if ($errors->any())
        myModal.show();
    @endif
});
</script>
</x-app-layout>