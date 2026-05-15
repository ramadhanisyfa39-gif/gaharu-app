
<x-app-layout>
<div class="container">

    <h3>Data Produksi</h3>

    @if(session('success'))

        <div class="alert alert-success">
            {{ session('success') }}
        </div>

    @endif

    <a href="{{ route('produksi.create') }}"
       class="btn btn-primary mb-3">

       Input Produksi

    </a>

    <table class="table table-bordered">

        <thead>
            <tr>
                <th>Kode Produksi</th>
                <th>Tanggal</th>
                <th>Status</th>
            </tr>
        </thead>

        <tbody>

            @foreach($produksi as $item)

                <tr>

                    <td>
                        {{ $item->kode_produksi }}
                    </td>

                    <td>
                        {{ $item->tanggal_mulai }}
                    </td>

                    <td>
                        {{ $item->status_produksi }}
                    </td>

                </tr>

            @endforeach

        </tbody>

    </table>

</div>

</x-app-layout>