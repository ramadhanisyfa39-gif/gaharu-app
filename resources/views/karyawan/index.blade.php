<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Daftar Karyawan</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow sm:rounded-lg">
                <a href="{{ route('karyawan.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded mb-4 inline-block">Tambah Karyawan</a>

                <table class="w-full mt-4 border">
                    <thead>
                        <tr class="bg-gray-100 border-b">
                            <th class="p-2 text-left">Nama Karyawan</th>
                            <th class="p-2 text-left">Jabatan</th>
                            <th class="p-2 text-left">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($karyawans as $k)
                        <tr class="border-b">
                            <td class="p-2">{{ $k->nama_karyawan }}</td>
                            <td class="p-2">{{ $k->jabatan }}</td>
                            <td class="p-2">
                                <a href="{{ route('karyawan.edit', $k->id) }}" class="text-blue-600">Edit</a>
                                <form action="{{ route('karyawan.destroy', $k->id) }}" method="POST" class="inline">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 ml-2" onclick="return confirm('Hapus?')">Hapus</button>
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