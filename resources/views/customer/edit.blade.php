<x-app-layout>

<div class="card">
    <div class="card-header">
        <h4>Edit Customer</h4>
    </div>

    <div class="card-body">

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('customer.update', $data->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label>Nama</label>
                <input type="text" name="nama" value="{{ $data->nama }}" class="form-control">
            </div>

            <div class="mb-3">
                <label>Jenis Pelanggan</label>
                    <select name="jenis" class="form-control">
                        <option value="">-- Pilih Jenis --</option>
                        <option value="Internal" {{ $data->jenis == 'internal' ? 'selected' : '' }}>Internal</option>
                        <option value="Reseller" {{ $data->jenis == 'reseller' ? 'selected' : '' }}>Reseller</option>
                        <option value="Horeca" {{ $data->jenis == 'horeca' ? 'selected' : '' }}>Horeca</option>
                        <option value="Corporate" {{ $data->jenis == 'corporate' ? 'selected' : '' }}>Corporate</option>
                    </select>
            </div>
            <div class="mb-3">
                <label>No HP</label>
                <input type="text" name="no_hp" value="{{ $data->no_hp }}" class="form-control">
            </div>

            <div class="mb-3">
                <label>Alamat</label>
                <textarea name="alamat" class="form-control">{{ $data->alamat }}</textarea>
            </div>

            <button class="btn btn-primary">Update</button>
            <a href="{{ route('customer.index') }}" class="btn btn-secondary">Kembali</a>
        </form>

    </div>
</div>

</x-app-layout>