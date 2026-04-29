<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Riwayat Jurnal Umum
        </h2>
    </x-slot>

    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold">Riwayat Jurnal Umum</h2>
            <a href="{{ route('jurnal.create') }}" class="btn btn-primary shadow-sm">
                <i class="fas fa-plus"></i> Tambah Jurnal
            </a>
        </div>

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>No. Ref</th>
                                <th>Deskripsi</th>
                                <th class="text-end">Total Debit</th>
                                <th class="text-end">Total Kredit</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($jurnals as $j)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($j->tanggal)->format('d/m/Y') }}</td>
                                <td><span class="badge bg-light text-dark border font-monospace">{{ $j->no_ref }}</span></td>
                                <td>{{ Str::limit($j->deskripsi, 50) }}</td>
                                <td class="text-end fw-semibold text-success">
                                    Rp {{ number_format($j->details->sum('debit'), 0, ',', '.') }}
                                </td>
                                <td class="text-end fw-semibold text-danger">
                                    Rp {{ number_format($j->details->sum('kredit'), 0, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('jurnal.show', $j->id) }}" class="btn btn-outline-info btn-sm">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Belum ada transaksi jurnal yang tercatat.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    </script>
</x-app-layout>