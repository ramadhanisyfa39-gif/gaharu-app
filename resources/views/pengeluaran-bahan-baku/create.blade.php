<x-app-layout>

<x-slot name="header">
    Pengeluaran Bahan Baku
</x-slot>

<div class="page-header mb-4">

    <div class="d-flex justify-content-between align-items-center">

        <div>

            <h1 class="page-header-title">
                Tambah Pengeluaran Bahan Baku
            </h1>

            <p class="text-muted mb-0">
                Buat permintaan pengeluaran bahan baku dari Gudang Utama ke gudang tujuan.
            </p>

        </div>

        <a href="{{ route('pengeluaran-bahan-baku.index') }}"
           class="btn btn-outline-secondary">

            <i class="bi bi-arrow-left"></i>
            Kembali

        </a>

    </div>

</div>

@if($barang->count() == 0)

<div class="alert alert-danger">

    <strong>
        Stok bahan baku tidak tersedia.
    </strong>

    Silakan lakukan pembelian terlebih dahulu.

</div>

@endif

<div class="card">

    <div
        class="card-header text-white fw-bold"
        style="
            background:#9c4f18;
            border-radius:24px 24px 0 0;
        ">

        <i class="bi bi-box-seam me-2"></i>

        Informasi Pengeluaran

    </div>

    <div class="card-body p-4">

        <form
            method="POST"
            action="{{ route('pengeluaran-bahan-baku.store') }}">

            @csrf

            <div class="mb-4">

                <label class="form-label fw-bold">
                    Gudang Tujuan
                </label>

                <select
                    name="gudang_id"
                    class="form-select"
                    required>

                    <option value="">
                        -- Pilih Gudang Tujuan --
                    </option>

                    @foreach($gudang as $g)

                    <option value="{{ $g->id }}">

                        {{ $g->nama }}
                        -
                        {{ $g->kategori }}

                    </option>

                    @endforeach

                </select>

                <small class="text-muted">

                    Bahan baku akan dipindahkan dari Gudang Utama
                    ke gudang tujuan yang dipilih.

                </small>

            </div>

            <hr>

            <div class="d-flex justify-content-between align-items-center mb-3">

                <h5 class="fw-bold mb-0">

                    Detail Bahan Baku

                </h5>

                <button
                    type="button"
                    onclick="tambahBaris()"
                    class="btn btn-sm"
                    style="
                        background:#f7f3ee;
                        border:1px solid #d88656;
                        color:#9c4f18;
                        border-radius:10px;
                    "
                    {{ $barang->count() == 0 ? 'disabled' : '' }}>

                    <i class="bi bi-plus-circle"></i>

                    Tambah Barang

                </button>

            </div>

            <div class="table-responsive">

                <table
                    class="table align-middle"
                    id="table-detail">

                    <thead
                        style="
                            background:#5a3416;
                            color:white;
                        ">

                        <tr>

                            <th>Barang</th>

                            <th width="200">
                                Qty Keluar
                            </th>

                            <th width="120">
                                Aksi
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        <tr>

                            <td>

                                <select
                                    name="barang_id[]"
                                    class="form-select barang-select"
                                    required
                                    {{ $barang->count() == 0 ? 'disabled' : '' }}>

                                    <option value="">
                                        -- Pilih Bahan Baku --
                                    </option>

                                    @foreach($barang as $b)

                                    <option
                                        value="{{ $b->id }}"
                                        data-stok="{{ $b->stok }}">

                                        {{ $b->kode_barang }}
                                        -
                                        {{ $b->nama }}
                                        ({{ $b->satuan }})

                                        @if($b->stok <= 0)
                                        - STOK HABIS
                                        @endif

                                    </option>

                                    @endforeach

                                </select>

                            </td>

                            <td>

                                <input
                                    type="number"
                                    name="qty[]"
                                    class="form-control"
                                    min="1"
                                    step="0.01"
                                    placeholder="Qty"
                                    required>

                            </td>

                            <td>

                                <button
                                    type="button"
                                    class="btn btn-danger btn-sm"
                                    onclick="hapusBaris(this)">

                                    Hapus

                                </button>

                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>

            <div class="mt-4">

                <label class="form-label fw-bold">

                    Keterangan

                </label>

                <textarea
                    name="keterangan"
                    rows="4"
                    class="form-control"
                    placeholder="Contoh: Pengeluaran bahan baku untuk produksi kopi robusta"></textarea>

            </div>

            <div
                class="p-3 rounded mt-4"
                style="
                    background:#fff8e8;
                    border:1px solid #f2d28c;
                    color:#7a5a00;
                ">

                <i class="bi bi-exclamation-triangle me-2"></i>

                Stok belum berpindah saat data disimpan.

                Pengurangan stok FIFO baru dilakukan
                setelah pengeluaran disetujui.

            </div>

            <div class="mt-4">

                <button
                    id="btnSimpan"
                    type="submit"
                    class="btn"
                    style="
                        background:#d88656;
                        color:white;
                        font-weight:600;
                        padding:12px 24px;
                        border-radius:12px;
                    "
                    {{ $barang->count() == 0 ? 'disabled' : '' }}>

                    <i class="bi bi-save me-2"></i>

                    Simpan Pengeluaran

                </button>

            </div>

        </form>

    </div>

</div>

<script>

function tambahBaris()
{
    let tbody =
        document.querySelector(
            '#table-detail tbody'
        );

    let row = `
        <tr>

            <td>

                <select
                    name="barang_id[]"
                    class="form-select barang-select"
                    required>

                    <option value="">
                        -- Pilih Bahan Baku --
                    </option>

                    @foreach($barang as $b)

                    <option
                        value="{{ $b->id }}"
                        data-stok="{{ $b->stok }}">

                        {{ $b->kode_barang }}
                        -
                        {{ $b->nama }}
                        ({{ $b->satuan }})

                    </option>

                    @endforeach

                </select>

            </td>

            <td>

                <input
                    type="number"
                    name="qty[]"
                    class="form-control"
                    min="1"
                    step="0.01"
                    required>

            </td>

            <td>

                <button
                    type="button"
                    class="btn btn-danger btn-sm"
                    onclick="hapusBaris(this)">

                    Hapus

                </button>

            </td>

        </tr>
    `;

    tbody.insertAdjacentHTML(
        'beforeend',
        row
    );
}

function hapusBaris(button)
{
    let row = button.closest('tr');

    if(document.querySelectorAll('#table-detail tbody tr').length > 1)
    {
        row.remove();
    }
}

</script>

</x-app-layout>
