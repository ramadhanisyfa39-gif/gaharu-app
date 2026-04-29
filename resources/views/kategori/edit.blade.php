<x-app-layout>
<div class="container">

    <h2 class="mb-4">Edit Kategori</h2>

    <div class="card">
        <div class="card-body">

            <form action="{{ route('kategori.update', $data->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label">Nama Kategori</label>
                    <input type="text" 
                           name="nama" 
                           class="form-control" 
                           value="{{ old('nama', $data->nama) }}" 
                           placeholder="Masukkan nama kategori">

                    @error('nama')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success">
                        Update
                    </button>

                    <a href="{{ route('kategori.index') }}" class="btn btn-secondary">
                        Kembali
                    </a>
                </div>

            </form>

        </div>
    </div>

</div>
</x-app-layout>