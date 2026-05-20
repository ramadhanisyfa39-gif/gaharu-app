<x-app-layout>

@if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="container">

<h3 class="mb-3">Tambah Barang</h3>

<div class="card">
<div class="card-body">

<form action="{{ route('barang.store') }}" method="POST">
@csrf

<div class="mb-3">
    <label>Kategori</label>
    <select name="kategori_id" class="form-control" required>
        <option value="">-- Pilih Kategori --</option>
        @foreach($kategori as $k)
            <option value="{{ $k->id }}">{{ $k->nama }}</option>
        @endforeach
    </select>
</div>

<div class="row">

    <div class="col-md-6 mb-3">
        <label>Kode Barang</label>
        <input type="text" name="kode_barang" class="form-control" required>
    </div>

    <div class="col-md-6 mb-3">
        <label>Nama Barang</label>
        <input type="text" name="nama" class="form-control" required>
    </div>

    <div class="col-md-6 mb-3">
        <label>Satuan</label>
        <input type="text" name="satuan" class="form-control">
    </div>

    <div class="col-md-6 mb-3">
        <label>Jenis Barang</label>
        <select name="jenis_utama" id="jenis" class="form-control" required>
            <option value="">-- Pilih Jenis --</option>
            <option value="BAHAN_BAKU">Bahan Baku</option>
            <option value="BARANG_JADI">Barang Jadi</option>
            <option value="OPERATIONAL">Operational</option>
        </select>
    </div>

</div>

<div class="mt-3">
    <button class="btn btn-primary">Simpan</button>
    <a href="{{ route('barang.index') }}" class="btn btn-secondary">Kembali</a>
</div>

</form>

</div>
</div>

</div>

</x-app-layout>