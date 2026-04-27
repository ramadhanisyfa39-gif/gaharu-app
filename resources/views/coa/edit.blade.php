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

                    <form action="{{ route('coa.update', $coa->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <label for="kode" class="block font-medium text-sm text-gray-700">Kode Akun</label>
                            <input type="text" name="kode" id="kode"
                                class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full"
                                value="{{ old('kode', $coa->kode) }}" required>
                            @error('kode')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="nama" class="block font-medium text-sm text-gray-700">Nama Akun</label>
                            <input type="text" name="nama" id="nama"
                                class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full"
                                value="{{ old('nama', $coa->nama) }}" required>
                        </div>

                        <div class="mb-4">
                            <label for="tipe" class="block font-medium text-sm text-gray-700">Tipe</label>
                            <select name="tipe" id="tipe" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" required>
                                @foreach(['Aset', 'Liabilitas', 'Ekuitas', 'Pendapatan', 'Beban'] as $cat)
                                <option value="{{ $cat }}" {{ (old('tipe', $coa->tipe) == $cat) ? 'selected' : '' }}>
                                    {{ $cat }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="block font-medium text-sm text-gray-700">Saldo Normal</label>
                            <div class="mt-2 space-x-4">
                                <label class="inline-flex items-center">
                                    <input type="radio" name="saldo_normal" value="debit"
                                        {{ old('saldo_normal', $coa->saldo_normal) == 'debit' ? 'checked' : '' }}
                                        class="text-indigo-600 shadow-sm focus:ring-indigo-500">
                                    <span class="ml-2">Debit</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" name="saldo_normal" value="kredit"
                                        {{ old('saldo_normal', $coa->saldo_normal) == 'kredit' ? 'checked' : '' }}
                                        class="text-indigo-600 shadow-sm focus:ring-indigo-500">
                                    <span class="ml-2">Kredit</span>
                                </label>
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Simpan Akun
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