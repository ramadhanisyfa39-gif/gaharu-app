<!DOCTYPE html>
<html>
<head>
    <title>Tambah Pengeluaran Bahan Baku</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-4">

    <div class="d-flex justify-content-between align-items-center mb-3">

        <div>
            <h3 class="mb-1">
                Tambah Pengeluaran Bahan Baku
            </h3>

            <p class="text-muted mb-0">
                Form ini digunakan untuk memindahkan bahan baku dari Gudang Utama ke gudang tujuan.
            </p>
        </div>

        <a href="{{ route('pengeluaran-bahan-baku.index') }}"
           class="btn btn-secondary">
            Kembali
        </a>

    </div>

    @if($barang->count() == 0)

        <div class="alert alert-danger">

            <strong>
                Stok tidak tersedia, lakukan pembelian lebih dulu!
            </strong>

        </div>

    @endif

    <div class="card shadow-sm">

        <div class="card-header bg-primary text-white">
            Informasi Pengeluaran
        </div>

        <div class="card-body">

            <form method="POST"
                  action="{{ route('pengeluaran-bahan-baku.store') }}">

                @csrf

                <div class="mb-3">

                    <label class="form-label fw-bold">
                        Gudang Tujuan
                    </label>

                    <select name="gudang_id"
                            class="form-select"
                            required>

                        <option value="">
                            -- Pilih Gudang Tujuan --
                        </option>

                        @foreach($gudang as $g)

                            <option value="{{ $g->id }}">
                                {{ $g->nama }} - {{ $g->kategori }}
                            </option>

                        @endforeach

                    </select>

                    <small class="text-muted">
                        Bahan baku akan dipindahkan dari Gudang Utama ke gudang tujuan yang dipilih.
                    </small>

                </div>

                <hr>

                <h6 class="fw-bold mb-3">
                    Detail Bahan Baku
                </h6>

                <table class="table table-bordered align-middle"
                       id="table-detail">

                    <thead class="table-light">

                        <tr>

                            <th>Barang</th>
                            <th width="220">Qty Keluar</th>
                            <th width="90">Aksi</th>

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

                                <input type="number"
                                       name="qty[]"
                                       class="form-control"
                                       min="1"
                                       step="0.01"
                                       placeholder="Qty"
                                       required
                                       {{ $barang->count() == 0 ? 'disabled' : '' }}>

                            </td>

                            <td class="text-center">

                                <button type="button"
                                        class="btn btn-danger btn-sm"
                                        onclick="hapusBaris(this)">

                                    Hapus

                                </button>

                            </td>

                        </tr>

                    </tbody>

                </table>

                <button type="button"
                        class="btn btn-outline-primary btn-sm mb-3"
                        onclick="tambahBaris()"
                        {{ $barang->count() == 0 ? 'disabled' : '' }}>

                    + Tambah Barang

                </button>

                <div class="mb-3">

                    <label class="form-label fw-bold">
                        Keterangan
                    </label>

                    <textarea name="keterangan"
                              class="form-control"
                              rows="3"
                              placeholder="Contoh: Pemindahan bahan baku ke Gudang Produksi"></textarea>

                </div>

                <div class="alert alert-warning">

                    Stok belum berpindah saat data disimpan.
                    Stok akan berpindah dari Gudang Utama ke Gudang Tujuan setelah pengeluaran di-approve.

                </div>

                <button
    id="btnSimpan"
    type="submit"
    class="btn btn-primary"
                        {{ $barang->count() == 0 ? 'disabled' : '' }}>

                    Simpan Pengeluaran

                </button>

            </form>

        </div>

    </div>

</div>

<script>

function tambahBaris() {

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

            <td class="text-center">

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

</script>
<script>

function cekStok()
{
    let warning =
        document.getElementById(
            'stokWarning'
        );

    let btn =
        document.getElementById(
            'btnSimpan'
        );

    let stokKosong = false;

    document
        .querySelectorAll(
            '.barang-select'
        )
        .forEach(function(select){

            let option =
                select.options[
                    select.selectedIndex
                ];

            let stok =
                parseFloat(
                    option.dataset.stok || 0
                );

            if(
                select.value &&
                stok <= 0
            ){
                stokKosong = true;
            }
        });

    if(stokKosong)
    {
        warning.classList.remove(
            'd-none'
        );

        btn.disabled = true;
    }
    else
    {
        warning.classList.add(
            'd-none'
        );

        btn.disabled = false;
    }
}

document.addEventListener(
    'change',
    function(e){

        if(
            e.target.classList.contains(
                'barang-select'
            )
        ){
            cekStok();
        }
    }
);

</script>
</body>
</html>