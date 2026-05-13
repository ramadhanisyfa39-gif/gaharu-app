<x-app-layout>
<!DOCTYPE html>
<html>
<head>
    <title>Detail Pesanan</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body style="background-color: #f5f6fa;">

<div class="container mt-5">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="fw-bold mb-0">
                Detail Pesanan
            </h2>

            <small class="text-muted">
                Informasi lengkap transaksi pesanan
            </small>

        </div>

        <a href="{{ route('pesanan.index') }}"
           class="btn btn-secondary">

            Kembali

        </a>

    </div>

    <!-- CARD HEADER -->
    <div class="card border-0 shadow-sm mb-4">

        <div class="card-body">

            <div class="row">

                <div class="col-md-3 mb-3">

                    <label class="text-muted">
                        Kode Pesanan
                    </label>

                    <h5>
                        {{ $pesanan->kode_pesanan }}
                    </h5>

                </div>

                <div class="col-md-3 mb-3">

                    <label class="text-muted">
                        Customer
                    </label>

                    <h5>
                        {{ $pesanan->customer->nama }}
                    </h5>

                </div>

                <div class="col-md-3 mb-3">

                    <label class="text-muted">
                        Tanggal
                    </label>

                    <h5>
                        {{ date('d M Y H:i', strtotime($pesanan->tanggal)) }}
                    </h5>

                </div>

                <div class="col-md-3 mb-3">

                    <label class="text-muted">
                        Status
                    </label>

                    <br>

                    @if($pesanan->status_pesanan == 'pending')

                        <span class="badge bg-warning text-dark">
                            Pending
                        </span>

                    @elseif($pesanan->status_pesanan == 'diproses')

                        <span class="badge bg-info">
                            Diproses
                        </span>

                    @elseif($pesanan->status_pesanan == 'dikirim')

                        <span class="badge bg-primary">
                            Dikirim
                        </span>

                    @elseif($pesanan->status_pesanan == 'selesai')

                        <span class="badge bg-success">
                            Selesai
                        </span>

                    @else

                        <span class="badge bg-danger">
                            Batal
                        </span>

                    @endif

                </div>

            </div>

        </div>

    </div>

    <!-- DETAIL -->
    <div class="card border-0 shadow-sm">

        <div class="card-body">

            <h5 class="mb-3">
                Detail Produk
            </h5>

            <div class="table-responsive">

                <table class="table table-bordered align-middle">

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
                                {{ $detail->produk->nama }}
                            </td>

                            <td>
                                {{ $detail->qty }}
                            </td>

                            <td>
                                Rp {{ number_format($detail->harga, 0, ',', '.') }}
                            </td>

                            <td>
                                Rp {{ number_format($detail->subtotal, 0, ',', '.') }}
                            </td>

                        </tr>

                        @endforeach

                    </tbody>

                    <tfoot>

                        <tr>

                            <th colspan="3"
                                class="text-end">

                                Total

                            </th>

                            <th>

                                Rp {{ number_format($pesanan->total_pesanan, 0, ',', '.') }}

                            </th>

                        </tr>

                    </tfoot>

                </table>

            </div>

        </div>

    </div>

</div>

</body>
</html>
</x-app-layout>