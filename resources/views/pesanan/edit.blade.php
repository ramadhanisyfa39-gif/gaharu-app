<x-app-layout>

<!DOCTYPE html>
<html>
<head>
    <title>Form Edit Pesanan</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body style="background-color: #f8fafc;">

<div class="container mt-5">

    <!-- HEADER -->
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

    <!-- CARD -->
    <div class="card border-0 shadow-sm rounded-4">

        <div class="card-body p-4">

            <form action="{{ route('pesanan.update', $pesanan->id) }}"
                  method="POST">

                @csrf
                @method('PUT')

                <!-- KODE -->
                <div class="mb-3">

                    <label class="form-label">
                        Kode Pesanan
                    </label>

                    <input type="text"
                           class="form-control"
                           value="{{ $pesanan->kode_pesanan }}"
                           readonly>

                </div>

                <!-- CUSTOMER -->
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

                <!-- TANGGAL -->
                <div class="mb-3">

                    <label class="form-label">
                        Tanggal
                    </label>

                    <input type="datetime-local"
                           name="tanggal"
                           class="form-control"
                           value="{{ date('Y-m-d\TH:i', strtotime($pesanan->tanggal)) }}">

                </div>

                <!-- ESTIMASI -->
                <div class="mb-3">

                    <label class="form-label">
                        Estimasi Kirim
                    </label>

                    <input type="datetime-local"
                           name="estimasi_kirim"
                           class="form-control"
                           value="{{ date('Y-m-d\TH:i', strtotime($pesanan->estimasi_kirim)) }}">

                </div>

                <!-- STATUS -->
                <div class="mb-4">

                    <label class="form-label">
                        Status Pesanan
                    </label>

                    <select name="status_pesanan"
                            class="form-select">

                        <option value="pending"
                            {{ $pesanan->status_pesanan == 'pending' ? 'selected' : '' }}>
                            Pending
                        </option>

                        <option value="diproses"
                            {{ $pesanan->status_pesanan == 'diproses' ? 'selected' : '' }}>
                            Diproses
                        </option>

                        <option value="dikirim"
                            {{ $pesanan->status_pesanan == 'dikirim' ? 'selected' : '' }}>
                            Dikirim
                        </option>

                        <option value="selesai"
                            {{ $pesanan->status_pesanan == 'selesai' ? 'selected' : '' }}>
                            Selesai
                        </option>

                        <option value="batal"
                            {{ $pesanan->status_pesanan == 'batal' ? 'selected' : '' }}>
                            Batal
                        </option>

                    </select>

                </div>

                <hr class="mb-4">

                <!-- DETAIL -->
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

                                <!-- PRODUK -->
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

                                <!-- QTY -->
                                <td>

                                    <input type="number"
                                           name="qty[]"
                                           class="form-control qty"
                                           value="{{ $detail->qty }}">

                                </td>

                                <!-- HARGA -->
                                <td>

                                    <input type="number"
                                           name="harga[]"
                                           class="form-control harga"
                                           value="{{ $detail->harga }}"
                                           readonly>

                                </td>

                                <!-- SUBTOTAL -->
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

                <!-- TOTAL -->
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

                <!-- BUTTON -->
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

// QTY
document.addEventListener('input', function(e) {

    if (e.target.classList.contains('qty')) {

        let row = e.target.closest('tr');

        hitungSubtotal(row);
    }
});

// PRODUK
document.addEventListener('change', function(e) {

    if (e.target.classList.contains('produk')) {

        let row = e.target.closest('tr');

        let harga =
            e.target.options[e.target.selectedIndex]
            .dataset.harga;

        row.querySelector('.harga').value = harga;

        hitungSubtotal(row);
    }
});

// LOAD
document.querySelectorAll('tbody tr')
.forEach(function(row) {

    hitungSubtotal(row);
});

</script>

</body>
</html>

</x-app-layout>