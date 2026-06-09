<x-app-layout>

    <div class="container">

        <h4 class="mb-4">
            Stok Gudang Batch
        </h4>

        <div class="card">

            <div class="card-body">

                <div class="table-responsive">

                    <table class="table table-bordered">

                        <thead>

                            <tr>

                                <th>Supplier</th>

                                <th>Nama Barang</th>

                                <th>Qty Masuk</th>

                                <th>Qty Keluar</th>

                                <th>Qty Sisa</th>

                                <th>Harga / Qty</th>

                                <th>Batch ID</th>

                                <th>Status</th>

                            </tr>

                        </thead>

                        <tbody>

                            @forelse($data as $item)

                                <tr>

                                    <td>
                                        {{ $item->supplier->nama ?? '-' }}
                                    </td>

                                    <td>
                                        {{ $item->barang->nama ?? '-' }}
                                    </td>

                                    <td>
                                        {{ $item->qty_masuk }}
                                    </td>

                                    <td>
                                        {{ $item->qty_keluar }}
                                    </td>

                                    <td>
                                        {{ $item->qty_sisa }}
                                    </td>

                                    <td>
                                        Rp
                                        {{ number_format($item->harga_per_qty, 0, ',', '.') }}
                                    </td>

                                    <td>
                                        {{ $item->batch_number }}
                                    </td>

                                    <td>

                                        @if($item->is_habis)

                                            <span class="badge bg-danger">
                                                Habis
                                            </span>

                                        @else

                                            <span class="badge bg-success">
                                                Aktif
                                            </span>

                                        @endif

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="8" class="text-center">

                                        Tidak ada data

                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

                <div class="mt-3">

                    {{ $data->links() }}

                </div>

            </div>

        </div>

    </div>

</x-app-layout>