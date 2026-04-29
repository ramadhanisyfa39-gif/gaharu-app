@extends('layouts.app')

@section('content')
<div class="container">
    <h4>Detail Pembelian</h4>

    <div class="card mb-3">
        <div class="card-body">
            <p><strong>Kode:</strong> {{ $pembelian->kode_pembelian }}</p>
            <p><strong>Tanggal:</strong> {{ $pembelian->tanggal }}</p>
            <p><strong>Supplier:</strong> {{ $pembelian->supplier->nama ?? '-' }}</p>
            <p><strong>Gudang:</strong> {{ $pembelian->gudang->nama ?? '-' }}</p>
            <p><strong>Total:</strong> Rp {{ number_format($pembelian->total, 0, ',', '.') }}</p>
        </div>
    </div>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Barang</th>
                <th>Qty</th>
                <th>Harga</th>
                <th>Subtotal</th>
                <th>Batch</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pembelian->details as $detail)
                <tr>
                    <td>{{ $detail->barang->nama ?? '-' }}</td>
                    <td>{{ $detail->qty }}</td>
                    <td>Rp {{ number_format($detail->harga, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($detail->qty * $detail->harga, 0, ',', '.') }}</td>
                    <td>{{ $detail->batch_number ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <a href="{{ route('pembelian.index') }}" class="btn btn-light">
        Kembali
    </a>
</div>
@endsection