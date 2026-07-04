<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Buku Pembantu Uang Muka Pembelian
        </h2>
    </x-slot>

    <div class="container py-4">
        <div class="mb-4">
            <h2 class="fw-bold mb-1">Buku Pembantu Uang Muka Pembelian (Subsidiary Ledger)</h2>
            <p class="text-muted small">Rekapitulasi mutasi saldo uang muka pembelian CV Gaharu Agung Sejahtera secara kronologis per supplier.</p>
        </div>

        <div class="card shadow border-0 rounded-3">
            <div class="card-header bg-dark text-white py-3">
                <h5 class="mb-0 fw-bold"><i class="fas fa-book me-2"></i>Daftar Saldo Uang Muka Per Supplier</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead class="table-light text-secondary small text-uppercase fw-bold">
                            <tr>
                                <th class="py-3 ps-4">Nama Supplier</th>
                                <th class="py-3">No. Invoice</th>
                                <th class="py-3">Tanggal Transaksi</th>
                                <th class="py-3 text-end">Uang Muka Keluar (+ Debit)</th>
                                <th class="py-3 text-end">Uang Muka Direklas (- Kredit)</th>
                                <th class="py-3 text-end text-primary">Sisa Uang Muka Menggantung</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $currentSupplier = ''; @endphp
                            @forelse($bukuPembantuUangMuka as $bpum)
                            @php
                            // Saldo normal Uang Muka (Aset) adalah Debit dikurangi Kredit
                            $sisaSaldo = $bpum->total_uang_muka_keluar - $bpum->total_uang_muka_direklas;
                            @endphp
                            <tr>
                                <td class="py-3 ps-4 fw-bold text-dark">
                                    @if($currentSupplier != $bpum->nama_supplier)
                                    {{ $bpum->nama_supplier }}
                                    @php $currentSupplier = $bpum->nama_supplier; @endphp
                                    @else
                                    <span class="text-muted font-weight-light">”</span>
                                    @endif
                                </td>
                                <td class="font-monospace text-secondary">{{ $bpum->kode_pembelian }}</td>
                                <td>{{ \Carbon\Carbon::parse($bpum->tanggal_transaksi)->format('d/m/Y') }}</td>
                                <td class="text-end text-success font-monospace">Rp {{ number_format($bpum->total_uang_muka_keluar, 0, ',', '.') }}</td>
                                <td class="text-end text-danger font-monospace">Rp {{ number_format($bpum->total_uang_muka_direklas, 0, ',', '.') }}</td>
                                <td class="text-end fw-bold text-primary font-monospace">
                                    @if($sisaSaldo == 0)
                                    <span class="badge bg-success text-white px-2 py-1">Selesai / Direklas</span>
                                    @else
                                    Rp {{ number_format($sisaSaldo, 0, ',', '.') }}
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="fas fa-info-circle me-1"></i> Tidak ada catatan mutasi uang muka supplier.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>