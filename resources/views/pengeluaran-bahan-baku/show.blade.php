<!DOCTYPE html>
<html>
<head>
    <title>Detail Pengeluaran Bahan Baku</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-1">Detail Pengeluaran Bahan Baku</h3>
            <p class="text-muted mb-0">Informasi detail transaksi pengeluaran bahan baku.</p>
        </div>

        <a href="{{ route('pengeluaran-bahan-baku.index') }}" class="btn btn-secondary">
            Kembali
        </a>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            Informasi Pengeluaran
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <strong>Kode Pengeluaran</strong>
                    <div>{{ $pengeluaran->kode_pengeluaran }}</div>
                </div>

                <div class="col-md-6 mb-3">
                    <strong>Tanggal</strong>
                    <div>{{ $pengeluaran->tanggal }}</div>
                </div>

                <div class="col-md-6 mb-3">
                    <strong>Gudang Tujuan</strong>
                    <div>{{ $pengeluaran->gudang->nama ?? '-' }}</div>
                </div>

                <div class="col-md-6 mb-3">
                    <strong>Status</strong>
                    <div>
                        @if($pengeluaran->status == 'draft')
                            <span class="badge bg-warning text-dark">Draft</span>
                        @else
                            <span class="badge bg-success">Disetujui</span>
                        @endif
                    </div>
                </div>

                <div class="col-md-12 mb-3">
                    <strong>Keterangan</strong>
                    <div>{{ $pengeluaran->keterangan ?? '-' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white">
            Detail Barang
        </div>

        <div class="card-body p-0">
            <table class="table table-bordered mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="60">No</th>
                        <th>Barang</th>
                        <th width="150">Qty</th>
                        <th width="150">Satuan</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($pengeluaran->details as $detail)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $detail->barang->nama ?? '-' }}</td>
                            <td>{{ $detail->qty }}</td>
                            <td>{{ $detail->barang->satuan ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted">
                                Tidak ada detail barang
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($pengeluaran->status == 'draft')
        <div class="mt-4 d-flex gap-2">
            <a href="{{ route('pengeluaran-bahan-baku.edit', $pengeluaran->id) }}"
               class="btn btn-warning">
                Edit Pengeluaran
            </a>

            <a href="{{ route('pengeluaran-bahan-baku.approve', $pengeluaran->id) }}"
               class="btn btn-success"
               onclick="return confirm('Approve pengeluaran ini?')">
                Approve Pengeluaran
            </a>
        </div>
    @endif

</div>

</body>
</html>