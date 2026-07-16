<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kwitansi - {{ $pesanan->kode_pesanan }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Arial', sans-serif; color: #333; }
        .invoice-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #eee; box-shadow: 0 0 10px rgba(0, 0, 0, 0.15); font-size: 16px; line-height: 24px; }
        .invoice-header { display: flex; justify-content: space-between; margin-bottom: 40px; border-bottom: 2px solid #eee; padding-bottom: 20px; }
        .invoice-title { font-size: 32px; font-weight: bold; color: #198754; }
        @media print {
            .no-print { display: none !important; }
            .invoice-box { box-shadow: none; border: none; margin: 0; padding: 0; }
        }
    </style>
</head>
<body>

<div class="container mt-5 mb-5 invoice-box">
    
    <div class="text-end mb-4 no-print">
        <a href="{{ route('pesanan.index') }}" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Kembali</a>
        <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="bi bi-printer"></i> Cetak Kwitansi</button>
    </div>

    <div class="invoice-header">
        <div>
            <h2>CV GAHARU AGUNG SEJAHTERA</h2>
            <small class="text-muted">Jl. Sarwo Edi Wibowo, Plamongan Sari, <br> Kec. Pedurungan, Kota Semarang, Jawa Tengah 50192<br>Telp: 0813-5135-6168</small>
        </div>
        <div class="text-end">
            <div class="invoice-title">KWITANSI</div>
            <strong>Kode Pesanan:</strong> {{ $pesanan->kode_pesanan }}<br>
            <strong>Tanggal:</strong> {{ date('d M Y', strtotime($pesanan->tanggal ?? $pesanan->created_at)) }}
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-sm-6">
            <h6 class="text-muted mb-1">Diterima dari (Customer):</h6>
            <strong>{{ $pesanan->customer->nama ?? $pesanan->customer->name ?? '-' }}</strong><br>
            {{ $pesanan->customer->alamat ?? 'Alamat tidak tersedia' }}<br>
            {{ $pesanan->customer->telepon ?? '-' }}
        </div>
        <div class="col-sm-6 text-end">
            <h6 class="text-muted mb-1">Status:</h6>
            @if($pesanan->status_pembayaran == 'Lunas')
                <span class="badge bg-success fs-6">LUNAS</span>
            @elseif($pesanan->status_pembayaran == 'DP')
                <span class="badge bg-warning text-dark fs-6">DP / DIBAYAR SEBAGIAN</span>
            @else
                <span class="badge bg-danger fs-6">BELUM BAYAR</span>
            @endif
        </div>
    </div>

    <h6 class="border-bottom pb-2 font-weight-bold">Ringkasan Pesanan</h6>
    <div class="row mb-4">
        <div class="col-6 text-secondary">Dasar Pengenaan Pajak (DPP):</div>
        <div class="col-6 text-end">Rp {{ number_format($pesanan->total_pesanan, 0, ',', '.') }}</div>
        
        <div class="col-6 text-secondary">PPN (10%):</div>
        <div class="col-6 text-end">Rp {{ number_format($pesanan->total_pesanan * 0.10, 0, ',', '.') }}</div>
        
        <div class="col-6 fw-bold border-top pt-2">Grand Total (Termasuk PPN):</div>
        <div class="col-6 text-end fw-bold border-top pt-2 fs-5 text-primary">Rp {{ number_format($pesanan->total_pesanan * 1.10, 0, ',', '.') }}</div>
    </div>

    <h6 class="border-bottom pb-2 font-weight-bold">Riwayat Pembayaran (Termasuk PPN)</h6>
    <table class="table table-sm mb-4">
        <thead class="table-light">
            <tr>
                <th>Tanggal Bayar</th>
                <th>Metode</th>
                <th>Catatan</th>
                <th class="text-end">Nominal</th>
            </tr>
        </thead>
        <tbody>
            @php $totalTelahDibayar = 0; @endphp
            @forelse($pesanan->pembayaran as $bayar)
                @php $totalTelahDibayar += $bayar->jumlah_bayar; @endphp
                <tr>
                    <td>{{ date('d M Y', strtotime($bayar->tanggal_bayar)) }}</td>
                    <td>{{ $bayar->metode_pembayaran }}</td>
                    <td>{{ $bayar->catatan ?? '-' }}</td>
                    <td class="text-end text-success fw-bold">Rp {{ number_format($bayar->jumlah_bayar * 1.10, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center text-muted">Belum ada pembayaran</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-end fw-bold">Total Telah Dibayar:</td>
                <td class="text-end fw-bold text-success border-top">Rp {{ number_format($totalTelahDibayar * 1.10, 0, ',', '.') }}</td>
            </tr>
            @php 
                $grandTotal = $pesanan->total_pesanan * 1.10;
                $totalTelahDibayarPpn = $totalTelahDibayar * 1.10;
                $sisaTagihan = $grandTotal - $totalTelahDibayarPpn; 
            @endphp
            <tr>
                <td colspan="3" class="text-end fw-bold">Sisa Tagihan:</td>
                <td class="text-end fw-bold {{ $sisaTagihan > 0 ? 'text-danger' : 'text-dark' }}">
                    Rp {{ number_format(max($sisaTagihan, 0), 0, ',', '.') }}
                </td>
            </tr>
        </tfoot>
    </table>

    <br><br>
    
    <div class="row text-center mt-5">
        <div class="col-6">
            <p>Penerima,</p>
            <br><br><br>
            <p class="mb-0 fw-bold">( .................................. )</p>
            <small class="text-muted">Admin / Kasir</small>
        </div>
        <div class="col-6">
            <p>Penyetor,</p>
            <br><br><br>
            <p class="mb-0 fw-bold">( {{ $pesanan->customer->nama ?? $pesanan->customer->name ?? '..................................' }} )</p>
            <small class="text-muted">Customer</small>
        </div>
    </div>

</div>

<script>
    // Uncomment baris di bawah jika ingin otomatis muncul popup print saat halaman dibuka
    // window.onload = function() { window.print(); }
</script>

</body>
</html>