<x-app-layout>

<div class="card">
    <div class="card-header">
        <h4>Tambah Customer</h4>
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

        <form action="{{ route('customer.store') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label>Nama</label>
                <input type="text" name="nama" class="form-control">
            </div>

            <div class="mb-3">
                <label>Jenis Pelanggan</label>
                <div class="mb-3">
                <select name="jenis" class="form-control">
                    <option value="">-- Pilih Jenis --</option>
                    <option value="Internal">Internal</option>
                    <option value="Reseller">Reseller</option>
                    <option value="Horeca">Horeca</option>
                    <option value="Corporate">Corporate</option>
                </select>
            </div>
            </div>

            <div class="mb-3">
                <label>No HP</label>
                <input type="text" name="no_hp" class="form-control">
            </div>

            <div class="mb-3">
                <label>Alamat</label>
                <textarea name="alamat" class="form-control"></textarea>
            </div>

            <button class="btn btn-primary">Simpan</button>
            <a href="{{ route('customer.index') }}" class="btn btn-secondary">Kembali</a>
        </form>

    </div>
</div>

</x-app-layout>