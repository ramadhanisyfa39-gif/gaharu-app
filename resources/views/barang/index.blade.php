<x-app-layout>
<div class="container">

<h3 class="mb-3">Data Barang</h3>

<a href="{{ route('barang.create') }}" class="btn btn-primary mb-3">
    + Tambah Barang
</a>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card shadow-sm">
<div class="card-body">

<table class="table table-bordered table-hover align-middle text-center">

<thead class="table-light">
<tr>
    <th>Kode</th>
    <th>Nama</th>
    <th>Kategori</th>
    <th>Satuan</th>
    <th>Jenis</th>
    <th>Harga B2B</th>
    <th>Harga POS</th>
    <th>HPP</th>
    <th>Aksi</th>
</tr>
</thead>

<tbody>
@forelse($data as $d)
<tr>
    <td>{{ $d->kode_barang }}</td>

    <td class="text-start fw-semibold">
        {{ $d->nama }}
    </td>

    <td>{{ $d->kategori->nama ?? '-' }}</td>

    <td>{{ $d->satuan }}</td>

    <td>
        @if($d->is_bahan_baku)
            <span class="badge bg-primary-subtle text-primary px-3 py-2">Bahan Baku</span>
        @elseif($d->is_barang_jadi)
            <span class="badge bg-success-subtle text-success px-3 py-2">Barang Jadi</span>
        @elseif($d->is_operational)
            <span class="badge bg-warning-subtle text-dark px-3 py-2">Operational</span>
        @endif
    </td>

    <!-- B2B -->
    <td class="fw-semibold">
        {{ $d->is_barang_jadi ? 'Rp ' . number_format($d->harga_jual_b2b,0,',','.') : '-' }}
    </td>

    <!-- POS -->
    <td class="fw-semibold">
        {{ $d->is_barang_jadi ? 'Rp ' . number_format($d->harga_jual_pos,0,',','.') : '-' }}
    </td>

    <!-- HPP (SEMUA JENIS PUNYA) -->
    <td class="fw-semibold">
        Rp {{ number_format($d->hpp_referensi,0,',','.') }}
    </td>

    <td>
        <div class="d-flex justify-content-center gap-1">

            <a href="{{ route('barang.edit',$d->id) }}" class="btn btn-warning btn-sm">
                Edit
            </a>

            <form action="{{ route('barang.destroy',$d->id) }}" method="POST">
                @csrf
                @method('DELETE')
                <button onclick="return confirm('Yakin hapus data ini?')" class="btn btn-danger btn-sm">
                    Hapus
                </button>
            </form>

        </div>
    </td>
</tr>

@empty
<tr>
    <td colspan="9" class="text-center text-muted">
        Data belum tersedia
    </td>
</tr>
@endforelse

</tbody>

</table>

</div>
</div>

</div>
</x-app-layout>