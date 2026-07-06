<x-app-layout>

<div class="container mt-4">

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {!! session('error') !!}
        </div>
    @endif

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
        </div>
    @endif

    <h3 class="mb-3">Tambah Penjualan POS</h3>

    <div class="card">
        <div class="card-body">

            <form action="{{ route('penjualan_pos.store') }}" method="POST">
                @csrf

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label font-weight-bold">Tanggal Transaksi</label>
                        <input type="datetime-local"
                               name="tanggal"
                               id="input-tanggal"
                               class="form-control"
                               value="{{ now()->format('Y-m-d\TH:i') }}"
                               required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label font-weight-bold">Gudang Operasional</label>
                        <select name="gudang_id" class="form-control" required>
                            <option value="">-- Pilih Gudang Operasional Cafe --</option>
                            @foreach($gudang as $g)
                                <option value="{{ $g->id }}" {{ old('gudang_id') == $g->id ? 'selected' : '' }}>
                                    {{ $g->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <hr>

                <h5 class="mb-3">Rincian Produk Terjual (Rekap Akhir Hari)</h5>
                <table class="table table-bordered align-middle" id="table-item">
                    <thead class="table-light">
                        <tr>
                            <th>Produk</th>
                            <th width="150">Qty</th>
                            <th width="200">Harga Jual</th>
                            <th width="200">Subtotal</th>
                            <th width="50">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <select name="produk_id[]" class="form-control select-produk" required>
                                    <option value="">-- Pilih Produk --</option>
                                    @foreach($produk as $p)
                                        <option value="{{ $p->id }}">
                                            {{ $p->nama }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>

                            <td>
                                <input type="number"
                                       step="0.01"
                                       name="qty[]"
                                       class="form-control qty"
                                       placeholder="0.00"
                                       required>
                            </td>

                            <td>
                                <input type="number"
                                       step="0.01"
                                       name="harga[]"
                                       class="form-control harga"
                                       readonly>
                            </td>

                            <td>
                                <input type="number"
                                       class="form-control subtotal"
                                       readonly>
                            </td>

                            <td>
                                <button type="button" class="btn btn-danger btn-sm btn-remove">
                                    X
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div class="d-flex justify-content-between align-items-center mt-3">
                    <button type="button" class="btn btn-secondary" id="btn-add">
                        + Tambah Item Menu
                    </button>
                    
                    <h4 class="mb-0 text-primary">Total Omzet: Rp <span id="grand-total">0</span></h4>
                </div>

                <hr class="mt-4">

                <div class="mt-3">
                    <button type="submit" class="btn btn-primary px-4">
                        Simpan Data Rekap
                    </button>

                    <a href="{{ route('penjualan_pos.index') }}" class="btn btn-light border px-4 ms-2">
                        Kembali
                    </a>
                </div>

            </form>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    
    // 1. TAMBAH BARIS ITEM BARU
    document.getElementById('btn-add').addEventListener('click', function(){
        // Kloning baris pertama pada tabel
        let bodyTable = document.querySelector('#table-item tbody');
        let firstRow = bodyTable.querySelector('tr');
        let row = firstRow.cloneNode(true);
        
        // Bersihkan isi inputan pada baris baru
        row.querySelectorAll('input').forEach(input => {
            input.value = '';
        });
        
        // Kembalikan dropdown produk ke default
        row.querySelectorAll('select').forEach(select => {
            select.selectedIndex = 0;
        });
        
        bodyTable.appendChild(row);
    });

    // 2. FUNGSI HITUNG GRAND TOTAL OMZET
    function hitungTotal() {
        let total = 0;
        document.querySelectorAll('.subtotal').forEach(function(item){
            total += parseFloat(item.value || 0);
        });
        document.getElementById('grand-total').innerText = total.toLocaleString('id-ID');
    }

    // 3. FUNGSI HITUNG SUBTOTAL PER BARIS MENU
    function hitungSubtotal(row) {
        let qty = parseFloat(row.querySelector('.qty').value || 0);
        let harga = parseFloat(row.querySelector('.harga').value || 0);
        let subtotal = qty * harga;
        
        row.querySelector('.subtotal').value = subtotal;
        hitungTotal();
    }

    // 4. FUNGSI PANGGIL API HARGA AKTIF PERIODE
    function fetchHarga(selectElement) {
        const row = selectElement.closest('tr');
        const inputHarga = row.querySelector('.harga'); 
        
        const produkId = selectElement.value;
        const tanggalInput = document.getElementById('input-tanggal').value; 

        if (produkId) {
            inputHarga.value = '...'; 

            const urlHarga = '/penjualan_pos/get-harga/' + produkId + '?tanggal=' + tanggalInput;

            fetch(urlHarga)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Route salah atau Server Error (Status: ' + response.status + ')');
                    }
                    return response.json();
                })
                .then(data => {
                    // PERBAIKAN: Menggunakan data.harga_pos sesuai kolom database
                    inputHarga.value = (data && data.harga_pos) ? data.harga_pos : 0;
                    hitungSubtotal(row);
                })
                .catch(error => {
                    console.error('Error fetching price:', error);
                    alert('Gagal mengambil harga aktif: ' + error.message);
                    inputHarga.value = 0;
                    hitungSubtotal(row);
                });
        } else {
            inputHarga.value = 0;
            hitungSubtotal(row);
        }
    }

    // 5. EVENT DELEGATION (Menangani baris dinamis secara otomatis)
    const table = document.querySelector('#table-item');

    // Deteksi perubahan pada dropdown Produk
    table.addEventListener('change', function(e) {
        if (e.target.classList.contains('select-produk')) {
            fetchHarga(e.target);
        }
    });

    // Deteksi input pada Qty atau Harga Jual
    table.addEventListener('input', function(e) {
        if (e.target.classList.contains('qty') || e.target.classList.contains('harga')) {
            let row = e.target.closest('tr');
            hitungSubtotal(row);
        }
    });

    // Deteksi penghapusan baris menu
    table.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-remove')) {
            let rows = document.querySelectorAll('#table-item tbody tr');
            if(rows.length > 1) {
                e.target.closest('tr').remove();
            } else {
                alert('Minimal harus ada 1 item produk dalam rekap penjualan!');
            }
            hitungTotal();
        }
    });

    // 6. DETEKSI PERUBAHAN TANGGAL REKAP (Untuk update harga otomatis)
    document.getElementById('input-tanggal').addEventListener('change', function() {
        document.querySelectorAll('.select-produk').forEach(function(selectElement) {
            if (selectElement.value !== "") {
                fetchHarga(selectElement);
            }
        });
    });

});
</script>

</x-app-layout>