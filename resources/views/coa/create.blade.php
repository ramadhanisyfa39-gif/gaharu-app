<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Tambah Akun Baru') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    <form action="{{ route('coa.store') }}" method="POST">
                        @csrf

                        <div class="mb-4">
                            <label for="kode" class="block font-medium text-sm text-gray-700">Kode Akun</label>
                            <input type="text" name="kode" id="kode" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" value="{{ old('kode') }}" required placeholder="Contoh: 110001">
                            @error('kode')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="parent_id" class="block text-sm font-medium text-gray-700">Akun Induk (Parent Account)</label>
                            <select name="parent_id" id="parent_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">-- Tanpa Induk (Kategori Header Utama) --</option>

                                @foreach($parentAccounts as $parent)
                                <option value="{{ $parent->id }}">
                                    [{{ $parent->kode }}] - {{ $parent->nama }} ({{ $parent->tipe }})
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="nama" class="block font-medium text-sm text-gray-700">Nama Akun</label>
                            <input type="text" name="nama" id="nama" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" value="{{ old('nama') }}" required placeholder="Contoh: Kas Outlet">
                            @error('nama')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="tipe" class="block font-medium text-sm text-gray-700">Tipe Kelompok Akun</label>
                            <select name="tipe" id="tipe" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" required>
                                <option value="Aset" {{ old('tipe') == 'Aset' ? 'selected' : '' }}>Aset</option>
                                <option value="Liabilitas" {{ old('tipe') == 'Liabilitas' ? 'selected' : '' }}>Liabilitas (Kewajiban)</option>
                                <option value="Ekuitas" {{ old('tipe') == 'Ekuitas' ? 'selected' : '' }}>Ekuitas (Modal)</option>
                                <option value="Pendapatan" {{ old('tipe') == 'Pendapatan' ? 'selected' : '' }}>Pendapatan</option>
                                <option value="Beban" {{ old('tipe') == 'Beban' ? 'selected' : '' }}>Beban</option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">*Sistem otomatis menentukan posisi Saldo Normal (Debit/Kredit) di latar belakang berdasarkan tipe yang Anda pilih.</p>
                        </div>

                        <div class="mb-4">
                            <label for="saldo_awal" class="block font-medium text-sm text-gray-700">Nominal Saldo Awal (Mula-mula)</label>
                            <input type="number" name="saldo_awal" id="saldo_awal" min="0" step="any" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" value="{{ old('saldo_awal', 0) }}" placeholder="Masukkan nominal uang tunai/saldo jika ada">
                            <p class="text-xs text-gray-500 mt-1">Biarkan bernilai 0 atau kosong jika akun ini tidak memiliki saldo awal migrasi pembukuan.</p>
                            @error('saldo_awal')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center gap-4 pt-2">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Simpan Akun Baru
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