<x-app-layout>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1">Tambah Pesanan</h2>
                <small class="text-muted">Form input pesanan customer</small>
            </div>
            <a href="{{ route('pesanan.index') }}" class="btn btn-secondary rounded-3">Kembali</a>
        </div>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <form action="{{ route('pesanan.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Kode Pesanan</label>
                        <input type="text" name="kode_pesanan" class="form-control bg-light" value="P{{ rand(100,999) }}" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Customer</label>
                        <select name="customer_id" class="form-select" required>
                            <option value="">-- Pilih Customer --</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tanggal</label>
                            <input type="datetime-local" name="tanggal" class="form-control" required>
                        </div>

                        <div class="col-md-6 mb-4">
                            <label class="form-label">Estimasi Kirim</label>
                            <input type="datetime-local" name="estimasi_kirim" class="form-control" required>
                        </div>
                    </div>

                    <hr class="mb-4">

                    <h5 class="fw-semibold mb-3">Detail Pesanan</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle" id="table-detail">
                            <thead class="table-light">
                                <tr>
                                    <th>Produk</th>
                                    <th width="150">Qty</th>
                                    <th width="200">Harga</th>
                                    <th width="200">Subtotal</th>
                                    <th width="50"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <select name="produk_id[]" class="form-select produk" required>
                                            <option value="">-- Pilih Produk --</option>
                                            @foreach($produk as $p)
                                                <option value="{{ $p->id }}">
                                                    {{ $p->nama }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>

                                    <td>
                                        <input type="number" name="qty[]" class="form-control qty" min="1" required>
                                    </td>

                                    <td>
                                        <input type="number" name="harga[]" class="form-control harga" placeholder="Input Harga..." required>
                                    </td>

                                    <td>
                                        <input type="number" name="subtotal[]" class="form-control subtotal" readonly>
                                    </td>

                                    <td>
                                        <button type="button" class="btn btn-danger btn-sm hapus-baris">×</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <button type="button" class="btn btn-outline-secondary rounded-3 mb-4" id="tambah-baris">
                        + Tambah Baris
                    </button>

                    <div class="row justify-content-end mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Total Pesanan</label>
                            <input type="number" name="total_pesanan" id="total_pesanan" class="form-control fw-bold text-primary" readonly>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary rounded-3 px-5 py-2">
                        Simpan Pesanan
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        /**
         * Menghitung subtotal per baris
         */
        function hitungSubtotal(row) {
            let qty = parseFloat(row.querySelector('.qty').value) || 0;
            let harga = parseFloat(row.querySelector('.harga').value) || 0;
            let subtotal = qty * harga;

            row.querySelector('.subtotal').value = subtotal;
            hitungTotal();
        }

        /**
         * Menghitung total keseluruhan pesanan
         */
        function hitungTotal() {
            let total = 0;
            document.querySelectorAll('.subtotal').forEach(function(item) {
                total += parseFloat(item.value) || 0;
            });
            document.getElementById('total_pesanan').value = total;
        }

        /**
         * Mereset input harga saat produk dipilih
         */
        function resetHarga(row) {
            // Kosongkan harga agar user input manual
            row.querySelector('.harga').value = '';
            row.querySelector('.subtotal').value = '';
            hitungTotal();
        }

        // Event listener untuk perubahan PRODUK (Reset harga)
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('produk')) {
                resetHarga(e.target.closest('tr'));
            }
        });

        // Event listener untuk input QTY atau HARGA (Hitung Subtotal)
        document.addEventListener('input', function(e) {
            if (e.target.classList.contains('qty') || e.target.classList.contains('harga')) {
                hitungSubtotal(e.target.closest('tr'));
            }
        });

        // TAMBAH BARIS BARU
        document.getElementById('tambah-baris').addEventListener('click', function () {
            let tableBody = document.querySelector('#table-detail tbody');
            let firstRow = tableBody.rows[0];
            let newRow = firstRow.cloneNode(true);

            // Bersihkan nilai pada baris baru
            newRow.querySelectorAll('input').forEach(input => {
                input.value = '';
            });
            newRow.querySelector('.produk').selectedIndex = 0;

            tableBody.appendChild(newRow);
        });

        // HAPUS BARIS
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('hapus-baris')) {
                let tableBody = document.querySelector('#table-detail tbody');
                if (tableBody.rows.length > 1) {
                    e.target.closest('tr').remove();
                    hitungTotal();
                } else {
                    alert("Minimal harus ada satu produk dalam pesanan.");
                }
            }
        });
    </script>
</x-app-layout>