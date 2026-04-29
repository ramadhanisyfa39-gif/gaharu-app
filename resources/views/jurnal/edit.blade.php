<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit Jurnal: {{ $jurnal->no_ref }}
        </h2>
    </x-slot>

    <div class="container py-4">
        <div class="card shadow">
            <div class="card-header bg-warning text-dark">
                <h4 class="mb-0">Koreksi Jurnal Umum: {{ $jurnal->no_ref }}</h4>
            </div>
            <div class="card-body">
                <form action="{{ route('jurnal.update', $jurnal->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Tanggal</label>
                            <input type="date" name="tanggal" class="form-control" value="{{ $jurnal->tanggal }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">No. Referensi</label>
                            <input type="text" name="no_ref" class="form-control" value="{{ $jurnal->no_ref }}" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Deskripsi</label>
                            <textarea name="deskripsi" class="form-control" rows="1" required>{{ $jurnal->deskripsi }}</textarea>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered" id="jurnal-table">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40%">Akun (COA)</th>
                                    <th>Debit</th>
                                    <th>Kredit</th>
                                    <th style="width: 50px"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($jurnal->details as $index => $detail)
                                <tr>
                                    <td>
                                        <select name="details[{{ $index }}][account_id]" class="form-select" required>
                                            @foreach($coas as $coa)
                                            <option value="{{ $coa->id }}" {{ $detail->account_id == $coa->id ? 'selected' : '' }}>
                                                {{ $coa->kode }} - {{ $coa->nama }}
                                            </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="number" name="details[{{ $index }}][debit]" class="form-control text-end input-debit" value="{{ $detail->debit }}" step="0.01" required></td>
                                    <td><input type="number" name="details[{{ $index }}][kredit]" class="form-control text-end input-kredit" value="{{ $detail->kredit }}" step="0.01" required></td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="this.parentElement.parentElement.remove(); hitungTotal();">×</button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-secondary fw-bold">
                                <tr>
                                    <td class="text-center">TOTAL</td>
                                    <td class="text-end" id="total-debit">Rp 0</td>
                                    <td class="text-end" id="total-kredit">Rp 0</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <button type="button" class="btn btn-outline-primary btn-sm mb-4" onclick="addRow()">+ Tambah Baris</button>

                    <div class="d-flex justify-content-between pt-3">
                        <a href="{{ route('jurnal.index') }}" class="btn btn-light border">Batal</a>
                        <button type="submit" class="btn btn-warning px-5 shadow">Perbarui Jurnal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Gunakan script yang sama dengan Create untuk hitungTotal() dan addRow()
        // (Script JavaScript dari bagian Create di atas bisa dikopi ke sini)
    </script>
</x-app-layout>