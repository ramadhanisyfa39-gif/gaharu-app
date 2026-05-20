<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Daftar Riwayat Penggajian Karyawan
                    </h2>
                    <a href="{{ route('penggajian.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md shadow-sm text-sm">
                        + Input Gaji Baru
                    </a>
                </div>

                @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
                @endif

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500 border">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                            <tr>
                                <th class="px-6 py-3 border-r">No</th>
                                <th class="px-6 py-3 border-r">Periode</th>
                                <th class="px-6 py-3 border-r">Nama Karyawan</th>
                                <th class="px-6 py-3 border-r">Gaji Pokok</th>
                                <th class="px-6 py-3 border-r">Total Gaji Bersih</th>
                                <th class="px-6 py-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payrolls as $index => $p)
                            <tr class="bg-white border-b hover:bg-gray-50">
                                <td class="px-6 py-4 border-r">{{ $index + 1 }}</td>
                                {{-- Menggunakan periode_bulan_tahun sesuai database --}}
                                <td class="px-6 py-4 border-r font-medium text-gray-900">
                                    {{ $p->periode_bulan_tahun }}
                                </td>
                                {{-- Mengambil nama dari relasi karyawan --}}
                                <td class="px-6 py-4 border-r">
                                    {{ $p->karyawan->nama_karyawan }}
                                </td>
                                <td class="px-6 py-4 border-r text-right">
                                    Rp {{ number_format($p->gaji_pokok, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 border-r text-right font-bold text-blue-600">
                                    Rp {{ number_format($p->total_gaji_bersih, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-center flex justify-center gap-2">
                                    {{-- Tombol Lihat/Cetak Slip --}}
                                    <a href="{{ route('penggajian.show', $p->id) }}"
                                        class="inline-flex items-center gap-2 bg-slate-800 hover:bg-slate-900 text-black px-4 py-2 rounded-lg text-xs font-medium transition-all shadow-md active:scale-95">
                                        <!-- Ikon Printer SVG Murni -->
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="6 9 6 2 18 2 18 9"></polyline>
                                            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                                            <rect x="6" y="14" width="12" height="8"></rect>
                                        </svg>
                                        <span>Cetak Slip</span>
                                    </a>

                                    {{-- Tombol Hapus --}}
                                    <form action="{{ route('penggajian.destroy', $p->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus data ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center gap-2 bg-white border border-red-200 text-red-600 hover:bg-red-50 px-4 py-2 rounded-lg text-xs font-medium transition-all">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                            </svg>
                                            Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-gray-400">
                                    Belum ada data penggajian yang diinput.
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