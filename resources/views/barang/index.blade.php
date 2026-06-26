<x-app-layout>
    <x-slot name="header">
        Master Barang
    </x-slot>

    <div class="container">
        <h3 class="mb-3">Data Barang</h3>

        <button type="button" class="btn mb-3 text-white" style="background-color: #d88656; border: none;" data-bs-toggle="modal" data-bs-target="#modalTambahBarang">
            + Tambah Barang
        </button>

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
                                    <div class="d-flex justify-content-center gap-1">
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
    </div>

    {{-- MODAL TAMBAH BARANG --}}
    <div class="modal fade" id="modalTambahBarang" tabindex="-1" aria-labelledby="modalTambahBarangLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header text-white" style="background-color: #d88656;">
                    <h5 class="modal-title" id="modalTambahBarangLabel">Tambah Barang Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
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
                                <input type="text" name="nama" class="form-control" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="fw-semibold">Satuan</label>
                                <input type="text" name="satuan" class="form-control">
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

                            <div class="col-md-6 mb-3" id="group-min-stock" style="display: none;">
                                <label class="fw-semibold text-danger">Minimum Stock (Batas Kritis)</label>
                                <input type="number" name="minimum_stock" id="minimum_stock" class="form-control" placeholder="Contoh: 10" min="0">
                            </div>
                        </div>

                        <hr>

                        <div id="group-harga" class="row">
                            <div class="col-md-6 mb-3">
                                <label class="fw-semibold">Harga B2B</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" name="harga_jual_b2b" class="form-control uang">
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="fw-semibold">Harga POS</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" name="harga_jual_pos" class="form-control uang">
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kembali</button>
                        <button type="submit" class="btn text-white" style="background-color: #d88656;">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
    input.uang {
        text-align: right;
    }
    .btn-close-white {
        filter: invert(1) grayscale(1) brightness(2);
    }
    </style>

    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const jenis = document.getElementById('jenis');
        const groupHarga = document.getElementById('group-harga');
        const groupMinStock = document.getElementById('group-min-stock');

        const b2b = document.querySelector('[name="harga_jual_b2b"]');
        const pos = document.querySelector('[name="harga_jual_pos"]');
        const minStockInput = document.getElementById('minimum_stock');
        const inputs = document.querySelectorAll('.uang');

        // FORMAT RUPIAH
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                let angka = this.value.replace(/\D/g, '');
                this.value = angka.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            });
        });

        // BERSIHKAN TITIK RUPIAH SEBELUM SUBMIT POP-UP
        document.getElementById("formTambahBarang").addEventListener("submit", function() {
            inputs.forEach(input => {
                input.value = input.value.replace(/\./g, '');
            });
        });

        // TOGGLE FORM DINAMIS (Harga & Minimum Stock)
        function toggleForm() {
            if (jenis.value === 'BAHAN_BAKU' || jenis.value === 'OPERATIONAL') {
                groupHarga.style.opacity = "0.3";
                b2b.disabled = true;
                pos.disabled = true;
                groupMinStock.style.display = "block";
            }
            else if (jenis.value === 'BARANG_JADI') {
                groupHarga.style.opacity = "1";
                b2b.disabled = false;
                pos.disabled = false;
                groupMinStock.style.display = "none";
                minStockInput.value = ''; 
            } else {
                groupMinStock.style.display = "none";
                minStockInput.value = '';
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

            fetch('/barang/generate-kode/' + kategoriId)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('kode_barang').value = data.kode_barang;
                });
        });

        if (kategori.value != '') {
            kategori.dispatchEvent(new Event('change'));
        }
    });
    </script>
</x-app-layout>