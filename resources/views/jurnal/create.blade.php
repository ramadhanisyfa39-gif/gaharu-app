<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Input Jurnal</h2>
    </x-slot>

    <div class="container py-4">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Input Jurnal Umum</h4>
            </div>
            <div class="card-body">
                @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <form action="{{ route('jurnal.store') }}" method="POST" id="form-jurnal">
                    @csrf
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Tanggal</label>
                            <input type="date" name="tanggal" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">No. Referensi</label>
                            <input type="text" name="no_ref" class="form-control" placeholder="Kosongkan untuk otomatis">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Deskripsi</label>
                            <textarea name="deskripsi" class="form-control" rows="1" required placeholder="Keterangan transaksi..."></textarea>
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
                                @php $oldDetails = old('details', [0, 1]); @endphp
                                @foreach($oldDetails as $index => $oldValue)
                                <tr>
                                    <td>
                                        <select name="details[{{ $index }}][account_id]" class="form-select" required>
                                            <option value="">-- Pilih Akun --</option>
                                            @foreach($coas as $coa)
                                            <option value="{{ $coa->id }}" {{ (isset($oldValue['account_id']) && $oldValue['account_id'] == $coa->id) ? 'selected' : '' }}>
                                                {{ $coa->kode }} - {{ $coa->nama }}
                                            </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="number" name="details[{{ $index }}][debit]" class="form-control text-end input-debit" value="{{ $oldValue['debit'] ?? 0 }}" step="0.01" required></td>
                                    <td><input type="number" name="details[{{ $index }}][kredit]" class="form-control text-end input-kredit" value="{{ $oldValue['kredit'] ?? 0 }}" step="0.01" required></td>
                                    <td class="text-center">
                                        @if($index > 1)
                                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="this.parentElement.parentElement.remove(); hitungTotal();">×</button>
                                        @endif
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

                    <div class="d-flex justify-content-between border-top pt-3">
                        <a href="{{ route('jurnal.index') }}" class="btn btn-light border">Batal</a>
                        <button type="submit" class="btn btn-success px-5 shadow">Simpan Jurnal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let rowIndex = Number("{{ count($oldDetails) }}");

        function addRow() {
            let table = document.getElementById('jurnal-table').getElementsByTagName('tbody')[0];
            let row = table.insertRow();
            row.innerHTML = `
            <td>
                <select name="details[${rowIndex}][account_id]" class="form-select" required>
                    <option value="">-- Pilih Akun --</option>
                    @foreach($coas as $coa)
                        <option value="{{ $coa->id }}">{{ $coa->kode }} - {{ $coa->nama }}</option>
                    @endforeach
                </select>
            </td>
            <td><input type="number" name="details[${rowIndex}][debit]" class="form-control text-end input-debit" value="0" step="0.01" required></td>
            <td><input type="number" name="details[${rowIndex}][kredit]" class="form-control text-end input-kredit" value="0" step="0.01" required></td>
            <td class="text-center">
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="this.parentElement.parentElement.remove(); hitungTotal();">×</button>
            </td>
        `;
            rowIndex++;
            attachEvents();
        }

        function hitungTotal() {
            let debits = document.querySelectorAll('.input-debit');
            let kredits = document.querySelectorAll('.input-kredit');
            let totalD = 0;
            let totalK = 0;

            debits.forEach(i => totalD += parseFloat(i.value || 0));
            kredits.forEach(i => totalK += parseFloat(i.value || 0));

            document.getElementById('total-debit').innerText = "Rp " + totalD.toLocaleString('id-ID');
            document.getElementById('total-kredit').innerText = "Rp " + totalK.toLocaleString('id-ID');

            // Visual warning jika tidak balance
            if (totalD.toFixed(2) !== totalK.toFixed(2)) {
                document.getElementById('total-debit').className = 'text-end text-danger';
                document.getElementById('total-kredit').className = 'text-end text-danger';
            } else {
                document.getElementById('total-debit').className = 'text-end text-success';
                document.getElementById('total-kredit').className = 'text-end text-success';
            }
        }

        function attachEvents() {
            document.querySelectorAll('.input-debit, .input-kredit').forEach(el => {
                el.addEventListener('input', hitungTotal);
            });
        }

        attachEvents();
        hitungTotal();
    </script>
</x-app-layout>