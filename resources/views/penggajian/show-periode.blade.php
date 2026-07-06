<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6 border-b pb-4">
                    <div>
                        <a href="{{ route('penggajian.index') }}" class="text-sm text-blue-600 hover:underline flex items-center gap-1 mb-1">
                            ← Kembali ke Ringkasan Utama
                        </a>
                        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                            Detail Karyawan & Gaji: Periode {{ $periode }}
                        </h2>
                    </div>

                    @if($currentStatus == 'draft' || $currentStatus == 'waiting approval')
                    <a href="{{ route('penggajian.create', ['target_periode' => $periode]) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow-sm text-sm font-medium transition-all">
                        + Input Gaji Karyawan Baru
                    </a>
                    @else
                    <span class="bg-gray-100 text-gray-400 border px-4 py-2 rounded-lg text-sm font-medium cursor-not-allowed">
                        Periode Terkunci (Approved)
                    </span>
                    @endif
                </div>

                @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4 text-sm">
                    {{ session('success') }}
                </div>
                @endif

                <div class="overflow-x-auto border border-gray-200 rounded-xl shadow-sm">
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-100 border-b">
                            <tr>
                                <th class="px-6 py-3 text-center w-16">No</th>
                                <th class="px-6 py-3">Nama Karyawan</th>
                                <th class="px-6 py-3 text-right">Gaji Pokok</th>
                                <th class="px-6 py-3 text-right">Gaji Bersih</th>
                                <th class="px-6 py-3 text-center w-40">Aksi Perorangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse($payrolls as $index => $payroll)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 text-center font-medium text-gray-900">{{ $index + 1 }}</td>
                                <td class="px-6 py-4 font-semibold text-gray-800">{{ $payroll->karyawan->nama_karyawan }}</td>
                                <td class="px-6 py-4 text-right text-gray-700">Rp {{ number_format($payroll->gaji_pokok, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-right font-bold text-blue-600">Rp {{ number_format($payroll->total_gaji_bersih, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex justify-center gap-2">
                                        <a href="{{ route('penggajian.show', $payroll->id) }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-2.5 py-1.5 rounded-lg text-xs font-medium transition-all">
                                            Lihat Slip
                                        </a>

                                        @if($currentStatus == 'draft' || $currentStatus == 'waiting approval')
                                        <a href="{{ route('penggajian.edit', $payroll->id) }}" class="bg-amber-50 hover:bg-amber-100 text-amber-700 px-2.5 py-1.5 rounded-lg text-xs font-medium transition-all border border-amber-200">
                                            Edit
                                        </a>

                                        @if($currentStatus == 'draft')
                                        <form action="{{ route('penggajian.destroy', $payroll->id) }}" method="POST" onsubmit="return confirm('Hapus data karyawan ini?')" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="bg-red-50 hover:bg-red-100 text-red-600 px-2.5 py-1.5 rounded-lg text-xs font-medium transition-all">
                                                Hapus
                                            </button>
                                        </form>
                                        @endif
                                        @else
                                        <span class="text-xs text-gray-400 italic">Terkunci</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-400">
                                    Belum ada karyawan yang dimasukkan pada periode ini. Silakan klik "+ Input Gaji Karyawan Baru" di atas.
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