<x-app-layout>
    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            {{-- Tombol Kembali --}}
            <div class="mb-4">
                <a href="{{ route('penggajian.index') }}" class="text-sm text-gray-600 hover:text-gray-900 inline-flex items-center gap-1">
                    ← Kembali ke Daftar Periode
                </a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                {{-- Header Halaman --}}
                <div class="flex justify-between items-center mb-6 border-b pb-4">
                    <div>
                        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                            Rincian Gaji Bersih Karyawan — Periode {{ $periode }}
                        </h2>
                        <p class="text-xs text-gray-500 mt-1">
                            Daftar *Take Home Pay* (THP) akhir karyawan setelah kalkulasi bonus dan potongan.
                        </p>
                    </div>

                    {{-- Status Periode --}}
                    <div>
                        @if(strtolower($status) == 'draft')
                        <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800 border border-gray-200">Draft</span>
                        @elseif(strtolower($status) == 'pending_approval')
                        <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-amber-100 text-amber-800 border border-amber-200">Pending Approval</span>
                        @elseif(strtolower($status) == 'approved')
                        <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 border border-green-200">Approved</span>
                        @elseif(strtolower($status) == 'posted')
                        <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 border border-blue-200">Posted</span>
                        @endif
                    </div>
                </div>

                {{-- Alert Flash Session --}}
                @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 text-sm">
                    {{ session('success') }}
                </div>
                @endif
                @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 text-sm">
                    {{ session('error') }}
                </div>
                @endif

                {{-- Tabel Ringkas Gaji Bersih --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500 border">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                            <tr>
                                <th class="px-6 py-3 border-r text-center w-16">No</th>
                                <th class="px-6 py-3 border-r">Nama Karyawan</th>
                                <th class="px-6 py-3 border-r text-right bg-slate-50 text-slate-900">Total Gaji Bersih (THP)</th>
                                @if(strtolower($status) == 'draft')
                                <th class="px-6 py-3 text-center w-24">Aksi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payrolls as $index => $p)
                            <tr class="bg-white border-b hover:bg-gray-50 text-gray-900">
                                <td class="px-6 py-4 border-r text-center text-gray-500">{{ $index + 1 }}</td>
                                <td class="px-6 py-4 border-r font-medium text-gray-900">
                                    {{ $p->karyawan->nama_karyawan ?? 'Karyawan Tidak Ditemukan' }}
                                </td>
                                <td class="px-6 py-4 border-r text-right font-mono font-semibold bg-slate-50/50 text-gray-900">
                                    Rp {{ number_format($p->total_gaji_bersih, 0, ',', '.') }}
                                </td>

                                {{-- Aksi Hapus (Hanya muncul jika berstatus draft) --}}
                                @if(strtolower($status) == 'draft')
                                <td class="px-6 py-4 text-center">
                                    <form action="{{ route('penggajian.destroy', $p->id) }}" method="POST" onsubmit="return confirm('Keluarkan karyawan ini dari periode penggajian?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs text-red-600 hover:text-red-800 font-medium hover:underline">
                                            Hapus
                                        </button>
                                    </form>
                                </td>
                                @endif
                            </tr>
                            @empty
                            <tr>
                                <td colspan="{{ strtolower($status) == 'draft' ? 4 : 3 }}" class="px-6 py-10 text-center text-gray-400">
                                    Belum ada data rincian karyawan untuk periode ini.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>

                        {{-- Opsional: Menampilkan Total Akumulasi di paling bawah tabel --}}
                        @if($payrolls->isNotEmpty())
                        <tfoot class="bg-gray-50 font-semibold text-gray-900 border-t-2">
                            <tr>
                                <td colspan="2" class="px-6 py-4 text-right border-r">Total Anggaran Periode Ini:</td>
                                <td class="px-6 py-4 text-right font-mono text-base text-blue-600 bg-blue-50/20">
                                    Rp {{ number_format($payrolls->sum('total_gaji_bersih'), 0, ',', '.') }}
                                </td>
                                @if(strtolower($status) == 'draft')
                                <td class="bg-gray-50"></td>
                                @endif
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>