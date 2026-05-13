<x-app-layout>

<div class="container">

<div class="d-flex justify-content-between mb-3">
    <h3>Penjualan POS</h3>

    <a href="{{ route('penjualan_pos.create') }}"
       class="btn btn-primary">
       + Tambah
    </a>
</div>

@if(session('success'))
<div class="alert alert-success">
    {{ session('success') }}
</div>
@endif

<div class="card">
<div class="card-body">

<table class="table table-bordered">

    <thead>
        <tr>
            <th>Kode</th>
            <th>Tanggal</th>
            <th>Gudang</th>
            <th>Total</th>
            <th width="220">Aksi</th>
        </tr>
    </thead>

    <tbody>

        @foreach($data as $item)

        <tr>
            <td>{{ $item->kode_transaksi }}</td>

            <td>
                {{ \Carbon\Carbon::parse($item->tanggal)->format('d-m-Y H:i') }}
            </td>

            <td>{{ $item->gudang->nama }}</td>

            <td>
                Rp {{ number_format($item->total, 0, ',', '.') }}
            </td>

            <td>

                <a href="{{ route('penjualan_pos.show', $item->id) }}"
                   class="btn btn-info btn-sm">
                   Detail
                </a>

                <a href="{{ route('penjualan_pos.edit', $item->id) }}"
                   class="btn btn-warning btn-sm">
                   Edit
                </a>

                <form action="{{ route('penjualan_pos.destroy', $item->id) }}"
                      method="POST"
                      class="d-inline">

                    @csrf
                    @method('DELETE')

                    <button type="submit"
                            class="btn btn-danger btn-sm"
                            onclick="return confirm('Hapus data?')">

                        Hapus
                    </button>

                </form>

            </td>

        </tr>

        @endforeach

    </tbody>

</table>

</div>
</div>
</div>

</x-app-layout>