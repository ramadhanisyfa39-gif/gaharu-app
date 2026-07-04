<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Detail Jurnal Khusus Pembelian</h2>
    </x-slot>

    <div class="container py-4">
        <div class="card shadow border-0 rounded-3 mb-4">
            <div class="card-header bg-info text-white py-3">
                <h5 class="mb-0 fw-bold"><i class="fas fa-info-circle me-2"></i>Informasi Dokumen Referensi</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3 mb-md-0">
                        <span class="text-secondary small d-block font-sans">No. Jurnal (Referensi)</span>
                        <strong class="fs-5 font-monospace text-dark">{{ $jurnal->no_ref }}</strong>
                    </div>
                    <div class="col-md-4 mb-3 mb-md-0">
                        <span class="text-secondary small d-block font-sans">Tanggal Pembukuan</span>
                        <strong class="fs-5 text-dark">{{ \Carbon\Carbon::parse($jurnal->tanggal)->format('d F Y') }}</strong>
                    </div>
                    <div class="col-md-4">
                        <span class="text-secondary small d-block font-sans">Keterangan / Deskripsi</span>
                        <p class="mb-0 text-muted small fw-medium">{{ $jurnal->deskripsi }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow border-0 rounded-3">
            <div class="card-header bg-light border-bottom py-3">
                <h5 class="mb-0 fw-bold text-secondary"><i class="fas fa-list me-2"></i>Rincian Posting Item Jurnal</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0">
                        <thead class="table-light text-secondary small text-uppercase fw-bold">
                            <tr>
                                <th class="py-3 ps-4" style="width: 50%">Akun Terikat (COA)</th>
                                <th class="py-3 text-end" style="width: 25%">Debit</th>
                                <th class="py-3 text-end" style="width: 25%">Kredit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($details as $d)
                            <tr>
                                <td class="py-3 ps-4 small {{ $d->kredit > 0 ? 'ps-5 text-secondary' : 'fw-semibold text-dark' }}">
                                    {{ $d->kode }} - {{ $d->nama ?? 'Akun Tidak Diketahui' }}
                                </td>

                                <td class="text-end font-monospace text-success fw-medium">
                                    {{ $d->debit > 0 ? 'Rp ' . number_format($d->debit, 2, ',', '.') : '-' }}
                                </td>

                                <td class="text-end font-monospace text-danger fw-medium">
                                    {{ $d->kredit > 0 ? 'Rp ' . number_format($d->kredit, 2, ',', '.') : '-' }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-secondary fw-bold">
                            <tr>
                                <td class="text-center">TOTAL KESEIMBANGAN (BALANCE)</td>
                                <td class="text-end text-success font-monospace">Rp {{ number_format($totalDebit, 2, ',', '.') }}</td>
                                <td class="text-end text-success font-monospace">Rp {{ number_format($totalKredit, 2, ',', '.') }}</td>
                            </tr>
                            </footer>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white py-3 d-flex justify-content-end">
                <a href="{{ route('jurnal-pembelian.index') }}" class="btn btn-secondary fw-bold px-4 shadow-sm">
                    <i class="fas fa-arrow-left me-1"></i> Kembali ke Riwayat
                </a>
            </div>
        </div>
    </div>
</x-app-layout>