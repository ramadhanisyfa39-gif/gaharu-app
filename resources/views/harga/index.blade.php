<x-app-layout>
<div class="container py-4">
    <div class="row">
        {{-- Menampilkan Pesan Sukses / Error Global --}}
        <div class="col-md-12 mb-2">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if($errors->has('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-1"></i> {{ $errors->first('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
        </div>

        {{-- Header Section --}}
        <div class="col-md-12 mb-4">
            <h2 class="h4 fw-bold text-gray-800">Manajemen Harga Jual (POS)</h2>
            <p class="text-muted">Kelola periode harga khusus untuk item kategori <strong>Barang Jadi</strong>.</p>
        </div>

        {{-- Section 1: Pemilihan Barang --}}
        <div class="col-md-12 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-body bg-light">
                    <div class="row align-items-center">
                        <div class="col-md-2">
                            <label class="fw-bold text-uppercase small text-muted">Pilih Item:</label>
                        </div>
                        <div class="col-md-10">
                            <select class="form-select form-select-lg border-primary" onchange="window.location.href='/harga-barang-pos/' + this.value">
                                <option value="">-- Cari Nama atau Kode Barang Jadi --</option>
                                @foreach($listBarang as $b)
                                    <option value="{{ $b->id }}" {{ isset($barangTerpilih) && $barangTerpilih->id == $b->id ? 'selected' : '' }}>
                                        [{{ $b->kode_barang }}] {{ $b->nama }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($barangTerpilih)
        {{-- Section 2: Form & Riwayat (Two Columns) --}}
        <div class="col-md-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-primary text-white fw-bold">
                    Input Harga Baru
                </div>
                <div class="card-body">
                    <form action="{{ route('harga.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="barang_id" value="{{ $barangTerpilih->id }}">
                        
                        <div class="mb-3">
                            <label class="small fw-bold">Tanggal Mulai</label>
                            <input type="date" name="tgl_mulai" class="form-control @error('tgl_mulai') is-invalid @enderror" value="{{ old('tgl_mulai', date('Y-m-d')) }}" required>
                            @error('tgl_mulai')
                                <div class="invalid-feedback fw-bold">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="small fw-bold">Tanggal Selesai</label>
                            <input type="date" name="tgl_selesai" class="form-control @error('tgl_selesai') is-invalid @enderror" value="{{ old('tgl_selesai') }}" required>
                            @error('tgl_selesai')
                                <div class="invalid-feedback fw-bold">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="small fw-bold">Harga Jual (POS)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">Rp</span>
                                <input type="number" name="harga_pos" class="form-control form-control-lg fw-bold text-primary" placeholder="0" value="{{ old('harga_pos') }}" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="small fw-bold">Keterangan (Opsional)</label>
                            <textarea name="keterangan" class="form-control" rows="2" placeholder="Contoh: Promo Lebaran">{{ old('keterangan') }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2">
                            <i class="fas fa-check-circle"></i> Terapkan Harga Baru
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                    <span>Riwayat Harga: {{ $barangTerpilih->nama }}</span>
                    <span class="badge bg-light text-dark border">{{ $riwayatHarga->count() }} Data</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Periode</th>
                                    <th>Nominal Harga</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($riwayatHarga as $h)
                                @php 
                                    $today = date('Y-m-d');
                                    $isAktif = ($today >= $h->tgl_mulai && $today <= $h->tgl_selesai);
                                @endphp
                                <tr class="{{ $isAktif ? ($h->tgl_selesai == $today ? 'table-warning' : 'table-success') : '' }}">
                                    <td class="ps-3">
                                        <div class="fw-bold">{{ date('d M Y', strtotime($h->tgl_mulai)) }}</div>
                                        <div class="small text-muted">s/d {{ date('d M Y', strtotime($h->tgl_selesai)) }}</div>
                                    </td>
                                    <td>
                                        <span class="fs-5 fw-bold {{ $isAktif ? ($h->tgl_selesai == $today ? 'text-warning' : 'text-success') : 'text-dark' }}">
                                            Rp {{ number_format($h->harga_pos, 0, ',', '.') }}
                                        </span>
                                        @if($h->keterangan)
                                            <div class="small text-muted fst-italic">{{ $h->keterangan }}</div>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($isAktif)
                                            {{-- Jika tanggal selesai sudah di-set hari ini --}}
                                            @if($h->tgl_selesai == $today)
                                                <span class="badge rounded-pill bg-warning text-dark px-3">BERAKHIR HARI INI</span>
                                            @else
                                                <span class="badge rounded-pill bg-success px-3">AKTIF SEKARANG</span>
                                            @endif
                                        @elseif($today > $h->tgl_selesai)
                                            <span class="badge rounded-pill bg-light text-muted border">Expired</span>
                                        @else
                                            <span class="badge rounded-pill bg-info text-dark">Rencana</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        {{-- Logika Tombol Aksi --}}
                                        @if($isAktif && $h->tgl_selesai > $today)
                                            {{-- Nilai Diubah ke hari ini agar lolos dari validasi controller --}}
                                            <form action="{{ route('harga.update', $h->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin mengakhiri periode harga ini sekarang?');">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="tgl_selesai" value="{{ date('Y-m-d') }}">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Akhiri Periode">
                                                    Akhiri
                                                </button>
                                            </form>
                                        @elseif($isAktif && $h->tgl_selesai == $today)
                                            {{-- Jika sudah diklik, ganti tombol menjadi teks keterangan --}}
                                            <span class="small text-muted fst-italic text-secondary">Sudah Diakhiri</span>
                                        @elseif($h->tgl_mulai > $today)
                                            <button type="button" class="btn btn-sm btn-outline-warning" title="Edit Data" onclick="alert('Buka modal edit untuk ID: {{ $h->id }}')">
                                                Edit
                                            </button>
                                            <form action="{{ route('harga.destroy', $h->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus rencana harga ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus Data">
                                                    Hapus
                                                </button>
                                            </form>
                                        @else
                                            <span class="small text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        Belum ada riwayat harga untuk item ini.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @else
        {{-- Empty State --}}
        <div class="col-md-12">
            <div class="text-center py-5 bg-white shadow-sm rounded">
                <img src="https://illustrations.popsy.co/gray/box.svg" style="width: 150px;" class="mb-3" alt="Pilih Barang">
                <h5 class="text-muted">Pilih barang di atas untuk melihat atau mengubah harga.</h5>
            </div>
        </div>
        @endif
    </div>
</div>

<style>
    .table-success { --bs-table-bg: #e8f5e9; }
    .table-warning { --bs-table-bg: #fffde7; }
    .form-select-lg { font-size: 1.1rem; }
    .card { border-radius: 12px; overflow: hidden; }
</style>
</x-app-layout>