<x-app-layout>
    <div class="bg-gray-100 pb-12 no-print">
        <div class="bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Neraca Saldo Lajur</h1>
                        <p class="text-sm text-gray-500">Monitoring integritas, mutasi, dan saldo akhir akun periode ini.</p>
                    </div>

                    <form action="{{ route('laporan.neraca-saldo.index') }}" method="GET" class="flex flex-wrap items-center gap-2">
                        <select name="bulan" class="rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                            @foreach(range(1, 12) as $m)
                            <option value="{{ sprintf('%02d', $m) }}" {{ $bulan == sprintf('%02d', $m) ? 'selected' : '' }}>
                                {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                            </option>
                            @endforeach
                        </select>
                        <select name="tahun" class="rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                            @for($y = date('Y'); $y >= 2020; $y--)
                            <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition shadow-sm">
                            Tampilkan
                        </button>
                        <a href="{{ route('laporan.neraca-saldo.index', array_merge(request()->all(), ['format' => 'excel'])) }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition shadow-sm no-underline">
                            📊 Export Excel
                        </a>
                        <a href="{{ route('laporan.neraca-saldo.index', array_merge(request()->all(), ['format' => 'pdf'])) }}" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition shadow-sm no-underline">
                            📕 Export PDF
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 my-6">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

            <div class="flex justify-between items-center mb-6 no-print">
                <div class="text-sm font-medium text-gray-500">
                    Periode: <span class="text-gray-900 font-bold">{{ date('F Y', mktime(0,0,0,$bulan,1,$tahun)) }}</span>
                </div>
                <button onclick="window.print()" class="flex items-center gap-2 bg-white border border-gray-200 px-4 py-2 rounded-lg shadow-sm hover:bg-gray-50 transition">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                    </svg>
                    <span class="text-xs font-bold text-gray-600 uppercase tracking-widest">Print PDF</span>
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse border border-gray-200">
                    <thead>
                        <tr class="bg-[#1f2537] text-black border-b border-gray-700 text-xs font-bold uppercase tracking-wider">
                            <th rowspan="2" class="p-3 w-28 text-left">Kode Akun</th>
                            <th rowspan="2" class="p-3 text-left">Nama Akun</th>
                            <th colspan="2" class="p-2 text-center bg-[#293754]">Saldo Awal</th>
                            <th colspan="2" class="p-2 text-center bg-[#3c2b2b]">Mutasi Periode</th>
                            <th colspan="2" class="p-2 text-center bg-[#1b3d2b]">Saldo Akhir</th>
                        </tr>
                        <tr class="bg-[#2a3142] text-black text-[11px] font-semibold border-b border-gray-300">
                            <th class="p-2 text-right bg-[#303f5e] w-32">Debit (Rp)</th>
                            <th class="p-2 text-right bg-[#303f5e] w-32">Kredit (Rp)</th>
                            <th class="p-2 text-right bg-[#473434] w-32">Debit (Rp)</th>
                            <th class="p-2 text-right bg-[#473434] w-32">Kredit (Rp)</th>
                            <th class="p-2 text-right bg-[#224a35] w-32">Debit (Rp)</th>
                            <th class="p-2 text-right bg-[#224a35] w-32">Kredit (Rp)</th>
                        </tr>
                    </thead>

                    @php
                    // Inisialisasi variabel total keseluruhan di bawah
                    $grandSA_D = 0; $grandSA_K = 0;
                    $grandM_D = 0; $grandM_K = 0;
                    $grandAK_D = 0; $grandAK_K = 0;

                    // Definisikan warna label kategori sesuai gambar referensi Anda
                    $kategoriData = [
                    'Aset' => ['color' => 'text-blue-600', 'bg' => 'bg-blue-50/50'],
                    'Kewajiban' => ['color' => 'text-orange-600', 'bg' => 'bg-orange-50/50'],
                    'Ekuitas' => ['color' => 'text-green-600', 'bg' => 'bg-green-50/50'],
                    'Pendapatan' => ['color' => 'text-purple-600', 'bg' => 'bg-purple-50/50'],
                    'Beban' => ['color' => 'text-red-600', 'bg' => 'bg-red-50/50'],
                    ];
                    @endphp

                    <tbody class="text-xs divide-y divide-gray-200">
                        @foreach($neracaSaldo->groupBy('tipe') as $tipe => $items)
                        <tr class="{{ $kategoriData[$tipe]['bg'] ?? 'bg-gray-50' }} font-bold text-[11px] uppercase tracking-wide">
                            <td colspan="8" class="p-2.5 {{ $kategoriData[$tipe]['color'] ?? 'text-gray-800' }}">
                                ● {{ $tipe == 'Beban' ? 'BEBAN & HPP' : $tipe }}
                            </td>
                        </tr>

                        @foreach($items as $row)
                        @php
                        // Akumulasi data ke grand total
                        $grandSA_D += $row->saldo_awal_debit ?? 0;
                        $grandSA_K += $row->saldo_awal_kredit ?? 0;
                        $grandM_D += $row->mutasi_debit ?? 0;
                        $grandM_K += $row->mutasi_kredit ?? 0;
                        $grandAK_D += $row->debet_akhir ?? 0;
                        $grandAK_K += $row->kredit_akhir ?? 0;
                        @endphp
                        <tr class="hover:bg-gray-50/80 transition-colors">
                            <td class="p-2.5 text-gray-500 font-medium border-r border-gray-100">{{ $row->kode }}</td>
                            <td class="p-2.5 font-semibold text-gray-800 border-r border-gray-100 flex items-center gap-2">
                                <span class="inline-block w-1 h-3 bg-blue-500 rounded"></span>
                                {{ $row->nama }}
                            </td>

                            <td class="p-2 text-right text-gray-700 border-r border-gray-100">
                                {{ ($row->saldo_awal_debit ?? 0) > 0 ? number_format($row->saldo_awal_debit, 0, ',', '.') : '-' }}
                            </td>
                            <td class="p-2 text-right text-gray-700 border-r border-gray-100">
                                {{ ($row->saldo_awal_kredit ?? 0) > 0 ? number_format($row->saldo_awal_kredit, 0, ',', '.') : '-' }}
                            </td>

                            <td class="p-2 text-right text-gray-700 border-r border-gray-100">
                                {{ ($row->mutasi_debit ?? 0) > 0 ? number_format($row->mutasi_debit, 0, ',', '.') : '-' }}
                            </td>
                            <td class="p-2 text-right text-gray-700 border-r border-gray-100">
                                {{ ($row->mutasi_kredit ?? 0) > 0 ? number_format($row->mutasi_kredit, 0, ',', '.') : '-' }}
                            </td>

                            <td class="p-2 text-right text-gray-900 font-medium border-r border-gray-100">
                                {{ ($row->debet_akhir ?? 0) > 0 ? number_format($row->debet_akhir, 0, ',', '.') : '-' }}
                            </td>
                            <td class="p-2 text-right text-gray-900 font-medium">
                                {{ ($row->kredit_akhir ?? 0) > 0 ? number_format($row->kredit_akhir, 0, ',', '.') : '-' }}
                            </td>
                        </tr>
                        @endforeach
                        @endforeach
                    </tbody>

                    <tfoot>
                        <tr class="bg-[#111625] text-white font-bold text-xs uppercase border-t-2 border-gray-800">
                            <td colspan="2" class="p-3 text-left tracking-wider">Total Keseluruhan</td>

                            <td class="p-2 text-right text-blue-300">{{ number_format($grandSA_D, 0, ',', '.') }}</td>
                            <td class="p-2 text-right text-blue-300 border-r border-gray-700">{{ number_format($grandSA_K, 0, ',', '.') }}</td>

                            <td class="p-2 text-right text-orange-300">{{ number_format($grandM_D, 0, ',', '.') }}</td>
                            <td class="p-2 text-right text-orange-300 border-r border-gray-700">{{ number_format($grandM_K, 0, ',', '.') }}</td>

                            <td class="p-2 text-right text-green-300">{{ number_format($grandAK_D, 0, ',', '.') }}</td>
                            <td class="p-2 text-right text-green-300">{{ number_format($grandAK_K, 0, ',', '.') }}</td>
                        </tr>

                        @php
                        $selisihSA = $grandSA_D - $grandSA_K;
                        $selisihM = $grandM_D - $grandM_K;
                        $selisihAK = $grandAK_D - $grandAK_K;
                        @endphp
                        <tr class="bg-[#0b0e16] text-[11px] font-medium text-gray-400 italic">
                            <td colspan="2" class="p-2 text-left text-gray-400">Selisih Saldo (Debit - Kredit)</td>

                            <td colspan="2" class="p-2 text-center border-r border-gray-800">
                                @if($selisihSA == 0)
                                <span class="text-green-400 font-bold">✓ 0 (Balance)</span>
                                @else
                                <span class="text-red-400 font-bold">{{ number_format($selisihSA, 0, ',', '.') }}</span>
                                @endif
                            </td>

                            <td colspan="2" class="p-2 text-center border-r border-gray-800">
                                @if($selisihM == 0)
                                <span class="text-green-400 font-bold">✓ 0 (Balance)</span>
                                @else
                                <span class="text-red-400 font-bold bg-red-950/50 px-2 py-0.5 rounded">{{ number_format($selisihM, 0, ',', '.') }}</span>
                                @endif
                            </td>

                            <td colspan="2" class="p-2 text-center">
                                @if($selisihAK == 0)
                                <span class="text-green-400 font-bold">✓ 0 (Balance)</span>
                                @else
                                <span class="text-red-400 font-bold bg-red-950/50 px-2 py-0.5 rounded">{{ number_format($selisihAK, 0, ',', '.') }}</span>
                                @endif
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <p class="text-center text-[10px] text-gray-400 mt-6 uppercase font-semibold tracking-widest">
                Subsistem Keuangan — CV Gaharu Agung Sejahtera
            </p>
        </div>
    </div>

    <style>
        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background: white !important;
            }

            table {
                border: 1px solid #d1d5db !important;
            }

            th {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            tr {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</x-app-layout>