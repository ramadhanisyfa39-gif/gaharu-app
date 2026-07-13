<x-app-layout>

<!DOCTYPE html>
<html>
<head>
    <title>Form Edit Pesanan</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body style="background-color: #f8fafc;">

<div class="container mt-5">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="fw-bold mb-1">
                Edit Pesanan
            </h2>

            <small class="text-muted">
                Form edit data pesanan customer
            </small>

        </div>

        <a href="{{ route('pesanan.index') }}"
           class="btn btn-secondary rounded-3">

            Kembali

        </a>

    </div>

    <div class="card border-0 shadow-sm rounded-4">

        <div class="card-body p-4">

            @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" role="alert">
                <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i> Terjadi Kesalahan Input:</div>
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            <form action="{{ route('pesanan.update', $pesanan->id) }}"
                  method="POST">

                @csrf
                @method('PUT')

                <div class="mb-3">

                    <label class="form-label">
                        Kode Pesanan
                    </label>

                    <input type="text"
                           class="form-control"
                           value="{{ $pesanan->kode_pesanan }}"
                           readonly>

                </div>

                <div class="mb-3">

                    <label class="form-label">
                        Customer
                    </label>

                    <select name="customer_id"
                            class="form-select">

                        @foreach($customers as $customer)

                        <option value="{{ $customer->id }}"
                            {{ $pesanan->customer_id == $customer->id ? 'selected' : '' }}>

                            {{ $customer->nama }}

                        </option>

                        @endforeach

                    </select>

                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Tanggal Transaksi</label>
                        <input type="datetime-local" name="tanggal" class="form-control" value="{{ date('Y-m-d\TH:i', strtotime($pesanan->tanggal)) }}" required>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Estimasi Tanggal Produksi</label>
                        <input type="date" name="estimasi_produksi" class="form-control" value="{{ $pesanan->estimasi_produksi }}">
                    </div>

                    <div class="col-md-4 mb-4">
                        <label class="form-label">Estimasi Kirim</label>
                        <input type="datetime-local" name="estimasi_kirim" class="form-control" value="{{ date('Y-m-d\TH:i', strtotime($pesanan->estimasi_kirim)) }}" required>
                    </div>
                </div>

                {{-- REVISI: Bagian input dropdown "Status Pesanan" telah dihapus sepenuhnya di sini --}}

                <hr class="mb-4">

                <h5 class="fw-semibold mb-3">
                    Detail Pesanan
                </h5>

                <div class="table-responsive">

                    <table class="table align-middle table-bordered">

                        <thead class="table-light">

                            <tr>
                                <th>Produk</th>
                                <th width="150">Qty</th>
                                <th width="200">Harga</th>
                                <th width="200">Subtotal</th>
                            </tr>

                        </thead>

                        <tbody>

                            @foreach($pesanan->details as $detail)

                            <tr>

                                <td>

                                    <select name="produk_id[]"
                                            class="form-select produk">

                                        @foreach($produk as $p)

                                        <option value="{{ $p->id }}"
                                                data-harga="{{ $p->harga_jual_b2b }}"
                                                {{ $detail->produk_id == $p->id ? 'selected' : '' }}>

                                            {{ $p->nama }}

                                        </option>

                                        @endforeach

                                    </select>

                                </td>

                                <td>

                                    <input type="number"
                                           name="qty[]"
                                           class="form-control qty"
                                           value="{{ $detail->qty }}">

                                </td>

                                <td>

                                    <input type="number"
                                           name="harga[]"
                                           class="form-control harga"
                                           value="{{ $detail->harga }}">

                                </td>

                                <td>

                                    <input type="number"
                                           name="subtotal[]"
                                           class="form-control subtotal"
                                           value="{{ $detail->subtotal }}"
                                           readonly>

                                </td>

                            </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

                <div class="mb-4 mt-4">

                    <label class="form-label fw-semibold">
                        Total Pesanan
                    </label>

                    <input type="number"
                           name="total_pesanan"
                           id="total_pesanan"
                           class="form-control"
                           value="{{ $pesanan->total_pesanan }}"
                           readonly>

                </div>

                <button type="submit"
                        class="btn btn-primary rounded-3">

                    Update Pesanan

                </button>

            </form>

        </div>

    </div>

</div>

<script>

function hitungSubtotal(row)
{
    let qty =
        parseFloat(row.querySelector('.qty').value) || 0;

    let harga =
        parseFloat(row.querySelector('.harga').value) || 0;

    let subtotal = qty * harga;

    row.querySelector('.subtotal').value = subtotal;

    hitungTotal();
}

function hitungTotal()
{
    let total = 0;

    document.querySelectorAll('.subtotal')
    .forEach(function(item) {

        total += parseFloat(item.value) || 0;
    });

    document.getElementById('total_pesanan').value = total;
}

// Perubahan QTY atau HARGA secara manual menggunakan input event
document.addEventListener('input', function(e) {

    if (e.target.classList.contains('qty') || e.target.classList.contains('harga')) {

        let row = e.target.closest('tr');

        hitungSubtotal(row);
    }
});

// Perubahan PRODUK (Otomatis mengganti harga sesuai data-harga)
document.addEventListener('change', function(e) {

    if (e.target.classList.contains('produk')) {

        let row = e.target.closest('tr');
        
        let selectedOption = e.target.options[e.target.selectedIndex];
        let harga = selectedOption ? parseFloat(selectedOption.getAttribute('data-harga')) || 0 : 0;

        row.querySelector('.harga').value = harga;

        hitungSubtotal(row);
    }
});

// LOAD AWAL (Hitung total saat halaman pertama kali dibuka)
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('tbody tr').forEach(function(row) {
        hitungSubtotal(row);
    });
});

</script>

</body>
</html>

</x-app-layout>