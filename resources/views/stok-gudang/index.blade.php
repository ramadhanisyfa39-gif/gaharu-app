<x-app-layout>
    <div class="container">
        <h4>Stok Gudang</h4>

        <form method="GET" action="{{ route('stok-gudang.index') }}" class="row mb-3">
            <div class="col-md-4">
                <label>Filter Gudang</label>
                <select name="gudang_id" class="form-control">
                    <option value="">-- Semua Gudang --</option>
                    @foreach($gudangs as $gudang)
                        <option value="{{ $gudang->id }}"
                            {{ request('gudang_id') == $gudang->id ? 'selected' : '' }}>
                            {{ $gudang->nama }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label>Filter Barang</label>
                <select name="barang_id" class="form-control">
                    <option value="">-- Semua Barang --</option>
                    @foreach($barangs as $barang)
                        <option value="{{ $barang->id }}"
                            {{ request('barang_id') == $barang->id ? 'selected' : '' }}>
                            {{ $barang->kode_barang }} - {{ $barang->nama }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    Filter
                </button>

                <a href="{{ route('stok-gudang.index') }}" class="btn btn-secondary">
                    Reset
                </a>
            </div>
        </form>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Gudang</th>
                    <th>Kode Barang</th>
                    <th>Nama Barang</th>
                    <th>Jumlah Stok</th>
                </tr>
            </thead>

            <tbody>
                @forelse($stokGudang as $stok)
                    <tr>
                        <td>{{ $stok->gudang->nama ?? '-' }}</td>
                        <td>{{ $stok->barang->kode_barang ?? '-' }}</td>
                        <td>{{ $stok->barang->nama ?? '-' }}</td>
                        <td>{{ number_format($stok->jumlah, 2, ',', '.') }} {{ $stok->barang->satuan ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center">
                            Belum ada stok.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{ $stokGudang->links() }}
    </div>
</x-app-layout>