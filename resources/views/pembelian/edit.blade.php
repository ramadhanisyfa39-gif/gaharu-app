<x-app-layout>
    <div class="container">
        <h4>Edit Pembelian</h4>

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('pembelian.update', $pembelian->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row mb-3">
                <div class="col-md-4">
                    <label>Supplier</label>
                    <select name="supplier_id" class="form-control" required>
                        <option value="">-- Pilih Supplier --</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}"
                                {{ $pembelian->supplier_id == $supplier->id ? 'selected' : '' }}>
                                {{ $supplier->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label>Gudang Tujuan</label>
                    <select name="gudang_id" class="form-control" required>
                        <option value="">-- Pilih Gudang --</option>
                        @foreach($gudangs as $gudang)
                            <option value="{{ $gudang->id }}"
                                {{ $pembelian->gudang_id == $gudang->id ? 'selected' : '' }}>
                                {{ $gudang->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label>Tanggal</label>
                    <input 
                        type="date" 
                        name="tanggal" 
                        class="form-control" 
                        value="{{ \Carbon\Carbon::parse($pembelian->tanggal)->format('Y-m-d') }}" 
                        required
                    >
                </div>
            </div>

            <hr>

            <h5>Detail Barang</h5>

            <table class="table table-bordered" id="table-items">
                <thead>
                    <tr>
                        <th>Barang</th>
                        <th width="120">Qty</th>
                        <th width="160">Harga</th>
                        <th width="160">Batch Number</th>
                        <th width="80">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($pembelian->details as $index => $detail)
                        <tr>
                            <td>
                                <select name="items[{{ $index }}][barang_id]" class="form-control" required>
                                    <option value="">-- Pilih Barang --</option>
                                    @foreach($barangs as $barang)
                                        <option value="{{ $barang->id }}"
                                            {{ $detail->barang_id == $barang->id ? 'selected' : '' }}>
                                            {{ $barang->kode_barang }} - {{ $barang->nama }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>

                            <td>
                                <input 
                                    type="number" 
                                    step="0.01" 
                                    name="items[{{ $index }}][qty]" 
                                    class="form-control" 
                                    value="{{ $detail->qty }}"
                                    required
                                >
                            </td>

                            <td>
                                <input 
                                    type="number" 
                                    step="0.01" 
                                    name="items[{{ $index }}][harga]" 
                                    class="form-control" 
                                    value="{{ $detail->harga }}"
                                    required
                                >
                            </td>

                            <td>
                                <input 
                                    type="text" 
                                    name="items[{{ $index }}][batch_number]" 
                                    class="form-control"
                                    value="{{ $detail->batch_number }}"
                                >
                            </td>

                            <td>
                                <button type="button" class="btn btn-danger btn-sm btn-remove">
                                    X
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <button type="button" class="btn btn-secondary" id="btn-add">
                Tambah Baris
            </button>

            <button type="submit" class="btn btn-primary">
                Update Pembelian
            </button>

            <a href="{{ route('pembelian.index') }}" class="btn btn-light">
                Kembali
            </a>
        </form>
    </div>

    <script>
        let rowIndex = {{ $pembelian->details->count() }};

        document.getElementById('btn-add').addEventListener('click', function () {
            const tbody = document.querySelector('#table-items tbody');

            const row = `
                <tr>
                    <td>
                        <select name="items[${rowIndex}][barang_id]" class="form-control" required>
                            <option value="">-- Pilih Barang --</option>
                            @foreach($barangs as $barang)
                                <option value="{{ $barang->id }}">
                                    {{ $barang->kode_barang }} - {{ $barang->nama }}
                                </option>
                            @endforeach
                        </select>
                    </td>

                    <td>
                        <input 
                            type="number" 
                            step="0.01" 
                            name="items[${rowIndex}][qty]" 
                            class="form-control" 
                            required
                        >
                    </td>

                    <td>
                        <input 
                            type="number" 
                            step="0.01" 
                            name="items[${rowIndex}][harga]" 
                            class="form-control" 
                            required
                        >
                    </td>

                    <td>
                        <input 
                            type="text" 
                            name="items[${rowIndex}][batch_number]" 
                            class="form-control"
                        >
                    </td>

                    <td>
                        <button type="button" class="btn btn-danger btn-sm btn-remove">
                            X
                        </button>
                    </td>
                </tr>
            `;

            tbody.insertAdjacentHTML('beforeend', row);
            rowIndex++;
        });

        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('btn-remove')) {
                const rows = document.querySelectorAll('#table-items tbody tr');

                if (rows.length > 1) {
                    e.target.closest('tr').remove();
                } else {
                    alert('Minimal harus ada 1 barang.');
                }
            }
        });
    </script>
</x-app-layout>