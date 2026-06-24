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

    <select name="kategori_id"
            id="kategori_id"
            class="form-control"
            required>

        <option value="">-- Pilih Kategori --</option>

        @foreach($kategori as $k)

            <option value="{{ $k->id }}">
                {{ $k->nama }}
            </option>

        @endforeach

    </select>
</div>

<div class="row">

    <div class="col-md-6 mb-3">

        <label>Kode Barang</label>

        <input type="text"
               name="kode_barang"
               id="kode_barang"
               class="form-control"
               readonly
               required>

    </div>

    <div class="col-md-6 mb-3">

        <label>Nama Barang</label>

        <input type="text"
               name="nama"
               class="form-control"
               required>

    </div>

    <div class="col-md-6 mb-3">

        <label>Satuan</label>

        <input type="text"
               name="satuan"
               class="form-control">

    </div>

    <div class="col-md-6 mb-3">

        <label>Jenis Barang</label>

        <select name="jenis_utama"
                id="jenis"
                class="form-control"
                required>

            <option value="">-- Pilih Jenis --</option>

            <option value="BAHAN_BAKU">
                Bahan Baku
            </option>

            <option value="BARANG_JADI">
                Barang Jadi
            </option>

            <option value="OPERATIONAL">
                Operational
            </option>

        </select>
    </div>

    <div class="col-md-6 mb-3" id="group-min-stock" style="display: none;">
        
        <label>Minimum Stock (Batas Kritis)</label>
        
        <input type="number"
               name="minimum_stock"
               id="minimum_stock"
               class="form-control"
               placeholder="Contoh: 10"
               min="0">

    </div>

</div>

<hr>

<div id="group-harga" class="row">

    <div class="col-md-6 mb-3">

        <label>Harga B2B</label>

        <div class="input-group">

            <span class="input-group-text">
                Rp
            </span>

            <input type="text"
                   name="harga_jual_b2b"
                   class="form-control uang">

        </div>

    </div>

    <div class="col-md-6 mb-3">

        <label>Harga POS</label>

        <div class="input-group">

            <span class="input-group-text">
                Rp
            </span>

            <input type="text"
                   name="harga_jual_pos"
                   class="form-control uang">

        </div>

    </div>

</div>

<div class="mt-3">

    <button class="btn btn-primary">
        Simpan
    </button>

    <a href="{{ route('barang.index') }}"
       class="btn btn-secondary">

       Kembali

    </a>

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
    const groupMinStock = document.getElementById('group-min-stock');

    const b2b = document.querySelector('[name="harga_jual_b2b"]');
    const pos = document.querySelector('[name="harga_jual_pos"]');
    const minStockInput = document.getElementById('minimum_stock');

    const inputs = document.querySelectorAll('.uang');

    // FORMAT RUPIAH
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

    // TOGGLE FORM (Harga dan Minimum Stock)
    function toggleForm() {

        if (jenis.value === 'BAHAN_BAKU' || jenis.value === 'OPERATIONAL') {

            // Nonaktifkan Harga
            groupHarga.style.opacity = "0.3";
            b2b.disabled = true;
            pos.disabled = true;

            // Tampilkan Minimum Stock
            groupMinStock.style.display = "block";

        }
        else if (jenis.value === 'BARANG_JADI') {

            // Aktifkan Harga
            groupHarga.style.opacity = "1";
            b2b.disabled = false;
            pos.disabled = false;

            // Sembunyikan Minimum Stock
            groupMinStock.style.display = "none";
            minStockInput.value = ''; // Kosongkan input saat disembunyikan

        } else {
            
            // Sembunyikan Minimum Stock jika opsi "-- Pilih Jenis --" dipilih
            groupMinStock.style.display = "none";
            minStockInput.value = '';

        }
    }

    jenis.addEventListener('change', toggleForm);

    // Panggil saat halaman pertama kali load (untuk menangani old input jika ada error validasi)
    toggleForm();

    // =====================================================
    // AUTO GENERATE KODE BARANG
    // =====================================================

    const kategori = document.getElementById('kategori_id');

    kategori.addEventListener('change', function () {

        let kategoriId = this.value;

        if (kategoriId == '') {

            document.getElementById('kode_barang').value = '';

            return;
        }

        fetch('/barang/generate-kode/' + kategoriId)

            .then(response => response.json())

            .then(data => {

                document.getElementById('kode_barang').value = data.kode_barang;

            });

    });

    // AUTO GENERATE SAAT HALAMAN DIBUKA
    if (kategori.value != '') {

        kategori.dispatchEvent(new Event('change'));

    }

});

</script>

</x-app-layout>