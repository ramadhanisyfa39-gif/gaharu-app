@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Riwayat Penggajian</h2>
        <a href="{{ route('penggajian.create') }}" class="btn btn-primary shadow-sm">
            Input Gaji Baru
        </a>
    </div>

    @if(session('success'))
    <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Karyawan</th>
                        <th>Periode</th>
                        <th>Gaji Pokok</th>
                        <th>Lembur (+)</th>
                        <th>Potongan (-)</th>
                        <th>Gaji Bersih</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($penggajians as $p)
                    <tr>
                        <td>
                            <div class="fw-bold">{{ $p->karyawan->nama_karyawan }}</div>
                            <small class="text-muted">{{ $p->karyawan->jabatan }}</small>
                        </td>
                        <td>{{ $p->periode_bulan_tahun }}</td>
                        <td>Rp {{ number_format($p->gaji_pokok, 0, ',', '.') }}</td>
                        <td class="text-success">+ Rp {{ number_format($p->lembur, 0, ',', '.') }}</td>
                        <td class="text-danger">- Rp {{ number_format($p->potongan, 0, ',', '.') }}</td>
                        <td class="fw-bold">Rp {{ number_format($p->total_gaji_bersih, 0, ',', '.') }}</td>
                        <td>
                            <span class="badge bg-success">Ditransfer: {{ \Carbon\Carbon::parse($p->tanggal_transfer)->format('d/m/Y') }}</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection