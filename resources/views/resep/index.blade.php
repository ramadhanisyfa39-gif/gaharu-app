<x-app-layout>
<div class="container">

<h3 class="mb-3">Data Resep</h3>

<a href="{{ route('resep.create') }}" class="btn btn-primary mb-3">+ Tambah</a>

<table class="table table-bordered">
<thead>
<tr>
    <th>Produk</th>
    <th>Output</th>
    <th>BTKL / Batch</th>
    <th>BOP / Batch</th>
    <th>Aksi</th>
</tr>
</thead>

<tbody>

@if($data->isEmpty())
<tr>
    <td colspan="6" class="text-center">Belum ada data resep</td>
</tr>
@endif

@foreach($data as $d)
<tr>
    <td>{{ $d->produk->nama ?? '-' }}</td>

    <td>
        {{ $d->output_qty }} {{ $d->satuan_output ?? '-' }}
    </td>

    <td>Rp {{ number_format($d->btkl_per_batch) }}</td>
    <td>Rp {{ number_format($d->bop_per_batch) }}</td>

    {{-- JUMLAH BAHAN --}}
    <td>{{ $d->bahanbaku->count() }} bahan</td>

    <td>
        <a href="{{ route('resep.bahan.show', $d->id) }}"
            class="btn btn-info btn-sm">
            Detail
        </a>

        <a href="{{ route('resep.edit',$d->id) }}"
           class="btn btn-warning btn-sm">
           Edit
        </a>

        <form action="{{ route('resep.destroy',$d->id) }}"
              method="POST"
              style="display:inline;">
            @csrf @method('DELETE')
            <button class="btn btn-danger btn-sm"
                onclick="return confirm('Yakin hapus?')">
                Hapus
            </button>
        </form>
    </td>
</tr>
@endforeach

</tbody>
</table>

</div>
</x-app-layout>