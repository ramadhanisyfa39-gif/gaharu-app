<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Detail Jurnal: {{ $jurnal->no_ref }}
            </h2>
            <a href="{{ route('jurnal.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded text-sm shadow-sm hover:bg-gray-600 transition">
                Kembali
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                <!-- Informasi Header Jurnal -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 border-b pb-6">
                    <div>
                        <p class="text-sm text-gray-500 uppercase font-bold">Tanggal Transaksi</p>
                        <p class="text-lg font-semibold">{{ \Carbon\Carbon::parse($jurnal->tanggal)->format('d F Y') }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 uppercase font-bold">Nomor Referensi</p>
                        <p class="text-lg font-semibold font-mono text-indigo-600">{{ $jurnal->no_ref }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 uppercase font-bold">Deskripsi</p>
                        <p class="text-lg font-semibold">{{ $jurnal->deskripsi }}</p>
                    </div>
                </div>

                <!-- Tabel Item Jurnal (Rincian Akun) -->
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-gray-50 text-gray-600 uppercase text-xs">
                                <th class="p-4 border-b text-left">Kode Akun</th>
                                <th class="p-4 border-b text-left">Nama Akun (COA)</th>
                                <th class="p-4 border-b text-right">Debit</th>
                                <th class="p-4 border-b text-right">Kredit</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($jurnal->details as $item)
                            <tr>
                                <td class="p-4 font-mono text-sm">{{ $item->coa?->kode ?? 'N/A' }}</td>
                                <td class="p-4 text-sm">{{ $item->coa?->nama ?? 'Akun Tidak Ditemukan' }}</td>
                                <td class="p-4 text-right text-sm">
                                    {{ $item->debit > 0 ? 'Rp ' . number_format($item->debit, 0, ',', '.') : '-' }}
                                </td>
                                <td class="p-4 text-right text-sm">
                                    {{ $item->kredit > 0 ? 'Rp ' . number_format($item->kredit, 0, ',', '.') : '-' }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 font-bold">
                            <tr>
                                <td colspan="2" class="p-4 text-right">TOTAL</td>
                                <td class="p-4 text-right text-indigo-600">
                                    Rp {{ number_format($jurnal->details->sum('debit'), 0, ',', '.') }}
                                </td>
                                <td class="p-4 text-right text-indigo-600">
                                    Rp {{ number_format($jurnal->details->sum('kredit'), 0, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Log Informasi -->
                <div class="mt-8 pt-4 border-t text-xs text-gray-400 italic">
                    Sumber: {{ strtoupper($jurnal->source_type) }} | Dicatat pada: {{ $jurnal->created_at ?? 'N/A' }}
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <!-- thead & tbody -->
                    </table>
                </div>

                <!-- LETAKKAN DI SINI (Setelah tabel) -->
                <div class="mt-8 flex justify-end gap-3 border-t pt-6">
                    <a href="{{ route('jurnal.index') }}"
                        class="px-6 py-2 bg-gray-200 text-gray-700 rounded-md font-semibold hover:bg-gray-300 transition">
                        Tutup Detail
                    </a>

                </div>
            </div>
        </div>
</x-app-layout>