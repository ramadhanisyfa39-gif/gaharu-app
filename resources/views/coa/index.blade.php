<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Chart of Accounts') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                @if($isLocked)
                <div class="bg-yellow-100 text-yellow-800 p-4 rounded-md mb-4 text-sm font-medium shadow-sm">
                    🔒 <strong>Sistem Terkunci:</strong> Transaksi harian sudah berjalan di dalam sistem. Input saldo awal, kode, tipe, dan hubungan akun induk telah dikunci permanen demi menjaga integritas pembukuan. Anda hanya dapat mengubah nama akun untuk keperluan koreksi teks.
                </div>
                @endif

                <div class="flex justify-between items-center mb-4">
                    <a href="{{ route('coa.create') }}" class="btn btn-primary" style="background-color: #4A90E2; color: white; padding: 10px 15px; border-radius: 5px; display: inline-block; font-weight: 600; text-transform: uppercase; font-size: 0.75rem; tracking-widest: 0.1em;">
                        Tambah Akun Baru
                    </a>
                </div>

                @if(session('success'))
                <div class="alert alert-success mb-4 text-green-600 font-medium bg-green-50 p-3 rounded-md border border-green-200">{{ session('success') }}</div>
                @endif

                @if(session('error'))
                <div class="alert alert-danger mb-4 text-red-600 font-medium bg-red-50 p-3 rounded-md border border-red-200">{{ session('error') }}</div>
                @endif

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b bg-gray-50 text-gray-700 font-semibold uppercase text-xs tracking-wider">
                                <th class="p-3">Kode Akun</th>
                                <th class="p-3">Nama Akun</th>
                                <th class="p-3">Tipe</th>
                                <th class="p-3">Saldo</th>
                                <th class="p-3">Tanggal Input</th>
                                <th class="p-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 text-sm">
                            @foreach($coas as $coa)
                            <tr class="border-b hover:bg-gray-50 transition duration-150">
                                <td class="p-3 font-mono {{ $coa->parent_id == null ? 'font-bold text-gray-900' : 'text-gray-600 text-xs' }}">
                                    {{ $coa->kode }}
                                </td>

                                <td class="p-3 {{ $coa->parent_id == null ? 'font-bold text-gray-900' : 'pl-8 text-gray-700' }}">
                                    @if($coa->parent_id != null)
                                    <span class="text-gray-400 mr-1">↳</span>
                                    @endif
                                    {{ $coa->nama }}
                                </td>

                                <td class="p-3">
                                    <span class="px-2 py-0.5 rounded text-xs font-semibold 
                        {{ in_array($coa->tipe, ['Aset', 'Beban']) ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-purple-50 text-purple-700 border border-purple-200' }}">
                                        {{ $coa->tipe }}
                                    </span>
                                </td>
                                <td class="p-3 font-semibold text-gray-900">
                                    Rp {{ number_format($coa->saldo_normal == 'debit' ? $coa->opening_debit : $coa->opening_kredit, 0, ',', '.') }}
                                </td>
                                <td class="p-3 text-xs text-gray-500">
                                    {{ $coa->tgl_input_saldo_awal ? \Carbon\Carbon::parse($coa->tgl_input_saldo_awal)->format('d M Y H:i') : '-' }}
                                </td>
                                <td class="p-3 text-xs">
                                    @if($coa->parent_id == null)
                                    <span class="text-gray-400 font-medium italic">🔒 Master Sistem</span>
                                    @else
                                    <a href="{{ route('coa.edit', $coa->id) }}" class="text-blue-600 hover:text-blue-900 font-semibold underline">Edit</a>
                                    @if(!$isLocked)
                                    |
                                    <form action="{{ route('coa.destroy', $coa->id) }}" method="POST" class="inline">
                                        @csrf @method('DELETE')
                                        <button class="text-red-600 hover:text-red-900 font-semibold underline" onclick="return confirm('Apakah Anda yakin ingin menghapus akun ini beserta histori saldo awalnya?')">Hapus</button>
                                    </form>
                                    @else
                                    | <span class="text-gray-400 italic">🔒 Terkunci</span>
                                    @endif
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>