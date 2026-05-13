<x-app-layout>

<!DOCTYPE html>
<html>
<head>
    <title>Tambah Pesanan</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body style="background-color: #f8fafc;">

<div class="container mt-5">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="fw-bold mb-1">
                Tambah Pesanan
            </h2>

            <small class="text-muted">
                Form input pesanan customer
            </small>

        </div>

        <!-- KEMBALI -->
        <a href="{{ route('pesanan.index') }}"
           class="btn btn-secondary rounded-3">

            Kembali

        </a>

    </div>

    <!-- CARD -->
    <div class="card border-0 shadow-sm rounded-4">

        <div class="card-body p-4">

            <form action="{{ route('pesanan.store') }}"
                  method="POST">

                @csrf

                <!-- KODE -->
                <div class="mb-3">

                    <label class="form-label">
                        Kode Pesanan
                    </label>

                    <input type="text"
                           name="kode_pesanan"
                           class="form-control"
                           value="P{{ rand(100,999) }}"
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

                        <option value="{{ $customer->id }}">
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
                           class="form-control">

                </div>

                <!-- ESTIMASI -->
                <div class="mb-4">

                    <label class="form-label">
                        Estimasi Kirim
                    </label>

                    <input type="datetime-local"
                           name="estimasi_kirim"
                           class="form-control">

                </div>

                <hr class="mb-4">

                <!-- DETAIL -->
                <h5 class="fw-semibold mb-3">
                    Detail Pesanan
                </h5>

                <div class="table-responsive">

                    <table class="table table-bordered align-middle"
                           id="table-detail">

                        <thead class="table-light">

                            <tr>
                                <th>Produk</th>
                                <th width="150">Qty</th>
                                <th width="200">Harga</th>
                                <th width="200">Subtotal</th>
                            </tr>

                        </thead>

                        <tbody>

                            <tr>

                                <!-- PRODUK -->
                                <td>

                                    <select name="produk_id[]"
                                            class="form-select produk">

                                        <option value="">
                                            -- Pilih Produk --
                                        </option>

                                        @foreach($produk as $p)

                                        <option value="{{ $p->id }}"
                                                data-harga="{{ $p->harga_jual_b2b }}">

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
                                           min="1">

                                </td>

                                <!-- HARGA -->
                                <td>

                                    <input type="number"
                                           name="harga[]"
                                           class="form-control harga"
                                           readonly>

                                </td>

                                <!-- SUBTOTAL -->
                                <td>

                                    <input type="number"
                                           name="subtotal[]"
                                           class="form-control subtotal"
                                           readonly>

                                </td>

                            </tr>

                        </tbody>

                    </table>

                </div>

                <!-- TAMBAH -->
                <button type="button"
                        class="btn btn-outline-secondary rounded-3 mb-4"
                        id="tambah-baris">

                    + Tambah Baris

                </button>

                <!-- TOTAL -->
                <div class="mb-4">

                    <label class="form-label fw-semibold">
                        Total Pesanan
                    </label>

                    <input type="number"
                           name="total_pesanan"
                           id="total_pesanan"
                           class="form-control"
                           readonly>

                </div>

                <!-- BUTTON -->
                <button type="submit"
                        class="btn btn-primary rounded-3">

                    Simpan Pesanan

                </button>

            </form>

        </div>

    </div>

</div>

<script>

function hitungSubtotal(row)
{
    let qty = parseFloat(
        row.querySelector('.qty').value
    ) || 0;

    let harga = parseFloat(
        row.querySelector('.harga').value
    ) || 0;

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

function isiHarga(row)
{
    let produkSelect =
        row.querySelector('.produk');

    let selectedOption =
        produkSelect.options[
            produkSelect.selectedIndex
        ];

    let harga =
        selectedOption.dataset.harga || 0;

    row.querySelector('.harga').value = harga;

    hitungSubtotal(row);
}

// PRODUK
document.addEventListener('change', function(e) {

    if (
        e.target.classList.contains('produk')
    ) {

        let row =
            e.target.closest('tr');

        isiHarga(row);
    }
});

// QTY
document.addEventListener('input', function(e) {

    if (
        e.target.classList.contains('qty')
    ) {

        let row =
            e.target.closest('tr');

        hitungSubtotal(row);
    }
});

// TAMBAH BARIS
document.getElementById('tambah-baris')
.addEventListener('click', function () {

    let table =
        document.querySelector('#table-detail tbody');

    let firstRow =
        table.rows[0];

    let newRow =
        firstRow.cloneNode(true);

    // reset input
    newRow.querySelectorAll('input')
    .forEach(input => {

        input.value = '';
    });

    // reset dropdown
    newRow.querySelector('.produk')
          .selectedIndex = 0;

    table.appendChild(newRow);
});

</script>

</body>
</html>

</x-app-layout>