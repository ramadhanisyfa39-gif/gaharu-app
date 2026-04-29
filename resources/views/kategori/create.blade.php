<x-app-layout>
<div class="container">

    <h2 class="mb-4">Tambah Kategori</h2>

    <div class="card">
        <div class="card-body">

            <form action="{{ route('kategori.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Nama Kategori</label>
                    <input type="text" name="nama" class="form-control" placeholder="Masukkan nama kategori">

                    @error('nama_kategori')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success">Simpan</button>

                    <a href="{{ route('kategori.index') }}" class="btn btn-secondary">
                        Kembali
                    </a>
                </div>

            </form>

        </div>
    </div>

</div>
</x-app-layout>