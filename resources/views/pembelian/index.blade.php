@extends('layouts.app')

@section('content')
<div class="container">
    <h4>Data Pembelian</h4>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <a href="{{ route('pembelian.create') }}" class="btn btn-primary mb-3">
        Tambah Pembelian
    </a>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Kode</th>
                <th>Tanggal</th>
                <th>Supplier</th>
                <th>Gudang</th>
                <th>Total</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($pembelian as $item)
                <tr>
                    <td>{{ $item->kode_pembelian }}</td>
                    <td>{{ $item->tanggal }}</td>
                    <td>{{ $item->supplier->nama ?? '-' }}</td>
                    <td>{{ $item->gudang->nama ?? '-' }}</td>
                    <td>Rp {{ number_format($item->total, 0, ',', '.') }}</td>
                    <td>
                        <a href="{{ route('pembelian.show', $item->id) }}" class="btn btn-sm btn-info">
                            Detail
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">Belum ada data pembelian.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{ $pembelian->links() }}
</div>
@endsection