<x-app-layout>

<div class="container">

    <h3 class="mb-3">
        Edit Pengeluaran Bahan Baku
    </h3>

    <div class="card">
        <div class="card-body">

            <form
                action="{{ route('pengeluaran-bahan-baku.update', $pengeluaran->id) }}"
                method="POST">

                @csrf
                @method('PUT')

                <div class="mb-3">

                    <label>Gudang</label>

                    <select
                        name="gudang_id"
                        class="form-control"
                        required>

                        @foreach($gudang as $g)

                            <option
                                value="{{ $g->id }}"
                                {{ $pengeluaran->gudang_id == $g->id ? 'selected' : '' }}>

                                {{ $g->nama }}

                            </option>

                        @endforeach

                    </select>

                </div>

                <hr>

                <h5>Detail Barang</h5>

                <table class="table table-bordered">

                    <thead>
                        <tr>
                            <th>Barang</th>
                            <th width="150">Qty</th>
                        </tr>
                    </thead>

                    <tbody>

                        @foreach($pengeluaran->details as $detail)

                            <tr>

                                <td>

                                    <select
                                        name="barang_id[]"
                                        class="form-control barang-select"
                                        required>

                                        @foreach($barang as $b)

                                            <option
                                                value="{{ $b->id }}"
                                                data-stok="{{ $b->stok }}"
                                                {{ $detail->barang_id == $b->id ? 'selected' : '' }}>

                                                {{ $b->nama }} (Tersedia: {{ number_format($b->stok) }})

                                            </option>

                                        @endforeach

                                    </select>

                                </td>

                                <td>

                                    <input
                                        type="number"
                                        step="0.01"
                                        name="qty[]"
                                        value="{{ $detail->qty }}"
                                        class="form-control qty-input"
                                        required>
                                    <small class="text-danger stok-warning d-block mt-1" style="display:none;"></small>

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

                <div class="mb-3">

                    <label>Keterangan</label>

                    <textarea
                        name="keterangan"
                        class="form-control"
                        rows="3">{{ $pengeluaran->keterangan }}</textarea>

                </div>

                <button class="btn btn-primary">
                    Update
                </button>

                <a
                    href="{{ route('pengeluaran-bahan-baku.index') }}"
                    class="btn btn-secondary">

                    Kembali

                </a>

            </form>

        </div>
    </div>

</div>

<script>
function checkStok(row) {
    let select = row.querySelector('.barang-select');
    let qtyInput = row.querySelector('.qty-input');
    let warning = row.querySelector('.stok-warning');

    if (!select || !qtyInput || !warning) return;

    let selectedOption = select.options[select.selectedIndex];
    if (!selectedOption || select.value === "") {
        warning.style.display = "none";
        return;
    }

    let stok = parseFloat(selectedOption.getAttribute('data-stok')) || 0;
    let qty = parseFloat(qtyInput.value) || 0;

    if (qty > stok) {
        warning.innerHTML = `⚠️ Stok tidak mencukupi! Tersedia: <strong>${stok}</strong>`;
        warning.style.display = "block";
    } else {
        warning.style.display = "none";
    }
}

// Jalankan checkStok awal saat halaman dibuka untuk setiap baris
document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll('#table-detail tbody tr, table tbody tr').forEach(function(row) {
        checkStok(row);
    });
});

document.addEventListener('change', function(e) {
    if (e.target.classList.contains('barang-select')) {
        let row = e.target.closest('tr');
        checkStok(row);
    }
});

document.addEventListener('input', function(e) {
    if (e.target.classList.contains('qty-input')) {
        let row = e.target.closest('tr');
        checkStok(row);
    }
});
</script>

</x-app-layout>