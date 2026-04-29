<x-app-layout>
<div class="container">

<h3 class="mb-3">Edit Barang</h3>

<div class="card">
<div class="card-body">

<form action="{{ route('barang.update', $data->id) }}" method="POST">
@csrf
@method('PUT')

<div class="mb-3">
    <label>Kategori</label>
    <select name="kategori_id" class="form-control">
        @foreach($kategori as $k)
            <option value="{{ $k->id }}" {{ $data->kategori_id == $k->id ? 'selected' : '' }}>
                {{ $k->nama }}
            </option>
        @endforeach
    </select>
</div>

<div class="row">

    <div class="col-md-6 mb-3">
        <label>Kode Barang</label>
        <input type="text" name="kode_barang" value="{{ $data->kode_barang }}" class="form-control" required>
    </div>

    <div class="col-md-6 mb-3">
        <label>Nama Barang</label>
        <input type="text" name="nama" value="{{ $data->nama }}" class="form-control" required>
    </div>

    <div class="col-md-6 mb-3">
        <label>Satuan</label>
        <input type="text" name="satuan" value="{{ $data->satuan }}" class="form-control">
    </div>

    <div class="col-md-6 mb-3">
        <label>Jenis Barang</label>
        <select name="jenis_utama" id="jenis" class="form-control" required>
            <option value="BAHAN_BAKU" {{ $data->jenis_utama=='BAHAN_BAKU'?'selected':'' }}>Bahan Baku</option>
            <option value="BARANG_JADI" {{ $data->jenis_utama=='BARANG_JADI'?'selected':'' }}>Barang Jadi</option>
            <option value="OPERATIONAL" {{ $data->jenis_utama=='OPERATIONAL'?'selected':'' }}>Operational</option>
        </select>
    </div>

</div>

<hr>

<!-- HARGA -->
<div id="group-harga" class="row">

    <div class="col-md-6 mb-3">
        <label>Harga B2B</label>
        <div class="input-group">
            <span class="input-group-text">Rp</span>
            <input type="text" name="harga_jual_b2b"
                value="{{ number_format($data->harga_jual_b2b,0,',','.') }}"
                class="form-control uang">
        </div>
    </div> 

    <div class="col-md-6 mb-3">
        <label>Harga POS</label>
        <div class="input-group">
            <span class="input-group-text">Rp</span>
            <input type="text" name="harga_jual_pos"
                value="{{ number_format($data->harga_jual_pos,0,',','.') }}"
                class="form-control uang">
        </div>
    </div>

</div>

<!-- HPP -->
<div id="group-hpp" class="row">
    <div class="col-md-6 mb-3">
        <label>HPP Referensi</label>
        <div class="input-group">
            <span class="input-group-text">Rp</span>
            <input type="text" name="hpp_referensi"
                value="{{ number_format($data->hpp_referensi,0,',','.') }}"
                class="form-control uang">
        </div>
    </div>
</div>

<div class="mt-3">
    <button class="btn btn-primary">Update</button>
    <a href="{{ route('barang.index') }}" class="btn btn-secondary">Kembali</a>
</div>

</form>

</div>
</div>

</div>

<style>
input.uang {
    text-align: right;
}
</style>

<script>
document.addEventListener("DOMContentLoaded", function () {

    const jenis = document.getElementById('jenis');
    const groupHarga = document.getElementById('group-harga');
    const groupHPP = document.getElementById('group-hpp');

    const b2b = document.querySelector('[name="harga_jual_b2b"]');
    const pos = document.querySelector('[name="harga_jual_pos"]');
    const hpp = document.querySelector('[name="hpp_referensi"]');

    const inputs = document.querySelectorAll('.uang');

    // ORMAT RUPIAH
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            let angka = this.value.replace(/\D/g, '');
            this.value = angka.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        });
    });

    // BERSIHKAN SEBELUM SUBMIT
    document.querySelector("form").addEventListener("submit", function() {
        inputs.forEach(input => {
            input.value = input.value.replace(/\./g, '');
        });
    });

    // 🎯 TOGGLE FORM
    function toggleForm() {

        if (jenis.value === 'BAHAN_BAKU' || jenis.value === 'OPERATIONAL') {

            groupHarga.style.opacity = "0.3";
            groupHPP.style.opacity = "1";

            b2b.disabled = true;
            pos.disabled = true;
            hpp.disabled = false;

        } else {

            groupHarga.style.opacity = "1";
            groupHPP.style.opacity = "0.3";

            b2b.disabled = false;
            pos.disabled = false;
            hpp.disabled = true;
        }
    }

    jenis.addEventListener('change', toggleForm);
    toggleForm();
});
</script>

</x-app-layout>