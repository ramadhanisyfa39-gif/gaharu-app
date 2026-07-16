<x-app-layout>
    <x-slot name="header">
        Master Barang
    </x-slot>

    <div class="container">
        <h3 class="mb-3">Data Barang</h3>

        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <button type="button" class="btn text-white" style="background-color: #d88656; border: none;" data-bs-toggle="modal" data-bs-target="#modalTambahBarang">
                + Tambah Barang
            </button>

            <form action="{{ route('barang.index') }}" method="GET" class="d-flex gap-2">
                <select name="kategori_id" class="form-select form-select-sm" style="width: 180px;">
                    <option value="">-- Semua Kategori --</option>
                    @foreach($kategori as $k)
                        <option value="{{ $k->id }}" {{ request('kategori_id') == $k->id ? 'selected' : '' }}>{{ $k->nama }}</option>
                    @endforeach
                </select>
                <div class="input-group input-group-sm" style="width: 220px;">
                    <input type="text" name="search" class="form-control" placeholder="Cari nama/kode..." value="{{ request('search') }}">
                    <button type="submit" class="btn text-white" style="background-color: #d88656; border: none;">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
                @if(request('kategori_id') || request('search'))
                    <a href="{{ route('barang.index') }}" class="btn btn-sm btn-secondary">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </a>
                @endif
            </form>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill"></i>
                {{ session('error') }}
            </div>
        @endif

        <div class="card shadow-sm">
            <div class="card-body">
                <table class="table table-bordered table-hover align-middle text-center">
                    <thead style="background-color: #d88656; color: white;">
                        <tr>
                            <th style="background-color: #d88656; color: white;">Kode</th>
                            <th style="background-color: #d88656; color: white;">Nama</th>
                            <th style="background-color: #d88656; color: white;">Kategori</th>
                            <th style="background-color: #d88656; color: white;">Satuan</th>
                            <th style="background-color: #d88656; color: white;">Jenis</th>
                            <th style="background-color: #d88656; color: white;">Min. Stock</th>
                            <th style="background-color: #d88656; color: white;">Min. Order</th>
                            <th style="background-color: #d88656; color: white;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data as $d)
                            <tr class="{{ !$d->is_active ? 'table-secondary' : '' }}">
                                <td>{{ $d->kode_barang }}</td>
                                <td class="text-start fw-semibold">
                                    {{ $d->nama }} 
                                    @if(!$d->is_active) <span class="badge bg-secondary ms-2">Non-Aktif</span> @endif
                                </td>
                                <td>{{ $d->kategori->nama ?? '-' }}</td>
                                <td>{{ $d->satuan }}</td>
                                <td>
                                    @if($d->is_bahan_baku)
                                        <span class="badge bg-primary-subtle text-primary px-3 py-2">Bahan Baku</span>
                                    @elseif($d->is_barang_jadi)
                                        <span class="badge bg-success-subtle text-success px-3 py-2">Barang Jadi</span>
                                    @elseif($d->is_operational)
                                        <span class="badge bg-warning-subtle text-dark px-3 py-2">Operational</span>
                                    @endif
                                </td>
                                {{-- FIX: Menghilangkan tag penutup td ganda --}}
                                <td>
                                    @if($d->minimum_stock !== null)
                                        <span class="fw-bold text-dark">{{ number_format($d->minimum_stock) }}</span> 
                                        <small class="text-muted">{{ $d->satuan }}</small>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="fw-bold text-dark">{{ number_format($d->minimum_order ?? 1) }}</span> 
                                    <small class="text-muted">{{ $d->satuan }}</small>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-center gap-1">
                                        <a href="{{ route('barang.show', $d->id) }}" class="btn btn-info btn-sm text-white">
                                            Detil
                                        </a>

                                        <form action="{{ route('barang.toggle', $d->id) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-{{ $d->is_active ? 'secondary' : 'success' }} btn-sm" title="Ubah Status">
                                                {{ $d->is_active ? 'Non-Aktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>

                                        <a href="{{ route('barang.edit',$d->id) }}" class="btn btn-warning btn-sm">
                                            Edit
                                        </a>

                                        <form action="{{ route('barang.destroy',$d->id) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button onclick="return confirm('Yakin hapus data ini?')" class="btn btn-danger btn-sm">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">
                                    Data belum tersedia
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3">
            {{ $data->links() }}
        </div>
    </div>

    {{-- MODAL TAMBAH BARANG --}}
    <div class="modal fade" id="modalTambahBarang" tabindex="-1" aria-labelledby="modalTambahBarangLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header text-white" style="background-color: #d88656;">
                    <h5 class="modal-title" id="modalTambahBarangLabel">Tambah Barang Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <form action="{{ route('barang.store') }}" method="POST" id="formTambahBarang">
                    @csrf
                    <div class="modal-body text-start">
                        
                        <div class="mb-3">
                            <label class="fw-semibold">Kategori</label>
                            <select name="kategori_id" id="kategori_id" class="form-control" required>
                                <option value="">-- Pilih Kategori --</option>
                                @foreach($kategori as $k)
                                    <option value="{{ $k->id }}">{{ $k->nama }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="fw-semibold">Kode Barang</label>
                                <input type="text" name="kode_barang" id="kode_barang" class="form-control" readonly required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="fw-semibold">Nama Barang</label>
                                <input type="text" name="nama" id="nama_barang" class="form-control" required autocomplete="off">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="fw-semibold">Satuan</label>
                                <input type="text" name="satuan" class="form-control" required placeholder="Contoh: kg, pcs, liter">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="fw-semibold">Jenis Barang</label>
                                <select name="jenis_utama" id="jenis" class="form-control" required>
                                    <option value="">-- Pilih Jenis --</option>
                                    <option value="BAHAN_BAKU">Bahan Baku</option>
                                    <option value="BARANG_JADI">Barang Jadi</option>
                                    <option value="OPERATIONAL">Operational</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3" id="group-min-stock" style="display: block;">
                                <label class="fw-semibold text-danger">Minimum Stock (Batas Kritis)</label>
                                <input type="number" name="minimum_stock" id="minimum_stock" class="form-control" placeholder="Contoh: 10" min="0">
                            </div>

                            <div class="col-md-6 mb-3" id="group-tipe-penjualan">
                                <label class="fw-semibold text-gray-700">Tipe Penjualan</label>
                                <select name="tipe_penjualan" id="tipe_penjualan" class="form-control">
                                    <option value="">-- Pilih Tipe Penjualan --</option>
                                    @php
                                        $userRole = auth()->user()->role->nama ?? '';
                                        $options = [];
                                        if (in_array($userRole, ['Super Admin', 'Administrator'])) {
                                            $options = ['POS Gaharu', 'POS Kejingga', 'B2B'];
                                        } elseif ($userRole === 'Kepala Outlet Gaharu') {
                                            $options = ['POS Gaharu', 'B2B'];
                                        } elseif ($userRole === 'Kepala Outlet Kejingga') {
                                            $options = ['POS Kejingga'];
                                        } elseif ($userRole === 'Kepala Gudang') {
                                            $options = ['B2B'];
                                        }
                                    @endphp
                                    @foreach($options as $opt)
                                        <option value="{{ $opt }}">{{ $opt }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="fw-semibold text-primary">Minimum Order (Batas Order)</label>
                                <input type="number" name="minimum_order" id="minimum_order" class="form-control" placeholder="Default: 1" min="1" value="1" step="0.01">
                            </div>
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kembali</button>
                        <button type="submit" class="btn text-white" style="background-color: #d88656">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const jenis = document.getElementById('jenis');
        const groupMinStock = document.getElementById('group-min-stock');
        const minStockInput = document.getElementById('minimum_stock');
        const groupTipePenjualan = document.getElementById('group-tipe-penjualan');
        const tipePenjualanSelect = document.getElementById('tipe_penjualan');

        function toggleForm() {
            if (jenis.value === "BAHAN_BAKU") {
                groupMinStock.style.display = "block";
            } else {
                groupMinStock.style.display = "none";
                minStockInput.value = "";
            }

            if (jenis.value === "BARANG_JADI") {
                groupTipePenjualan.style.display = "block";
                tipePenjualanSelect.setAttribute('required', 'required');
            } else {
                groupTipePenjualan.style.display = "none";
                tipePenjualanSelect.removeAttribute('required');
                tipePenjualanSelect.value = "";
            }
        }

        jenis.addEventListener('change', toggleForm);
        toggleForm();

        // AUTO GENERATE KODE BARANG BERDASARKAN KATEGORI
        const kategori = document.getElementById('kategori_id');
        kategori.addEventListener('change', function () {
            let kategoriId = this.value;
            if (kategoriId == '') {
                document.getElementById('kode_barang').value = '';
                return;
            }

            fetch("{{ route('barang.generate-kode', ':kategori') }}".replace(':kategori', kategoriId))
                .then(response => response.json())
                .then(data => {
                    document.getElementById('kode_barang').value = data.kode_barang;
                });
        });

        if (kategori.value != '') {
            kategori.dispatchEvent(new Event('change'));
        }

        // VALIDASI NAMA BARANG DUPLIKAT DENGAN AJAX (Lebih Tangguh)
        const form = document.getElementById('formTambahBarang');
        let bypassCheck = false;

        form.addEventListener('submit', function (e) {
            if (bypassCheck) return;
            e.preventDefault(); // Block submit pertama kali

            try {
                const namaInput = document.getElementById('nama_barang');
                if (!namaInput) {
                    bypassFormSubmit();
                    return;
                }

                const namaVal = namaInput.value.trim();
                if (!namaVal) {
                    bypassFormSubmit();
                    return;
                }

                fetch("{{ route('barang.check-nama') }}?nama=" + encodeURIComponent(namaVal))
                    .then(response => {
                        if (!response.ok) {
                            throw new Error("HTTP error " + response.status);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.exists) {
                            const confirmSubmit = confirm("Nama Barang ini sudah terdaftar, apakah tetap ingin diinput?");
                            if (confirmSubmit) {
                                bypassFormSubmit();
                            }
                        } else {
                            bypassFormSubmit();
                        }
                    })
                    .catch(err => {
                        console.error("Duplicate name check failed:", err);
                        bypassFormSubmit();
                    });
            } catch (err) {
                console.error("Error in duplicate check handler:", err);
                bypassFormSubmit();
            }

            function bypassFormSubmit() {
                bypassCheck = true;
                form.submit();
            }
        });
    });
    </script>
</x-app-layout>