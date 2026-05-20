<x-app-layout>
    <div class="bg-gray-100 min-h-screen pb-12">
        <div class="bg-white border-b border-gray-200 no-print">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Neraca Saldo</h1>
                        <p class="text-sm text-gray-500">Monitoring integritas data akun periode ini.</p>
                    </div>

                    <form action="{{ route('laporan.neraca-saldo.index') }}" method="GET" class="flex items-center gap-2">
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
                    </form>
                </div>
            </div>
        </div>

        <div class="p-10">
            <div class="flex justify-between items-center mb-12 pb-6 border-b-2 border-gray-900">
                <div class="flex flex-col">
                    <h2 class="text-2xl font-black text-gray-900 leading-none">CV GAHARU AGUNG SEJAHTERA</h2>
                    <div class="flex items-center gap-3 mt-2">
                        <span class="bg-gray-900 text-gray-500 text-[10px] px-2 py-0.5 rounded font-bold uppercase tracking-wider">Laporan</span>
                        <h3 class="text-sm font-bold text-gray-500 uppercase tracking-[0.2em]">Neraca Saldo</h3>
                    </div>
                </div>

                <div class="flex flex-col items-end gap-2">
                    <button onclick="window.print()" class="no-print group flex items-center gap-2 bg-white border border-gray-200 px-4 py-2 rounded-lg shadow-sm hover:bg-gray-50 transition-all duration-200">
                        <svg class="w-4 h-4 text-gray-500 group-hover:text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                        </svg>
                        <span class="text-xs font-bold text-gray-600 uppercase tracking-widest">Print PDF</span>
                    </button>
                    <p class="text-[11px] font-medium text-gray-400 italic">
                        Periode: <span class="text-gray-700 font-bold not-italic">{{ date('F Y', mktime(0,0,0,$bulan,1,$tahun)) }}</span>
                    </p>
                </div>
            </div>
        </div>

        <div class="p-0">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-gray-800 text-white">
                        <th class="py-4 px-6 text-left text-xs font-bold uppercase tracking-wider">Kode Akun</th>
                        <th class="py-4 px-6 text-left text-xs font-bold uppercase tracking-wider">Deskripsi Perkiraan</th>
                        <th class="py-4 px-6 text-right text-xs font-bold uppercase tracking-wider">Debet</th>
                        <th class="py-4 px-6 text-right text-xs font-bold uppercase tracking-wider">Kredit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @php $totalD = 0; $totalK = 0; @endphp
                    @foreach($neracaSaldo as $row)
                    @php
                    $totalD += $row->debet_akhir;
                    $totalK += $row->kredit_akhir;
                    @endphp
                    <tr class="hover:bg-blue-50/30 transition-colors">
                        <td class="py-4 px-6 text-sm font-semibold text-gray-500">{{ $row->kode }}</td>
                        <td class="py-4 px-6 text-sm font-bold text-gray-800">{{ $row->nama }}</td>
                        <td class="py-4 px-6 text-right text-sm {{ $row->debet_akhir > 0 ? 'text-gray-900' : 'text-gray-300' }}">
                            {{ number_format($row->debet_akhir, 2, ',', '.') }}
                        </td>
                        <td class="py-4 px-6 text-right text-sm {{ $row->kredit_akhir > 0 ? 'text-gray-900' : 'text-gray-300' }}">
                            {{ number_format($row->kredit_akhir, 2, ',', '.') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-gray-900 text-white font-bold">
                        <td colspan="2" class="py-5 px-6 text-right text-xs uppercase tracking-widest">Total Periode {{ date('F Y', mktime(0,0,0,$bulan,1,$tahun)) }}</td>
                        <td class="py-5 px-6 text-right text-lg">
                            <span class="text-blue-400">Rp</span> {{ number_format($totalD, 2, ',', '.') }}
                        </td>
                        <td class="py-5 px-6 text-right text-lg">
                            <span class="text-blue-400">Rp</span> {{ number_format($totalK, 2, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="p-6 bg-gray-50 flex justify-center">
            @if($totalD == $totalK)
            <div class="flex items-center gap-2 text-green-700 font-bold text-xs uppercase tracking-widest bg-white border border-green-200 px-6 py-2 rounded-full shadow-sm">
                <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                Data Is Balanced
            </div>
            @else
            <div class="flex items-center gap-2 text-red-700 font-bold text-xs uppercase tracking-widest bg-white border border-red-200 px-6 py-2 rounded-full shadow-sm animate-bounce">
                <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                Out of Balance
            </div>
            @endif
        </div>
    </div>

    <p class="text-center text-[10px] text-gray-400 mt-8 uppercase font-semibold tracking-[0.2em]">Subsistem Keuangan - CV Gaharu Agung Sejahtera</p>
    </div>
    </div>

    <style>
        @media print {
            .no-print {
                display: none !important;
            }

            .bg-gray-100 {
                background: white !important;
            }

            .shadow-xl {
                box-shadow: none !important;
            }

            .mt-8 {
                margin-top: 0 !important;
            }

            tr.bg-gray-800 {
                background-color: #1f2937 !important;
                -webkit-print-color-adjust: exact;
            }

            tr.bg-gray-900 {
                background-color: #111827 !important;
                -webkit-print-color-adjust: exact;
            }

            th,
            td {
                color: inherit !important;
            }
        }
    </style>
</x-app-layout>