<x-app-layout>
<div class="container">

<h3>Resep: {{ $resep->produk->nama }}</h3>

{{-- INFO RESEP --}}
<div class="alert alert-info">
    Output: {{ (int) $resep->output_qty }} {{ $resep->satuan_output }} <br>
    BTKL per batch: Rp {{ number_format($resep->btkl_per_batch) }} <br>
    BOP per batch: Rp {{ number_format($resep->bop_per_batch) }}
</div>

{{-- HITUNG PER PRODUK --}}
@php
    $btkl_per_produk = $resep->btkl_per_batch / $resep->output_qty;
    $bop_per_produk  = $resep->bop_per_batch / $resep->output_qty;
    $total_bahan = 0;
@endphp

<div class="alert alert-success">
    BTKL / Produk: Rp {{ number_format($btkl_per_produk) }} <br>
    BOP / Produk: Rp {{ number_format($bop_per_produk) }}
</div>

{{-- TABEL BAHAN --}}
<table class="table table-bordered text-center">

<tr>
    <th>Bahan</th>
    <th>Qty / Produk</th>
    <th>Harga</th>
    <th>Subtotal</th>
    <th>Satuan</th>
</tr>

@foreach($bahan as $b)

@php
    $harga = $b->bahan->hpp_referensi ?? 0;
    $subtotal = $b->qty_bahan * $harga;
    $total_bahan += $subtotal;
@endphp

<tr>
    <td>{{ $b->bahan->nama }}</td>

    <td>{{ (int) $b->qty_bahan }}</td>

    <td>
        Rp {{ number_format($harga) }}
    </td>

    <td>
        Rp {{ number_format($subtotal) }}
    </td>

    <td>{{ $b->satuan }}</td>
</tr>

@endforeach

<tr>
    <td colspan="3">
        <strong>Total Bahan / Produk</strong>
    </td>

    <td colspan="2">
        <strong>Rp {{ number_format($total_bahan) }}</strong>
    </td>
</tr>

</table>

{{-- HPP FINAL --}}
@php
    $hpp = $total_bahan + $btkl_per_produk + $bop_per_produk;
@endphp

<div class="alert alert-warning">
    <strong>HPP per Produk:</strong>
    Rp {{ number_format($hpp) }}
</div>

<div class="mt-3">
    <a href="{{ route('resep.index') }}"
       class="btn btn-primary">
       Kembali
    </a>
</div>

</div>
</x-app-layout>