@extends('layouts.app')

@section('content')
<div class="container">

    <h2 class="mb-4">Tambah Barang</h2>

    <div class="card">
        <div class="card-body">

            <form action="{{ route('barang.store') }}" method="POST">
                @csrf

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Kategori</label>
                        <select name="kategori_id" class="form-control">
                            <option value="">-- Pilih --</option>
                            @foreach($kategori as $k)
                                <option value="{{ $k->id }}">{{ $k->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label>Kode Barang</label>
                        <input type="text" name="kode_barang" class="form-control">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label>Nama</label>
                        <input type="text" name="nama" class="form-control">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label>Satuan</label>
                        <input type="text" name="satuan" class="form-control">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label>Jenis Barang</label>
                        <select name="jenis_utama" id="jenis" class="form-control">
                            <option value="">-- Pilih --</option>
                            <option value="BAHAN_BAKU">Bahan Baku</option>
                            <option value="BARANG_JADI">Barang Jadi</option>
                            <option value="OPERATIONAL">Operational</option>
                        </select>
                    </div>
                </div>

                <hr>

                <div class="row" id="group-harga">
                    <div class="col-md-4 mb-3">
                        <label>Harga B2B</label>
                        <input type="number" name="harga_jual_b2b" class="form-control">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label>Harga POS</label>
                        <input type="number" name="harga_jual_pos" class="form-control">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label>HPP Referensi</label>
                        <input type="number" name="hpp_referensi" class="form-control">
                    </div>
                </div>

                <button class="btn btn-success">Simpan</button>
                <a href="{{ route('barang.index') }}" class="btn btn-secondary">Kembali</a>

            </form>

        </div>
    </div>

</div>

<script>
document.getElementById('jenis').addEventListener('change', function() {
    let jenis = this.value;
    let hargaGroup = document.getElementById('group-harga');

    if (jenis === 'BAHAN_BAKU' || jenis === 'OPERATIONAL') {
        hargaGroup.style.display = 'none';
    } else {
        hargaGroup.style.display = 'flex';
    }
});
</script>
@endsection