<x-app-layout>
<div class="container">

<h3>Resep: {{ $resep->produk->nama }}</h3>

{{-- INFO RESEP --}}
<div class="alert alert-info">
    Output: {{ $resep->output_qty }} {{ $resep->satuan_output }} <br>
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

{{-- FORM TAMBAH BAHAN --}}
<form action="{{ route('resep.bahan.store',$resep->id) }}" method="POST">
@csrf

<div class="row mb-3">
<div class="col">
<select name="bahan_id" class="form-control">
<option disabled selected>-- Pilih Bahan --</option>
@foreach($master as $m)
<option value="{{ $m->id }}">{{ $m->nama }}</option>
@endforeach
</select>
</div>

<div class="col">
<input type="number" name="qty_bahan" class="form-control" placeholder="Qty per produk">
</div>

<div class="col">
<input type="text" name="satuan" class="form-control" placeholder="Satuan">
</div>

<div class="col">
<button class="btn btn-primary">Tambah</button>
</div>
</div>

</form>

{{-- TABEL BAHAN + BIAYA --}}
<table class="table table-bordered">
<tr>
<th>Bahan</th>
<th>Qty / Produk</th>
<th>Harga</th>
<th>Subtotal</th>
<th>Satuan</th>
<th>Aksi</th>
</tr>

@foreach($bahan as $b)
@php
    $harga = $b->bahan->harga ?? 0;
    $subtotal = $b->qty_bahan * $harga;
    $total_bahan += $subtotal;
@endphp

<tr>
<td>{{ $b->bahan->nama }}</td>
<td>{{ $b->qty_bahan }}</td>
<td>Rp {{ number_format($harga) }}</td>
<td>Rp {{ number_format($subtotal) }}</td>
<td>{{ $b->satuan }}</td>
<td>
<form action="{{ route('resep.bahan.destroy',$b->id) }}" method="POST">
@csrf @method('DELETE')
<button class="btn btn-danger btn-sm">Hapus</button>
</form>
</td>
</tr>
@endforeach

<tr>
<td colspan="3"><strong>Total Bahan / Produk</strong></td>
<td colspan="3"><strong>Rp {{ number_format($total_bahan) }}</strong></td>
</tr>

</table>

{{-- HPP FINAL --}}
@php
    $hpp = $total_bahan + $btkl_per_produk + $bop_per_produk;
@endphp

<div class="alert alert-warning">
    <strong>HPP per Produk:</strong> Rp {{ number_format($hpp) }}
</div>

</div>
</x-app-layout>