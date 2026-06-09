<x-app-layout>

@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="container">

<h3 class="mb-3">Tambah Penjualan POS</h3>

<div class="card">
<div class="card-body">

<form action="{{ route('penjualan_pos.store') }}"
      method="POST">

@csrf

<div class="row mb-3">

    <div class="col-md-6">
        <label>Tanggal</label>
        <input type="datetime-local"
               name="tanggal"
               id="input-tanggal"
               class="form-control"
               value="{{ now()->format('Y-m-d\TH:i') }}"
               required>
    </div>

    <div class="col-md-6">
        <label>Gudang Operasional</label>
        <select name="gudang_id"
                class="form-control"
                required>

            <option value="">-- Pilih Gudang --</option>
            @foreach($gudang as $g)
                <option value="{{ $g->id }}">
                    {{ $g->nama }}
                </option>
            @endforeach

        </select>
    </div>

</div>

<hr>

<table class="table table-bordered" id="table-item">
    <thead>
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
                <select name="produk_id[]"
                        class="form-control select-produk"
                        required>

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
                <button type="button"
                        class="btn btn-danger btn-remove">
                    X
                </button>
            </td>
        </tr>
    </tbody>
</table>

<button type="button"
        class="btn btn-secondary mb-3"
        id="btn-add">
    + Tambah Item
</button>

<h4>Total: Rp <span id="grand-total">0</span></h4>

<button type="submit" class="btn btn-primary">
    Simpan Data
</button>

<a href="{{ route('penjualan_pos.index') }}"
   class="btn btn-secondary">
    Kembali
</a>

</form>

</div>
</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    
    // 1. TAMBAH BARIS ITEM
    document.getElementById('btn-add').addEventListener('click', function(){
        let row = document.querySelector('#table-item tbody tr').cloneNode(true);
        
        row.querySelectorAll('input').forEach(input => {
            input.value = '';
        });
        
        row.querySelectorAll('select').forEach(select => {
            select.selectedIndex = 0;
        });
        
        document.querySelector('#table-item tbody').appendChild(row);
    });

    // 2. FUNGSI HITUNG GRAND TOTAL
    function hitungTotal() {
        let total = 0;
        document.querySelectorAll('.subtotal').forEach(function(item){
            total += parseFloat(item.value || 0);
        });
        document.getElementById('grand-total').innerText = total.toLocaleString('id-ID');
    }

    // 3. FUNGSI HITUNG SUBTOTAL PER BARIS
    function hitungSubtotal(row) {
        let qty = parseFloat(row.querySelector('.qty').value || 0);
        let harga = parseFloat(row.querySelector('.harga').value || 0);
        let subtotal = qty * harga;
        
        row.querySelector('.subtotal').value = subtotal;
        hitungTotal();
    }

    // 4. FUNGSI PANGGIL API HARGA
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
                    inputHarga.value = data.harga;
                    hitungSubtotal(row);
                })
                .catch(error => {
                    console.error('Error fetching price:', error);
                    alert('Gagal mengambil harga: ' + error.message);
                    inputHarga.value = 0;
                    hitungSubtotal(row);
                });
        } else {
            inputHarga.value = 0;
            hitungSubtotal(row);
        }
    }

    // 5. EVENT DELEGATION UNTUK SELURUH TABEL
    const table = document.querySelector('#table-item');

    table.addEventListener('change', function(e) {
        if (e.target.classList.contains('select-produk')) {
            fetchHarga(e.target);
        }
    });

    table.addEventListener('input', function(e) {
        if (e.target.classList.contains('qty') || e.target.classList.contains('harga')) {
            let row = e.target.closest('tr');
            hitungSubtotal(row);
        }
    });

    table.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-remove')) {
            let rows = document.querySelectorAll('#table-item tbody tr');
            if(rows.length > 1) {
                e.target.closest('tr').remove();
            }
            hitungTotal();
        }
    });

    // 6. DETEKSI PERUBAHAN TANGGAL
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