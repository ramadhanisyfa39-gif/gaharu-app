<x-app-layout>
    <div class="container">
        <h4>Detail Pembelian</h4>

        <div class="card mb-3">
            <div class="card-body">
                <p>
                    <strong>Kode:</strong> 
                    {{ $pembelian->kode_pembelian }}
                </p>

                <p>
                    <strong>Tanggal:</strong> 
                    {{ $pembelian->tanggal }}
                </p>

                <p>
                    <strong>Supplier:</strong> 
                    {{ $pembelian->supplier->nama ?? '-' }}
                </p>

                <p>
                    <strong>Gudang:</strong> 
                    {{ $pembelian->gudang->nama ?? '-' }}
                </p>

                <p>
                    <strong>Total:</strong> 
                    Rp {{ number_format($pembelian->total, 0, ',', '.') }}
                </p>

                <p>
                    <strong>Metode Pembayaran:</strong> 
                    {{ strtoupper($pembelian->metode_pembayaran ?? 'Belum Dicatat') }}
                </p>

                @if($pembelian->metode_pembayaran === 'dp')
                    <p>
                        <strong>DP (Persentase / Nominal):</strong> 
                        {{ $pembelian->persen_dp }}% / Rp {{ number_format($pembelian->nominal_dp, 0, ',', '.') }}
                    </p>
                    <p>
                        <strong>Estimasi Pelunasan:</strong> 
                        {{ $pembelian->tanggal_pelunasan ? \Carbon\Carbon::parse($pembelian->tanggal_pelunasan)->format('d M Y') : '-' }}
                    </p>
                @endif

                @if($pembelian->metode_pembayaran === 'termin')
                    <p>
                        <strong>Jatuh Tempo:</strong> 
                        {{ $pembelian->tanggal_jatuh_tempo ? \Carbon\Carbon::parse($pembelian->tanggal_jatuh_tempo)->format('d M Y') : '-' }}
                    </p>
                    <p>
                        <strong>Estimasi Pelunasan:</strong> 
                        {{ $pembelian->tanggal_pelunasan ? \Carbon\Carbon::parse($pembelian->tanggal_pelunasan)->format('d M Y') : '-' }}
                    </p>
                @endif

                <p>
                    <strong>Status Pembayaran:</strong> 
                    @if($pembelian->is_lunas)
                        <span class="badge bg-success">Lunas ({{ $pembelian->lunas_at ? \Carbon\Carbon::parse($pembelian->lunas_at)->format('d M Y') : '' }})</span>
                    @else
                        <span class="badge bg-warning text-dark">Belum Lunas</span>
                    @endif
                </p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Barang</th>
                        <th>Qty</th>
                        <th>Harga</th>
                        <th>Subtotal</th>
                        <th>Batch</th>
                    </tr>
                </thead>
    
                <tbody>
                    @foreach($pembelian->details as $detail)
                        <tr>
                            <td>{{ $detail->barang->nama ?? '-' }}</td>
                            <td>{{ $detail->qty }}</td>
                            <td>
                                Rp {{ number_format($detail->harga_per_qty, 0, ',', '.') }}
                            </td>
                            <td>
                                Rp {{ number_format($detail->harga, 0, ',', '.') }}
                            </td>
                            <td>{{ $detail->batch_number }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <div class="mt-3">
            <a href="{{ route('pembelian.index') }}" class="btn btn-light border">
                Kembali
            </a>
        </div>
    </div>
</x-app-layout>