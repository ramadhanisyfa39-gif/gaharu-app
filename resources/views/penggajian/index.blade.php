<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Mengubah overflow-hidden menjadi overflow-visible agar dropdown tidak terpotong --}}
            <div class="bg-white overflow-visible shadow-sm sm:rounded-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Daftar Periode Penggajian Karyawan
                    </h2>
                    {{-- UPDATE: Mengubah link ke route index.create untuk inisiasi otomatis --}}
                    <a href="{{ route('penggajian.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md shadow-sm text-sm font-medium">
                        + Buat Periode Baru
                    </a>
                </div>

                @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
                @endif

                @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    {{ session('error') }}
                </div>
                @endif

                {{-- Mengubah overflow-x-auto menjadi min-w-full pada table langsung agar tidak mengunci koordinat z-index --}}
                <div class="w-full">
                    <table class="w-full text-sm text-left text-gray-500 border style-table">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                            <tr>
                                <th class="px-6 py-3 border-r text-center w-16">No</th>
                                <th class="px-6 py-3 border-r">Periode Bulan & Tahun</th>
                                <th class="px-6 py-3 border-r text-center">Jumlah Karyawan</th>
                                <th class="px-6 py-3 border-r text-right">Total Anggaran Gaji</th>
                                <th class="px-6 py-3 border-r text-center">Status</th>
                                <th class="px-6 py-3 text-center w-32">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($groupedPayrolls as $index => $p)
                            <tr class="bg-white border-b hover:bg-gray-50">
                                <td class="px-6 py-4 border-r text-center">{{ $index + 1 }}</td>
                                <td class="px-6 py-4 border-r font-semibold text-gray-900">
                                    {{ $p->periode_bulan_tahun }}
                                </td>
                                <td class="px-6 py-4 border-r text-center">
                                    {{ $p->total_karyawan }} Orang
                                </td>
                                <td class="px-6 py-4 border-r text-right font-medium text-gray-900">
                                    Rp {{ number_format($p->total_nominal, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 border-r text-center">
                                    @if(strtolower($p->status) == 'draft')
                                    <span class="inline-block px-2.5 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800 border border-gray-200">Draft</span>
                                    @elseif(strtolower($p->status) == 'pending_approval')
                                    <span class="inline-block px-2.5 py-1 text-xs font-semibold rounded-full bg-amber-100 text-amber-800 border border-amber-200">Pending Approval</span>
                                    @elseif(strtolower($p->status) == 'approved')
                                    <span class="inline-block px-2.5 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 border border-green-200">Approved</span>
                                    @elseif(strtolower($p->status) == 'posted')
                                    <span class="inline-block px-2.5 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 border border-blue-200">Posted</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center">
                                    {{-- Menggunakan komponen bawaan HTML <details> yang jauh lebih stabil --}}
                                    <details class="relative inline-block text-left dropdown-details">
                                        {{-- Penampilan Tombol Utama (Summary) --}}
                                        <summary class="list-none cursor-pointer outline-none inline-flex justify-center items-center w-8 h-8 text-gray-500 hover:text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-full focus:outline-none transition-all">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                <circle cx="12" cy="12" r="1"></circle>
                                                <circle cx="12" cy="5" r="1"></circle>
                                                <circle cx="12" cy="19" r="1"></circle>
                                            </svg>
                                        </summary>

                                        {{-- Kotak Pilihan Dropdown --}}
                                        <div class="absolute right-0 mt-2 w-44 bg-white rounded-md shadow-xl border border-gray-200 z-50 divide-y divide-gray-100 origin-top-right">

                                            {{-- 1. LINK DETAIL KARYAWAN --}}
                                            <div class="py-1">
                                                <a href="{{ route('penggajian.periode', $p->periode_bulan_tahun) }}"
                                                    class="flex items-center px-4 py-2 text-xs text-gray-700 hover:bg-gray-100 text-left font-medium">
                                                    👁️ Detail Karyawan
                                                </a>
                                            </div>

                                            {{-- 2. TOMBOL ALUR APPROVAL --}}
                                            @if(strtolower($p->status) == 'draft' || strtolower($p->status) == 'pending_approval')
                                            <div class="py-1">
                                                @if(strtolower($p->status) == 'draft')
                                                <form action="{{ route('penggajian.submit', $p->periode_bulan_tahun) }}" method="POST" class="w-full m-0 p-0">
                                                    @csrf
                                                    <button type="submit" class="w-full text-left px-4 py-2 text-xs text-amber-600 hover:bg-amber-50 font-medium">
                                                        🚀 Minta Approval
                                                    </button>
                                                </form>
                                                @endif

                                                @if(strtolower($p->status) == 'pending_approval')
                                                <form action="{{ route('penggajian.approve', $p->periode_bulan_tahun) }}" method="POST" class="w-full m-0 p-0">
                                                    @csrf
                                                    <button type="submit" class="w-full text-left px-4 py-2 text-xs text-green-600 hover:bg-green-50 font-medium">
                                                        ✅ Approve Gaji
                                                    </button>
                                                </form>
                                                @endif
                                            </div>
                                            @endif

                                            {{-- 3. TOMBOL JURNAL UTAMA --}}
                                            <div class="py-1">
                                                @if(strtolower($p->status) == 'approved')
                                                <form action="{{ route('penggajian.journal', $p->periode_bulan_tahun) }}" method="POST" class="w-full m-0 p-0">
                                                    @csrf
                                                    <button type="submit" class="w-full text-left px-4 py-2 text-xs text-blue-600 hover:bg-blue-50 font-medium">
                                                        📊 Kirim Jurnal
                                                    </button>
                                                </form>
                                                @else
                                                <button type="button" disabled class="w-full text-left px-4 py-2 text-xs text-gray-300 cursor-not-allowed font-medium">
                                                    📊 Kirim Jurnal
                                                </button>
                                                @endif
                                            </div>

                                        </div>
                                    </details>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-gray-400">
                                    Belum ada periode penggajian yang dibuat.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Script Tambahan untuk menutup dropdown otomatis jika klik di luar area --}}
    <script>
        document.addEventListener('click', function(e) {
            document.querySelectorAll('.dropdown-details').forEach(function(el) {
                if (!el.contains(e.target)) {
                    el.removeAttribute('open');
                }
            });
        });
    </script>
</x-app-layout>