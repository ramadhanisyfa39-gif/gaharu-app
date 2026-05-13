<x-app-layout>

    <div class="container">

        <h4>Data Pembelian</h4>

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <a href="{{ route('pembelian.create') }}"
           class="btn btn-primary mb-3">

            Tambah Pembelian

        </a>

        <table class="table table-bordered align-middle">

            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Tanggal</th>
                    <th>Supplier</th>
                    <th>Gudang</th>
                    <th>Total</th>
                    <th width="320">Aksi</th>
                </tr>
            </thead>

            <tbody>

                @forelse($pembelian as $item)

                    <tr>

                        <td>
                            {{ $item->kode_pembelian }}
                        </td>

                        <td>
                            {{ $item->tanggal }}
                        </td>

                        <td>
                            {{ $item->supplier->nama ?? '-' }}
                        </td>

                        <td>
                            {{ $item->gudang->nama ?? '-' }}
                        </td>

                        <td>
                            Rp {{ number_format($item->total, 0, ',', '.') }}
                        </td>

                        <td>

                            <div class="d-flex gap-1 flex-wrap">

                                {{-- DETAIL --}}
                                <a href="{{ route('pembelian.show', $item->id) }}"
                                   class="btn btn-info btn-sm">

                                    Detail

                                </a>

                                {{-- EDIT --}}
                                @if($item->isEditable())

                                    <a href="{{ route('pembelian.edit', $item->id) }}"
                                       class="btn btn-warning btn-sm">

                                        Edit

                                    </a>

                                @else

                                    <button
                                        class="btn btn-secondary btn-sm"
                                        disabled>

                                        Edit Terkunci

                                    </button>

                                @endif

                                {{-- DELETE --}}
                                @if($item->isEditable())

                                    <form
                                        action="{{ route('pembelian.destroy', $item->id) }}"
                                        method="POST"
                                        class="d-inline"
                                        onsubmit="return confirm('Yakin ingin menghapus pembelian ini? Stok akan ikut dikurangi.')">

                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="btn btn-danger btn-sm">

                                            Hapus

                                        </button>

                                    </form>

                                @else

                                    <button
                                        class="btn btn-secondary btn-sm"
                                        disabled>

                                        Delete Terkunci

                                    </button>

                                @endif

                            </div>

                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="6" class="text-center">

                            Belum ada data pembelian.

                        </td>
                    </tr>

                @endforelse

            </tbody>

        </table>

        <div class="mt-3">
            {{ $pembelian->links() }}
        </div>

    </div>

</x-app-layout>