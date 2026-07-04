<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Akun: ') . $coa->nama }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    @if($isLocked)
                    <div class="bg-blue-100 text-blue-800 p-3 rounded-md mb-4 text-xs font-medium">
                        ℹ️ <strong>Sistem Terproteksi:</strong> Riwayat transaksi keuangan harian sudah berjalan. Demi alasan keamanan akuntansi, Anda hanya dapat mengubah <strong>Nama Akun</strong> saja. Atribut kode, tipe, hubungan induk, dan saldo awal dinonaktifkan.
                    </div>
                    @endif

                    <form action="{{ route('coa.update', $coa->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <label for="kode" class="block font-medium text-sm text-gray-700">Kode Akun</label>
                            <input type="text" name="kode" id="kode"
                                class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full {{ $isLocked ? 'bg-gray-100 text-gray-500 cursor-not-allowed' : '' }}"
                                value="{{ old('kode', $coa->kode) }}"
                                required {{ $isLocked ? 'readonly' : '' }}>
                            @error('kode')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="parent_id" class="block font-medium text-sm text-gray-700">Akun Induk (Parent Account)</label>
                            @if($isLocked)
                            <input type="text" class="border-gray-300 bg-gray-100 text-gray-500 rounded-md shadow-sm mt-1 block w-full cursor-not-allowed"
                                value="{{ $coa->parent ? '['.$coa->parent->kode.'] '.$coa->parent->nama : 'Header Utama' }}" disabled>
                            @else
                            <select name="parent_id" id="parent_id" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full">
                                <option value="">-- Tanpa Induk (Header Utama) --</option>
                                @foreach($parentAccounts as $p)
                                <option value="{{ $p->id }}" {{ (old('parent_id', $coa->parent_id) == $p->id) ? 'selected' : '' }}>
                                    [{{ $p->kode }}] - {{ $p->nama }} ({{ $p->tipe }})
                                </option>
                                @endforeach
                            </select>
                            @endif
                        </div>

                        <div class="mb-4">
                            <label for="nama" class="block font-medium text-sm text-gray-700">Nama Akun</label>
                            <input type="text" name="nama" id="nama"
                                class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full"
                                value="{{ old('nama', $coa->nama) }}" required>
                            @error('nama')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="tipe" class="block font-medium text-sm text-gray-700">Tipe</label>
                            @if($isLocked)
                            <input type="text" class="border-gray-300 bg-gray-100 text-gray-500 rounded-md shadow-sm mt-1 block w-full cursor-not-allowed" value="{{ $coa->tipe }}" disabled>
                            @else
                            <select name="tipe" id="tipe" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" required>
                                @foreach(['Aset', 'Liabilitas', 'Ekuitas', 'Pendapatan', 'Beban'] as $cat)
                                <option value="{{ $cat }}" {{ (old('tipe', $coa->tipe) == $cat) ? 'selected' : '' }}>
                                    {{ $cat }}
                                </option>
                                @endforeach
                            </select>
                            @endif
                        </div>

                        <div class="mb-4">
                            <label for="saldo_awal" class="block font-medium text-sm text-gray-700">Nominal Saldo Awal</label>
                            <input type="number" name="saldo_awal" id="saldo_awal" min="0" step="any"
                                class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full {{ $isLocked ? 'bg-gray-100 text-gray-500 cursor-not-allowed' : '' }}"
                                value="{{ old('saldo_awal', $saldoAwal) }}"
                                {{ $isLocked ? 'readonly' : '' }}>
                            @error('saldo_awal')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center gap-4 pt-2">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Simpan Perubahan
                            </button>
                            <a href="{{ route('coa.index') }}" class="text-sm text-gray-600 hover:text-gray-900 underline">
                                Batal
                            </a>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>