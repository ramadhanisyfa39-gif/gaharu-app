<x-app-layout>

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="fw-bold mb-0">Penjualan POS</h3>

        <div>
            <a href="{{ route('penjualan_pos.laporan') }}"
               class="btn btn-success px-4 me-2">
               📊 Lihat Laporan
            </a>

            <a href="{{ route('penjualan_pos.create') }}"
               class="btn btn-primary px-4">
               + Tambah
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
    </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            
            <div class="table-responsive">
                <table class="table table-hover align-middle text-nowrap mb-0">

                    <thead class="table-dark">
                        <tr>
                            <th class="ps-3">Kode</th>
                            <th>Tanggal</th>
                            <th>Gudang</th>
                            <th class="text-end">Total Omzet</th>
                            <th class="text-end">Total HPP</th>
                            <th class="text-end">Laba Kotor</th>
                            <th class="text-center" style="min-width: 180px;">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>

                        @foreach($data as $item)
                        @php
                            /**
                             * Menghitung total HPP per transaksi dari relasi detail.
                             * Pastikan 'details' sesuai dengan nama fungsi relasi di Model PenjualanPos
                             */
                            $totalHpp = $item->details ? $item->details->sum(fn($d) => $d->hpp_satuan * $d->qty) : 0;
                            $labaKotor = $item->total - $totalHpp;
                        @endphp

                        <tr>
                            <td class="ps-3 fw-semibold text-secondary">{{ $item->kode_transaksi }}</td>

                            <td>
                                {{ \Carbon\Carbon::parse($item->tanggal)->format('d-m-Y H:i') }}
                            </td>

                            <td>{{ $item->gudang->nama }}</td>

                            <td class="text-end fw-medium">
                                Rp {{ number_format($item->total, 0, ',', '.') }}
                            </td>

                            <td class="text-end text-muted">
                                Rp {{ number_format($totalHpp, 0, ',', '.') }}
                            </td>

                            <td class="text-end fw-bold text-success">
                                Rp {{ number_format($labaKotor, 0, ',', '.') }}
                            </td>

                            <td class="text-center">

                                <a href="{{ route('penjualan_pos.show', $item->id) }}"
                                   class="btn btn-info btn-sm text-white">
                                    Detail
                                </a>

                                <a href="{{ route('penjualan_pos.edit', $item->id) }}"
                                   class="btn btn-warning btn-sm text-white">
                                    Edit
                                </a>

                                <form action="{{ route('penjualan_pos.destroy', $item->id) }}"
                                   method="POST"
                                   class="d-inline">

                                    @csrf
                                    @method('DELETE')

                                    <button type="submit"
                                            class="btn btn-danger btn-sm"
                                            onclick="return confirm('Hapus data?')">
                                        Hapus
                                    </button>

                                </form>

                            </td>

                        </tr>

                        @endforeach

                    </tbody>

                </table>
            </div>
            
        </div>
    </div>
</div>

</x-app-layout>