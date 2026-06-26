<x-app-layout>
    <x-slot name="header">
        Master Kategori Barang
    </x-slot>

    <div class="container mt-4">
        <h2 class="mb-3 fw-semibold" style="color: #2d3748; font-size: 1.5rem;">Data Kategori</h2>

        <button type="button" class="btn mb-4 text-white custom-btn-tambah" data-bs-toggle="modal" data-bs-target="#modalTambahKategori">
            + Tambah Kategori
        </button>

        @if(session('success'))
    <div class="modal fade" id="modalSuksesCentang" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0" style="border-radius: 20px; padding: 24px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
                <div class="modal-body text-center p-0">
                    
                    <div class="mx-auto mb-3 d-flex align-items-center justify-content-center animate-scale-up" 
                         style="width: 64px; height: 64px; background-color: #e6f7ed; border-radius: 50%;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="#10b981" style="width: 28px; height: 28px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                    </div>

                    <h5 class="fw-bold mb-1" style="color: #1f2937; font-size: 18px;">Berhasil!</h5>
                    <p class="text-secondary small mb-4" style="font-size: 14px;">{{ session('success') }}</p>
                    
                    <button type="button" class="btn w-100 text-white" data-bs-dismiss="modal" 
                            style="background-color: #d88656; border-radius: 10px; padding: 10px; font-weight: 500; font-size: 14px; border: none; transition: background 0.2s;">
                        Selesai
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var modalElement = document.getElementById('modalSuksesCentang');
            var instanceModal = new bootstrap.Modal(modalElement);
            
            // Tampilkan pop-up langsung saat halaman selesai dimuat
            instanceModal.show();
            
            // Otomatis menutup sendiri secara smooth setelah 3 detik jika tidak diklik
            setTimeout(function() {
                instanceModal.hide();
            }, 3000);
        });
    </script>

    <style>
        @keyframes scaleUp {
            0% { transform: scale(0.6); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }
        .animate-scale-up {
            animation: scaleUp 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
    </style>
@endif

        <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
            <div class="card-body p-0">
                <table class="table align-middle mb-0 custom-table">
                    <thead>
                        <tr>
                            <th width="80" class="ps-4">No</th>
                            <th>Nama Kategori</th>
                            <th width="180" class="text-center pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data as $d)
                        <tr>
                            <td class="ps-4 text-secondary">{{ $loop->iteration }}</td>
                            <td class="fw-medium" style="color: #4a5568;">{{ $d->nama }}</td>
                            <td class="text-center pe-4">
                                <button type="button" class="btn btn-sm btn-edit me-1" data-bs-toggle="modal" data-bs-target="#modalEditKategori{{ $d->id }}">
    Edit
</button>

                                <form action="{{ route('kategori.destroy', $d->id) }}" method="POST" style="display:inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-hapus" onclick="return confirm('Yakin hapus kategori ini?')">
                                        Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center py-4 text-secondary">
                                Data belum tersedia.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade animate-fade-in" id="modalTambahKategori" tabindex="-1" aria-labelledby="modalTambahKategoriLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content custom-modal">
                <div class="modal-header border-0 pb-0 pt-4 px-4">
                    <h5 class="modal-title fw-bold" id="modalTambahKategoriLabel" style="color: #2d3748;">Tambah Kategori Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <form action="{{ route('kategori.store') }}" method="POST">
                    @csrf
                    <div class="modal-body px-4 pt-3 pb-4">
                        <div class="mb-2">
                            <label for="nama" class="form-label custom-label">Nama Kategori</label>
                            <input type="text" name="nama" id="nama" class="form-control custom-input" placeholder="Contoh: POWDER, SYRUP, COFFEE" required autocomplete="off">
                        </div>
                    </div>
                    <div class="modal-footer border-0 px-4 pb-4 pt-0 d-flex justify-content-end gap-2">
                        <button type="button" class="btn custom-btn-batal" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn text-white custom-btn-simpan">Simpan Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @foreach($data as $d)
    <div class="modal fade animate-fade-in" id="modalEditKategori{{ $d->id }}" tabindex="-1" aria-labelledby="modalEditKategoriLabel{{ $d->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content custom-modal">
                <div class="modal-header border-0 pb-0 pt-4 px-4">
                    <h5 class="modal-title fw-bold" id="modalEditKategoriLabel{{ $d->id }}" style="color: #2d3748;">Ubah Kategori</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <form action="{{ route('kategori.update', $d->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body px-4 pt-3 pb-4">
                        <div class="mb-2">
                            <label for="nama{{ $d->id }}" class="form-label custom-label">Nama Kategori</label>
                            <input type="text" name="nama" id="nama{{ $d->id }}" class="form-control custom-input" value="{{ $d->nama }}" required autocomplete="off">
                        </div>
                    </div>
                    <div class="modal-footer border-0 px-4 pb-4 pt-0 d-flex justify-content-end gap-2">
                        <button type="button" class="btn custom-btn-batal" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn text-white custom-btn-simpan">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endforeach

    <style>
        /* Gaya Tombol Utama */
        .custom-btn-tambah {
            background-color: #d88656;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.2s ease;
            border: none;
        }
        .custom-btn-tambah:hover {
            background-color: #d88656;
            transform: translateY(-1px);
        }

        /* Gaya Tabel Kustom */
        .custom-table thead {
            background-color: #d88656 !important; /* Cokelat gelap Gaharu */
        }
        .custom-table th {
            color: #ffffff !important;
            font-weight: 500;
            padding: 14px 12px;
            font-size: 14px;
            border: none;
        }
        .custom-table td {
            padding: 14px 12px;
            font-size: 14px;
            border-bottom: 1px solid #f1f5f9;
        }

        /* Tombol Aksi di Tabel */
        .btn-edit {
            background-color: #ffb800;
            color: #2d3748;
            font-weight: 500;
            border-radius: 6px;
            padding: 5px 14px;
            border: none;
        }
        .btn-edit:hover {
            background-color: #e0a200;
            color: #2d3748;
        }
        .btn-hapus {
            background-color: #e53e3e;
            color: white;
            font-weight: 500;
            border-radius: 6px;
            padding: 5px 14px;
            border: none;
        }
        .btn-hapus:hover {
            background-color: #c53030;
            color: white;
        }

        /* Pop-up Modal Minimalis */
        .custom-modal {
            border-radius: 16px !important;
            border: none !important;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
        }
        .custom-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #718096;
            font-weight: 700;
        }
        .custom-input {
            border-radius: 8px !important;
            padding: 12px !important;
            border: 1px solid #e2e8f0 !important;
            font-size: 14px !important;
        }
        .custom-input:focus {
            border-color: #d88656 !important;
            box-shadow: 0 0 0 3px rgba(189, 122, 76, 0.15) !important;
        }
        .custom-btn-batal {
            background-color: #f7fafc;
            border: 1px solid #e2e8f0;
            color: #4a5568;
            border-radius: 8px;
            padding: 9px 18px;
            font-size: 14px;
        }
        .custom-btn-batal:hover {
            background-color: #edf2f7;
        }
        .custom-btn-simpan {
            background-color: #d88656;
            border-radius: 8px;
            padding: 9px 18px;
            font-size: 14px;
            font-weight: 500;
            border: none;
        }
        .custom-btn-simpan:hover {
            background-color: #d88656;
        }
    </style>
</x-app-layout>