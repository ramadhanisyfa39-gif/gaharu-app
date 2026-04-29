@extends('layouts.app')

@section('content')
<div class="container">
    <h4>Tambah Pembelian</h4>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('pembelian.store') }}" method="POST">
        @csrf

        <div class="row mb-3">
            <div class="col-md-4">
                <label>Supplier</label>
                <select name="supplier_id" class="form-control" required>
                    <option value="">-- Pilih Supplier --</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}">
                            {{ $supplier->nama }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label>Gudang Tujuan</label>
                <select name="gudang_id" class="form-control" required>
                    <option value="">-- Pilih Gudang --</option>
                    @foreach($gudangs as $gudang)
                        <option value="{{ $gudang->id }}">
                            {{ $gudang->nama }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label>Tanggal</label>
                <input type="date" name="tanggal" class="form-control" value="{{ date('Y-m-d') }}" required>
            </div>
        </div>

        <hr>

        <h5>Detail Barang</h5>

        <table class="table table-bordered" id="table-items">
            <thead>
                <tr>
                    <th>Barang</th>
                    <th width="120">Qty</th>
                    <th width="160">Harga</th>
                    <th width="160">Batch Number</th>
                    <th width="80">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <select name="items[0][barang_id]" class="form-control" required>
                            <option value="">-- Pilih Barang --</option>
                            @foreach($barangs as $barang)
                                <option value="{{ $barang->id }}">
                                    {{ $barang->kode_barang }} - {{ $barang->nama }}
                                </option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <input type="number" step="0.01" name="items[0][qty]" class="form-control" required>
                    </td>
                    <td>
                        <input type="number" step="0.01" name="items[0][harga]" class="form-control" required>
                    </td>
                    <td>
                        <input type="text" name="items[0][batch_number]" class="form-control">
                    </td>
                    <td>
                        <button type="button" class="btn btn-danger btn-sm btn-remove">
                            X
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>

        <button type="button" class="btn btn-secondary" id="btn-add">
            Tambah Baris
        </button>

        <button type="submit" class="btn btn-primary">
            Simpan Pembelian
        </button>

        <a href="{{ route('pembelian.index') }}" class="btn btn-light">
            Kembali
        </a>
    </form>
</div>

<script>
    let rowIndex = 1;

    document.getElementById('btn-add').addEventListener('click', function () {
        const tbody = document.querySelector('#table-items tbody');

        const row = `
            <tr>
                <td>
                    <select name="items[${rowIndex}][barang_id]" class="form-control" required>
                        <option value="">-- Pilih Barang --</option>
                        @foreach($barangs as $barang)
                            <option value="{{ $barang->id }}">
                                {{ $barang->kode_barang }} - {{ $barang->nama }}
                            </option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <input type="number" step="0.01" name="items[${rowIndex}][qty]" class="form-control" required>
                </td>
                <td>
                    <input type="number" step="0.01" name="items[${rowIndex}][harga]" class="form-control" required>
                </td>
                <td>
                    <input type="text" name="items[${rowIndex}][batch_number]" class="form-control">
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm btn-remove">
                        X
                    </button>
                </td>
            </tr>
        `;

        tbody.insertAdjacentHTML('beforeend', row);
        rowIndex++;
    });

    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('btn-remove')) {
            const rows = document.querySelectorAll('#table-items tbody tr');

            if (rows.length > 1) {
                e.target.closest('tr').remove();
            }
        }
    });
</script>
@endsection