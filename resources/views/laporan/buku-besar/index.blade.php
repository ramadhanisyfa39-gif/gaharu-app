<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Laporan Buku Besar</h2>
    </x-slot>

    <div class="py-12" x-data="{ activeAccordion: null }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6 no-print">
                <form action="{{ route('laporan.buku-besar.index') }}" method="GET" class="flex flex-wrap items-end gap-4">
                    <div class="w-full md:w-1/4">
                        <label class="block text-sm font-medium text-gray-700">Bulan</label>
                        <select name="bulan" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            @foreach(range(1, 12) as $m)
                            <option value="{{ sprintf('%02d', $m) }}" {{ $bulan == sprintf('%02d', $m) ? 'selected' : '' }}>
                                {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-full md:w-1/4">
                        <label class="block text-sm font-medium text-gray-700">Tahun</label>
                        <select name="tahun" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            @for($y = date('Y'); $y >= 2020; $y--)
                            <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <button type="submit" class="bg-indigo-600 text-black px-6 py-2 rounded-md hover:bg-indigo-700 font-medium shadow-sm transition">Tampilkan Laporan</button>
                    <a href="{{ route('laporan.buku-besar.index', array_merge(request()->all(), ['format' => 'excel'])) }}" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-md font-medium shadow-sm transition no-underline">
                        📊 Export Excel
                    </a>
                    <a href="{{ route('laporan.buku-besar.index', array_merge(request()->all(), ['format' => 'pdf'])) }}" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-md font-medium shadow-sm transition no-underline">
                        📕 Export PDF
                    </a>
                    <button type="button" onclick="window.print()" class="bg-gray-100 text-gray-700 px-6 py-2 rounded-md hover:bg-gray-200 transition">Cetak PDF</button>
                </form>
            </div>

            @forelse($accountsData as $account)
            <div class="bg-white border border-gray-200 rounded-lg mb-4 overflow-hidden shadow-sm">
                <button
                    @click="activeAccordion === {{ $account->id }} ? activeAccordion = null : activeAccordion = {{ $account->id }}"
                    class="w-full flex justify-between items-center px-6 py-4 bg-gray-50 hover:bg-gray-100 transition focus:outline-none">
                    <div class="flex items-center gap-4">
                        <span class="text-lg font-bold text-gray-800">{{ $account->kode }} - {{ $account->nama }}</span>
                        <span class="text-xs text-indigo-600 bg-indigo-50 px-2 py-1 rounded-full font-semibold border border-indigo-100">
                            {{ $account->items->count() }} Transaksi
                        </span>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="text-sm font-medium text-gray-500 italic">Periode: {{ date('F', mktime(0, 0, 0, $bulan, 1)) }} {{ $tahun }}</span>
                        <svg class="w-5 h-5 text-gray-400 transition-transform" :class="activeAccordion === {{ $account->id }} ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </div>
                </button>

                <div x-show="activeAccordion === {{ $account->id }}" x-collapse x-cloak class="p-6 border-t border-gray-100">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-gray-400 border-b text-xs uppercase font-bold tracking-wider">
                                    <th class="py-3 px-2 text-left font-bold uppercase tracking-wider w-32">Tanggal</th>
                                    <th class="py-3 px-2 text-left font-bold uppercase tracking-wider w-40">Referensi</th>
                                    <th class="py-3 px-2 text-left font-bold uppercase tracking-wider">Keterangan</th>
                                    <th class="py-3 px-2 text-right font-bold uppercase tracking-wider w-32">Debet</th>
                                    <th class="py-3 px-2 text-right font-bold uppercase tracking-wider w-32">Kredit</th>
                                    <th class="py-3 px-2 text-right font-bold uppercase tracking-wider w-40">Saldo</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @php
                                $saldo = $account->beginning_balance;
                                $subDebet = 0; $subKredit = 0;
                                @endphp
                                <tr class="text-gray-500 font-medium italic">
                                    <td class="py-4 px-2 italic">01/{{ $bulan }}/{{ $tahun }}</td>
                                    <td class="py-4 px-2">-</td>
                                    <td class="py-4 px-2">Beginning Balance</td>
                                    <td class="py-4 px-2 text-right text-gray-400">0,00</td>
                                    <td class="py-4 px-2 text-right text-gray-400">0,00</td>
                                    <td class="py-4 px-2 text-right font-bold text-gray-700">{{ number_format($account->beginning_balance, 2, ',', '.') }}</td>
                                </tr>
                                @foreach($account->items as $item)
                                @php
                                $saldo += ($item->debit - $item->kredit);
                                $subDebet += $item->debit; $subKredit += $item->kredit;
                                @endphp
                                <tr class="hover:bg-gray-50/50 transition">
                                    <td class="py-4 px-2">{{ date('y/m/d', strtotime($item->tanggal)) }}</td>
                                    <td class="py-4 px-2 text-gray-400 font-mono text-xs">{{ $item->no_ref }}</td>
                                    <td class="py-4 px-2 text-gray-700">{{ $item->deskripsi }}</td>
                                    <td class="py-4 px-2 text-right {{ $item->debit > 0 ? 'text-green-600' : 'text-gray-400' }}">
                                        {{ number_format($item->debit, 2, ',', '.') }}
                                    </td>
                                    <td class="py-4 px-2 text-right {{ $item->kredit > 0 ? 'text-red-600' : 'text-gray-400' }}">
                                        {{ number_format($item->kredit, 2, ',', '.') }}
                                    </td>
                                    <td class="py-4 px-2 text-right font-bold text-gray-800">
                                        {{ number_format($saldo, 2, ',', '.') }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="border-t-2 border-gray-100">
                                    <td colspan="3" class="py-6 px-2 text-right text-xs font-bold text-gray-400 uppercase tracking-widest">
                                        Subtotal Mutasi
                                    </td>
                                    <td class="py-6 px-2 text-right font-bold text-gray-700">
                                        {{ number_format($subDebet, 2, ',', '.') }}
                                    </td>
                                    <td class="py-6 px-2 text-right font-bold text-gray-700">
                                        {{ number_format($subKredit, 2, ',', '.') }}
                                    </td>
                                    <td class="py-6 px-2 text-right">
                                        <span class="bg-indigo-50 text-indigo-700 px-4 py-2 rounded-md font-bold">
                                            {{ number_format($saldo, 2, ',', '.') }}
                                        </span>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            @empty
            <div class="bg-white p-12 text-center shadow sm:rounded-lg">
                <p class="text-gray-400 italic">Tidak ada data transaksi untuk periode {{ date('F Y', mktime(0,0,0,$bulan,1,$tahun)) }}.</p>
            </div>
            @endforelse
        </div>
    </div>

    <style>
        [x-cloak] {
            display: none !important;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            button svg {
                display: none !important;
            }

            [x-show] {
                display: block !important;
            }

            /* Semua accordion terbuka saat cetak */
            .mb-4 {
                break-inside: avoid;
            }
        }
    </style>
</x-app-layout>