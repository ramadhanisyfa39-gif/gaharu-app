<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Daftar Surat Jalan Pengiriman') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                
                {{-- 1. PERUBAHAN DI SINI: Membuat Header Flexbox dan Menambahkan Tombol --}}
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-gray-700">Riwayat Pengiriman Logistik</h3>
                    <a href="{{ route('pengiriman.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded text-sm shadow transition duration-150">
                        + Buat Surat Jalan Baru
                    </a>
                </div>
                
                <table class="min-w-full divide-y divide-gray-200 border">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No Pengiriman</th>
                            {{-- 2. PERUBAHAN DI SINI: Mengubah judul kolom menjadi Kode Pesanan --}}
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kode Pesanan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal Kirim</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kurir / Armada</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($pengirimans as $kirim)
                        <tr>
                            <td class="px-6 py-4 text-sm font-bold text-indigo-600">{{ $kirim->no_pengiriman }}</td>
                            {{-- 3. PERUBAHAN DI SINI: Memanggil kode_pesanan melalui relasi baru --}}
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $kirim->pesanan->kode_pesanan ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $kirim->tanggal_pengiriman }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $kirim->kurir }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-sm text-center text-gray-400">Belum ada pengiriman barang dilakukan.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>