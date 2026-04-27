@extends('layouts.app')

@section('content')
<div class="container">

    <h2 class="mb-4">Edit Barang</h2>

    <div class="card">
        <div class="card-body">

            <form action="{{ route('barang.update', $data->id) }}" method="POST">
                @csrf
                @method('PUT')

                <!-- Kategori -->
                <div class="mb-3">
                    <label>Kategori</label>
                    <select name="kategori_id" class="form-control">
                        @foreach($kategori as $k)
                            <option value="{{ $k->id }}" 
                                {{ $data->kategori_id == $k->id ? 'selected' : '' }}>
                                {{ $k->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Kode -->
                <div class="mb-3">
                    <label>Kode Barang</label>
                    <input type="text" name="kode_barang" class="form-control"
                           value="{{ old('kode_barang', $data->kode_barang) }}">
                </div>

                <!-- Nama -->
                <div class="mb-3">
                    <label>Nama</label>
                    <input type="text" name="nama" class="form-control"
                           value="{{ old('nama', $data->nama) }}">
                </div>

                <!-- Satuan -->
                <div class="mb-3">
                    <label>Satuan</label>
                    <input type="text" name="satuan" class="form-control"
                           value="{{ old('satuan', $data->satuan) }}">
                </div>

                <hr>

                <!-- Checkbox -->
                <label>Jenis Barang:</label><br>

                <input type="checkbox" name="is_bahan_baku" 
                    {{ $data->is_bahan_baku ? 'checked' : '' }}> Bahan Baku<br>

                <input type="checkbox" name="is_barang_jadi" 
                    {{ $data->is_barang_jadi ? 'checked' : '' }}> Barang Jadi<br>

                <input type="checkbox" name="is_operational" 
                    {{ $data->is_operational ? 'checked' : '' }}> Operational<br>

                <input type="checkbox" name="is_direct_consumption" 
                    {{ $data->is_direct_consumption ? 'checked' : '' }}> Direct Consumption<br>

                <hr>

                <!-- Harga -->
                <div class="mb-3">
                    <label>Harga B2B</label>
                    <input type="number" name="harga_jual_b2b" class="form-control"
                           value="{{ old('harga_jual_b2b', $data->harga_jual_b2b) }}">
                </div>

                <div class="mb-3">
                    <label>Harga POS</label>
                    <input type="number" name="harga_jual_pos" class="form-control"
                           value="{{ old('harga_jual_pos', $data->harga_jual_pos) }}">
                </div>

                <div class="mb-3">
                    <label>HPP Referensi</label>
                    <input type="number" name="hpp_referensi" class="form-control"
                           value="{{ old('hpp_referensi', $data->hpp_referensi) }}">
                </div>

                <button class="btn btn-primary">Update</button>
                <a href="{{ route('barang.index') }}" class="btn btn-secondary">Kembali</a>

            </form>

        </div>
    </div>

</div>
@endsection