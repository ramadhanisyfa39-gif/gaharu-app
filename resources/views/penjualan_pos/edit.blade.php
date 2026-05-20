<x-app-layout>
    <div class="container py-4">
        <h3 class="mb-4 fw-bold">Edit Penjualan POS</h3>

        <div class="card shadow-sm">
            <div class="card-body">
                <form action="{{ route('penjualan_pos.update', $penjualan->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Tanggal</label>
                            <input type="datetime-local" 
                                   name="tanggal" 
                                   class="form-control" 
                                   value="{{ \Carbon\Carbon::parse($penjualan->tanggal)->format('Y-m-d\TH:i') }}" 
                                   required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Gudang</label>
                            <select name="gudang_id" class="form-select" required>
                                <option value="">-- Pilih Gudang --</option>
                                @foreach($gudang as $g)
                                    <option value="{{ $g->id }}" {{ $penjualan->gudang_id == $g->id ? 'selected' : '' }}>
                                        {{ $g->nama }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <hr>

                    <div class="table-responsive mb-3">
                        <table class="table table-bordered align-middle" id="table-item">
                            <thead class="table-light">
                                <tr>
                                    <th>Produk</th>
                                    <th width="150">Qty</th>
                                    <th width="200">Harga</th>
                                    <th width="200">Subtotal</th>
                                    <th width="50" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($penjualan->details as $detail)
                                <tr>
                                    <td>
                                        <select name="produk_id[]" class="form-select produk" required>
                                            <option value="">-- Pilih Produk --</option>
                                            @foreach($produk as $p)
                                                <option value="{{ $p->id }}" 
                                                        data-harga="{{ $p->harga_jual_pos }}" 
                                                        {{ $detail->produk_id == $p->id ? 'selected' : '' }}>
                                                    {{ $p->nama }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" 
                                               step="0.01" 
                                               min="0.01"
                                               name="qty[]" 
                                               class="form-control qty" 
                                               value="{{ $detail->qty }}" 
                                               required>
                                    </td>
                                    <td>
                                        <input type="number" 
                                               step="0.01" 
                                               name="harga[]" 
                                               class="form-control harga" 
                                               value="{{ $detail->harga }}" 
                                               readonly>
                                    </td>
                                    <td>
                                        <input type="number" 
                                               class="form-control subtotal" 
                                               value="{{ $detail->subtotal }}" 
                                               readonly>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-danger btn-sm btn-remove">
                                            <i class="fa-solid fa-xmark"></i> X
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <button type="button" class="btn btn-success mb-4" id="btn-add">
                        + Tambah Item
                    </button>

                    <div class="d-flex justify-content-end align-items-center mb-4">
                        <h4 class="mb-0 fw-bold">
                            Total: Rp <span id="grand-total">{{ number_format($penjualan->total, 0, ',', '.') }}</span>
                        </h4>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('penjualan_pos.index') }}" class="btn btn-secondary">
                            Kembali
                        </a>
                        <button type="submit" class="btn btn-primary px-4">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Menambah baris baru
        document.getElementById('btn-add').addEventListener('click', function() {
            let tableBody = document.querySelector('#table-item tbody');
            let firstRow = tableBody.querySelector('tr');
            
            if (!firstRow) return; // Cegah error jika tabel kosong

            let row = firstRow.cloneNode(true);

            // Reset nilai input
            row.querySelectorAll('input').forEach(input => {
                input.value = '';
            });
            row.querySelector('.subtotal').value = 0;
            
            // Reset dropdown select
            row.querySelectorAll('select').forEach(select => {
                select.selectedIndex = 0;
            });

            tableBody.appendChild(row);
        });

        // Deteksi perubahan pada dropdown Produk
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('produk')) {
                let row = e.target.closest('tr');
                let selectedOption = e.target.options[e.target.selectedIndex];
                let harga = selectedOption.getAttribute('data-harga') || 0;

                row.querySelector('.harga').value = harga;
                hitungSubtotal(row);
            }
        });

        // Deteksi input pada kolom Qty
        document.addEventListener('input', function(e) {
            if (e.target.classList.contains('qty')) {
                let row = e.target.closest('tr');
                hitungSubtotal(row);
            }
        });

        // Hapus baris
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-remove') || e.target.closest('.btn-remove')) {
                let rows = document.querySelectorAll('#table-item tbody tr');
                
                // Jangan izinkan menghapus baris terakhir
                if (rows.length > 1) {
                    e.target.closest('tr').remove();
                    hitungTotal();
                } else {
                    alert('Minimal harus ada satu item produk!');
                }
            }
        });

        // Fungsi Hitung Subtotal per baris
        function hitungSubtotal(row) {
            let qty = parseFloat(row.querySelector('.qty').value) || 0;
            let harga = parseFloat(row.querySelector('.harga').value) || 0;
            let subtotal = qty * harga;

            row.querySelector('.subtotal').value = subtotal;
            hitungTotal();
        }

        // Fungsi Hitung Grand Total
        function hitungTotal() {
            let total = 0;
            document.querySelectorAll('.subtotal').forEach(function(item) {
                total += parseFloat(item.value) || 0;
            });

            document.getElementById('grand-total').innerText = total.toLocaleString('id-ID');
        }
    </script>
</x-app-layout>