@extends('layouts.app')

@section('content')
<div class="container">

    <h2 class="mb-3">Data Barang</h2>

    <a href="{{ route('barang.create') }}" class="btn btn-primary mb-3">
        + Tambah Barang
    </a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-body table-responsive">

            <table class="table table-bordered table-striped align-middle">
                <thead>
                    <tr>
                        <th width="110">Kode</th>
                        <th>Barang</th>
                        <th width="170">Jenis</th>
                        <th width="200" class="text-end">Harga</th>
                        <th width="120">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($data as $d)
                    <tr>
                        <td>{{ $d->kode_barang }}</td>
                        <td>
                            <strong>{{ $d->nama }}</strong><br>
                            <small class="text-muted">
                                {{ $d->kategori->nama ?? '-' }} • {{ $d->satuan ?? '-' }}
                            </small>
                        </td>

                        <td>
                            @if($d->jenis_utama == 'BAHAN_BAKU')
                                <span class="badge bg-primary">Bahan Baku</span>
                            @elseif($d->jenis_utama == 'BARANG_JADI')
                                <span class="badge bg-success">Barang Jadi</span>
                            @else
                                <span class="badge bg-warning text-dark">Operational</span>
                            @endif
                        </td>

                        <td class="text-end">
                            @if($d->harga_jual_b2b)
                                <small>B2B:</small>
                                Rp {{ number_format($d->harga_jual_b2b,0,',','.') }}<br>
                            @endif
                            @if($d->harga_jual_pos)
                                <small>POS:</small>
                                Rp {{ number_format($d->harga_jual_pos,0,',','.') }}<br>
                            @endif
                            @if($d->hpp_referensi)
                                <small>HPP:</small>
                                Rp {{ number_format($d->hpp_referensi,0,',','.') }}
                            @endif
                        </td>

                        <td>
                            <a href="{{ route('barang.edit', $d->id) }}" class="btn btn-warning btn-sm">Edit</a>
                            <form action="{{ route('barang.destroy', $d->id) }}" method="POST" style="display:inline">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger btn-sm" onclick="return confirm('Yakin hapus?')">
                                    Hapus
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center">Data belum ada</td>
                    </tr>
                @endforelse
                </tbody>
            </table>

        </div>
    </div>

</div>
@endsection