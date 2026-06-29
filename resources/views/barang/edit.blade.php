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

<h3 class="mb-3">Edit Barang</h3>

<div class="card">
<div class="card-body">

<form action="{{ route('barang.update', $data->id) }}" method="POST">
@csrf
@method('PUT')

<div class="mb-3">
    <label class="fw-semibold">Kategori</label>
    <select name="kategori_id"
            id="kategori_id"
            class="form-control"
            required>

        <option value="">-- Pilih Kategori --</option>

        @foreach($kategori as $k)
            <option value="{{ $k->id }}" {{ $data->kategori_id == $k->id ? 'selected' : '' }}>
                {{ $k->nama }}
            </option>
        @endforeach

    </select>
</div>

<div class="row">

    <div class="col-md-6 mb-3">
        <label class="fw-semibold">Kode Barang</label>
        <input type="text"
               name="kode_barang"
               id="kode_barang"
               class="form-control"
               value="{{ old('kode_barang', $data->kode_barang) }}"
               readonly
               required>
        {{-- Kode tidak berubah saat edit, cukup readonly --}}
    </div>

    <div class="col-md-6 mb-3">
        <label class="fw-semibold">Nama Barang</label>
        <input type="text"
               name="nama"
               class="form-control"
               value="{{ old('nama', $data->nama) }}"
               required>
    </div>

    <div class="col-md-6 mb-3">
        <label class="fw-semibold">Satuan</label>
        <input type="text"
               name="satuan"
               class="form-control"
               value="{{ old('satuan', $data->satuan) }}"
               placeholder="Contoh: kg, pcs, liter"
               required>
    </div>

    <div class="col-md-6 mb-3">
        <label class="fw-semibold">Jenis Barang</label>
        <select name="jenis_utama"
                id="jenis"
                class="form-control"
                required>

            <option value="">-- Pilih Jenis --</option>

            <option value="BAHAN_BAKU"   {{ old('jenis_utama', $data->jenis_utama) == 'BAHAN_BAKU'   ? 'selected' : '' }}>Bahan Baku</option>
            <option value="BARANG_JADI"  {{ old('jenis_utama', $data->jenis_utama) == 'BARANG_JADI'  ? 'selected' : '' }}>Barang Jadi</option>
            <option value="OPERATIONAL"  {{ old('jenis_utama', $data->jenis_utama) == 'OPERATIONAL'  ? 'selected' : '' }}>Operational</option>

        </select>
    </div>

    <div class="col-md-6 mb-3" id="group-min-stock" style="display: none;">
        <label class="fw-semibold text-danger">Minimum Stock (Batas Kritis)</label>
        <input type="number"
               name="minimum_stock"
               id="minimum_stock"
               class="form-control"
               placeholder="Contoh: 10"
               min="0"
               value="{{ old('minimum_stock', $data->minimum_stock) }}">
    </div>

</div>

<hr>

<div id="group-harga" class="row">

    <div class="col-md-6 mb-3">
        <label class="fw-semibold">Harga B2B</label>
        <div class="input-group">
            <span class="input-group-text">Rp</span>
            <input type="text"
                   name="harga_jual_b2b"
                   class="form-control uang"
                   value="{{ old('harga_jual_b2b', number_format($data->harga_jual_b2b, 0, ',', '.')) }}">
        </div>
    </div>

    <div class="col-md-6 mb-3">
        <label class="fw-semibold">Harga POS</label>
        <div class="input-group">
            <span class="input-group-text">Rp</span>
            <input type="text"
                   name="harga_jual_pos"
                   class="form-control uang"
                   value="{{ old('harga_jual_pos', number_format($data->harga_jual_pos, 0, ',', '.')) }}">
        </div>
    </div>

</div>

<div class="mt-3">
    <button type="submit" class="btn btn-primary">Update</button>
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

    const jenis        = document.getElementById('jenis');
    const groupHarga   = document.getElementById('group-harga');
    const groupMinStock = document.getElementById('group-min-stock');
    const b2b          = document.querySelector('[name="harga_jual_b2b"]');
    const pos          = document.querySelector('[name="harga_jual_pos"]');
    const minStockInput = document.getElementById('minimum_stock');
    const inputs       = document.querySelectorAll('.uang');

    // FORMAT RUPIAH — saat user mengetik
    inputs.forEach(input => {
        input.addEventListener('input', function () {
            let angka = this.value.replace(/\D/g, '');
            this.value = angka.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        });
    });

    // BERSIHKAN TITIK SEBELUM SUBMIT agar nilai bersih dikirim ke controller
    document.querySelector("form").addEventListener("submit", function () {
        inputs.forEach(input => {
            input.value = input.value.replace(/\./g, '');
        });
    });

    // TOGGLE TAMPILAN HARGA & MINIMUM STOCK
    function toggleForm() {
        if (jenis.value === 'BAHAN_BAKU' || jenis.value === 'OPERATIONAL') {
            groupHarga.style.opacity = "0.3";
            b2b.readOnly = true;
            pos.readOnly = true;
            b2b.value    = '0';
            pos.value    = '0';
            groupMinStock.style.display = "block";
        }
        else if (jenis.value === 'BARANG_JADI') {
            groupHarga.style.opacity = "1";
            b2b.readOnly = false;
            pos.readOnly = false;
            groupMinStock.style.display = "none";
            minStockInput.value = '';
        } else {
            groupMinStock.style.display = "none";
            minStockInput.value = '';
        }
    }

    jenis.addEventListener('change', toggleForm);

    // Jalankan saat load agar state form sesuai data yang sudah ada
    toggleForm();

    // Setelah toggleForm, pastikan nilai harga tidak di-reset jika BARANG_JADI
    // (karena toggleForm di atas tidak mengubah nilai harga untuk BARANG_JADI)
});
</script>

</x-app-layout>
