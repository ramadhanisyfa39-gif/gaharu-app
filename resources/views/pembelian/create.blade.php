<x-app-layout>
    <div class="container">
        <h4>Tambah Pembelian</h4>

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('pembelian.store') }}" method="POST">
            @csrf

            <div class="row mb-3">

                {{-- SUPPLIER --}}
                <div class="col-md-4">
                    <label>Supplier</label>

                    <select
                        name="supplier_id"
                        id="supplier_id"
                        class="form-control"
                        required>

                        <option value="">
                            -- Pilih Supplier --
                        </option>

                        @foreach($suppliers as $supplier)
                            <option
                                value="{{ $supplier->id }}"
                                data-nama="{{ strtoupper($supplier->nama) }}">

                                {{ $supplier->nama }}

                            </option>
                        @endforeach

                    </select>
                </div>

                {{-- GUDANG --}}
                <div class="col-md-4">

                    <label>Gudang Tujuan</label>

                    @php
                        $gudangUtama = $gudangs->firstWhere('nama', 'Gudang Utama');
                    @endphp

                    <input
                        type="text"
                        class="form-control"
                        value="Gudang Utama"
                        readonly>

                    <input
                        type="hidden"
                        name="gudang_id"
                        value="{{ $gudangUtama?->id }}">

                </div>

                {{-- TANGGAL --}}
                <div class="col-md-4">
                    <label>Tanggal</label>

                    <input
                        type="date"
                        name="tanggal"
                        id="tanggal"
                        class="form-control"
                        value="{{ date('Y-m-d') }}"
                        required>
                </div>
            </div>

            <hr>

            <h5>Detail Barang</h5>

            <table class="table table-bordered" id="table-items">

                <thead>
                    <tr>
                        <th>Nama Barang</th>
                        <th width="120">Qty</th>
                        <th width="180">Total Harga</th>
                        <th width="180">Harga / Qty</th>
                        <th width="220">Batch Number</th>
                        <th width="80">Aksi</th>
                    </tr>
                </thead>

                <tbody>

                    <tr class="item-row">

                        {{-- BARANG --}}
                        <td>
                            <select
                                name="items[0][barang_id]"
                                class="form-control barang-select"
                                required>

                                <option value="">
                                    -- Pilih Barang --
                                </option>

                                @foreach($barangs as $barang)
                                    <option
                                        value="{{ $barang->id }}"
                                        data-kode="{{ $barang->kode_barang }}">

                                        {{ $barang->kode_barang }}
                                        -
                                        {{ $barang->nama }}

                                    </option>
                                @endforeach

                            </select>
                        </td>

                        {{-- QTY --}}
                        <td>
                            <input
                                type="number"
                                step="0.01"
                                name="items[0][qty]"
                                class="form-control qty-input"
                                required>
                        </td>

                        {{-- TOTAL HARGA --}}
                        <td>
                            <input
                                type="number"
                                step="0.01"
                                name="items[0][harga]"
                                class="form-control harga-input"
                                required>
                        </td>

                        {{-- HARGA PER QTY --}}
                        <td>
                            <input
                                type="number"
                                step="0.01"
                                name="items[0][harga_per_qty]"
                                class="form-control harga-per-qty"
                                readonly>
                        </td>

                        {{-- BATCH --}}
                        <td>
                            <input
                                type="text"
                                name="items[0][batch_number]"
                                class="form-control batch-number"
                                readonly>
                        </td>

                        {{-- AKSI --}}
                        <td>
                            <button
                                type="button"
                                class="btn btn-danger btn-sm btn-remove">

                                X

                            </button>
                        </td>

                    </tr>

                </tbody>
            </table>

            <button
                type="button"
                class="btn btn-secondary"
                id="btn-add">

                Tambah Baris

            </button>

            <button
                type="submit"
                class="btn btn-primary">

                Simpan Pembelian

            </button>

            <a
                href="{{ route('pembelian.index') }}"
                class="btn btn-light">

                Kembali

            </a>
        </form>
    </div>

    <script>

        let rowIndex = 1;

        /*
        |--------------------------------------------------------------------------
        | TAMBAH ROW
        |--------------------------------------------------------------------------
        */

        document.getElementById('btn-add')
            .addEventListener('click', function () {

            const tbody =
                document.querySelector('#table-items tbody');

            const row = `
                <tr class="item-row">

                    <td>
                        <select
                            name="items[${rowIndex}][barang_id]"
                            class="form-control barang-select"
                            required>

                            <option value="">
                                -- Pilih Barang --
                            </option>

                            @foreach($barangs as $barang)

                                <option
                                    value="{{ $barang->id }}"
                                    data-kode="{{ $barang->kode_barang }}">

                                    {{ $barang->kode_barang }}
                                    -
                                    {{ $barang->nama }}

                                </option>

                            @endforeach

                        </select>
                    </td>

                    <td>
                        <input
                            type="number"
                            step="0.01"
                            name="items[${rowIndex}][qty]"
                            class="form-control qty-input"
                            required>
                    </td>

                    <td>
                        <input
                            type="number"
                            step="0.01"
                            name="items[${rowIndex}][harga]"
                            class="form-control harga-input"
                            required>
                    </td>

                    <td>
                        <input
                            type="number"
                            step="0.01"
                            name="items[${rowIndex}][harga_per_qty]"
                            class="form-control harga-per-qty"
                            readonly>
                    </td>

                    <td>
                        <input
                            type="text"
                            name="items[${rowIndex}][batch_number]"
                            class="form-control batch-number"
                            readonly>
                    </td>

                    <td>
                        <button
                            type="button"
                            class="btn btn-danger btn-sm btn-remove">

                            X

                        </button>
                    </td>

                </tr>
            `;

            tbody.insertAdjacentHTML('beforeend', row);

            rowIndex++;
        });

        /*
        |--------------------------------------------------------------------------
        | REMOVE ROW
        |--------------------------------------------------------------------------
        */

        document.addEventListener('click', function (e) {

            if (e.target.classList.contains('btn-remove')) {

                const rows =
                    document.querySelectorAll('#table-items tbody tr');

                if (rows.length > 1) {

                    e.target.closest('tr').remove();
                }
            }
        });

        /*
        |--------------------------------------------------------------------------
        | GENERATE BATCH NUMBER
        |--------------------------------------------------------------------------
        */

        function generateBatchNumber(row)
        {
            const tanggal =
                document.getElementById('tanggal').value;

            const supplierSelect =
                document.getElementById('supplier_id');

            const supplierOption =
                supplierSelect.options[supplierSelect.selectedIndex];

            const supplier =
                supplierOption.dataset.nama ?? '';

            const barangSelect =
                row.querySelector('.barang-select');

            const barangOption =
                barangSelect.options[barangSelect.selectedIndex];

            const kodeBarang =
                barangOption.dataset.kode ?? '';

            if (
                !tanggal ||
                !supplier ||
                !kodeBarang
            ) {
                return;
            }

            const tanggalFormat =
                tanggal.replaceAll('-', '');

            const batch =
                `${tanggalFormat}-${supplier}-${kodeBarang}`;

            row.querySelector('.batch-number').value =
                batch;
        }

        /*
        |--------------------------------------------------------------------------
        | HITUNG HARGA PER QTY
        |--------------------------------------------------------------------------
        */

        function calculateHargaPerQty(row)
        {
            const qtyInput =
                row.querySelector('.qty-input');

            const hargaInput =
                row.querySelector('.harga-input');

            const hargaPerQtyInput =
                row.querySelector('.harga-per-qty');

            const qty =
                parseFloat(qtyInput.value) || 0;

            const harga =
                parseFloat(hargaInput.value) || 0;

            let hasil = 0;

            if (qty > 0) {
                hasil = harga / qty;
            }

            hargaPerQtyInput.value =
                hasil.toFixed(2);
        }

        /*
        |--------------------------------------------------------------------------
        | AUTO GENERATE BATCH
        |--------------------------------------------------------------------------
        */

        document.addEventListener('change', function(e) {

            if (
                e.target.classList.contains('barang-select') ||
                e.target.id === 'supplier_id' ||
                e.target.id === 'tanggal'
            ) {

                document.querySelectorAll('.item-row')
                    .forEach(row => {

                        generateBatchNumber(row);
                    });
            }
        });

        /*
        |--------------------------------------------------------------------------
        | AUTO HITUNG HARGA PER QTY
        |--------------------------------------------------------------------------
        */

        document.addEventListener('input', function(e) {

            if (
                e.target.classList.contains('qty-input') ||
                e.target.classList.contains('harga-input')
            ) {

                const row =
                    e.target.closest('.item-row');

                calculateHargaPerQty(row);
            }
        });

    </script>
</x-app-layout>