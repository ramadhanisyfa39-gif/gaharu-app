<x-app-layout>

<div class="container">

<h3 class="mb-3">Detail Penjualan POS</h3>

<div class="card mb-3">
<div class="card-body">

<div class="row">

    <div class="col-md-6">

        <table class="table">

            <tr>
                <th width="200">Kode</th>
                <td>{{ $penjualan->kode_transaksi }}</td>
            </tr>

            <tr>
                <th>Tanggal</th>
                <td>
                    {{ \Carbon\Carbon::parse($penjualan->tanggal)->format('d-m-Y H:i') }}
                </td>
            </tr>

            <tr>
                <th>Gudang</th>
                <td>{{ $penjualan->gudang->nama }}</td>
            </tr>

            <tr>
                <th>Input Oleh</th>
                <td>
                    {{ $penjualan->creator->name ?? '-' }}
                </td>
            </tr>

        </table>

    </div>

    <div class="col-md-6 text-end">

        <h5>Total Penjualan</h5>

        <h2 class="text-primary">
            Rp {{ number_format($penjualan->total, 0, ',', '.') }}
        </h2>

    </div>

</div>

</div>
</div>

<div class="card">
<div class="card-body">

<table class="table table-bordered">

    <thead class="table-light">
        <tr>
            <th>No</th>
            <th>Produk</th>
            <th>Qty</th>
            <th>Harga</th>
            <th>Subtotal</th>
        </tr>
    </thead>

    <tbody>

        @foreach($penjualan->details as $key => $d)

        <tr>

            <td>{{ $key + 1 }}</td>

            <td>{{ $d->produk->nama }}</td>

            <td>{{ $d->qty }}</td>

            <td>
                Rp {{ number_format($d->harga, 0, ',', '.') }}
            </td>

            <td>
                Rp {{ number_format($d->subtotal, 0, ',', '.') }}
            </td>

        </tr>

        @endforeach

    </tbody>

</table>

</div>
</div>

<div class="mt-3">

    <a href="{{ route('penjualan_pos.index') }}"
       class="btn btn-secondary">

       Kembali
    </a>

    <a href="{{ route('penjualan_pos.edit', $penjualan->id) }}"
       class="btn btn-warning">

       Edit
    </a>

</div>

</div>

</x-app-layout>