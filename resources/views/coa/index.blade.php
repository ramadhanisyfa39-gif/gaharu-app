<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Chart of Accounts') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                <a href="{{ route('coa.create') }}" class="btn btn-primary mb-4" style="background-color: #4A90E2; color: white; padding: 10px; border-radius: 5px; display: inline-block;">
                    Tambah Akun Baru
                </a>

                @if(session('success'))
                <div class="alert alert-success mb-4 text-green-600">{{ session('success') }}</div>
                @endif

                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b">
                            <th class="p-2">Kode Akun</th>
                            <th class="p-2">Nama Akun</th>
                            <th class="p-2">Tipe</th>
                            <th class="p-2">Saldo Normal</th>
                            <th class="p-2">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($coas as $coa)
                        <tr class="border-b">
                            <td class="p-2">{{ $coa->kode }}</td>
                            <td class="p-2">{{ $coa->nama }}</td>
                            <td class="p-2">{{ $coa->tipe }}</td>
                            <td class="p-2">{{ $coa->saldo_normal }}</td>
                            <td class="p-2">
                                <a href="{{ route('coa.edit', $coa->id) }}" class="text-blue-600">Edit</a>
                                |
                                <form action="{{ route('coa.destroy', $coa->id) }}" method="POST" class="inline">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600" onclick="return confirm('Hapus?')">Hapus</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>