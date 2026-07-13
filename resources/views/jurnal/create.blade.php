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
                            <label class="form-label fw-bold">Deskripsi / Keterangan</label>
                            <input type="text" name="deskripsi" class="form-control" placeholder="Contoh: Pembayaran Gaji Karyawan" required>
                        </div>
                    </div>

                    <h5 class="fw-bold mb-3">Item Jurnal</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="table-items">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40%">Akun / COA</th>
                                    <th style="width: 25%">Debit</th>
                                    <th style="width: 25%">Kredit</th>
                                    <th style="width: 10%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="wrapper-items">
                                <tr>
                                    <td>
                                        <select name="details[0][account_id]" class="form-select select2" required>
                                            <option value="">-- Pilih Akun --</option>
                                            @foreach($coas as $coa)
                                            <option value="{{ $coa->id }}">[{{ $coa->kode }}] {{ $coa->nama }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" name="details[0][debit]" class="form-control input-debit" min="0" step="0.01" value="0" required>
                                    </td>
                                    <td>
                                        <input type="number" name="details[0][kredit]" class="form-control input-kredit" min="0" step="0.01" value="0" required>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-danger disabled"><i class="bi bi-trash"></i></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <select name="details[1][account_id]" class="form-select select2" required>
                                            <option value="">-- Pilih Akun --</option>
                                            @foreach($coas as $coa)
                                            <option value="{{ $coa->id }}">[{{ $coa->kode }}] {{ $coa->nama }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" name="details[1][debit]" class="form-control input-debit" min="0" step="0.01" value="0" required>
                                    </td>
                                    <td>
                                        <input type="number" name="details[1][kredit]" class="form-control input-kredit" min="0" step="0.01" value="0" required>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-danger disabled"><i class="bi bi-trash"></i></button>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="table-light fw-bold">
                                    <td class="text-end">Total:</td>
                                    <td id="total-debit" class="text-end text-success">Rp 0</td>
                                    <td id="total-kredit" class="text-end text-success">Rp 0</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="mb-3">
                        <button type="button" class="btn btn-sm btn-secondary" onclick="tambahBaris()">
                            <i class="bi bi-plus-circle"></i> Tambah Baris
                        </button>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary px-4">Simpan Transaksi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let rowIndex = 2;

        function tambahBaris() {
            let wrapper = document.getElementById('wrapper-items');
            let row = document.createElement('tr');

            row.innerHTML = `
                <td>
                    <select name="details[\${rowIndex}][account_id]" class="form-select" required>
                        <option value="">-- Pilih Akun --</option>
                        @foreach($coas as $coa)
                        <option value="{{ $coa->id }}">[{{ $coa->kode }}] {{ $coa->nama }}</option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <input type="number" name="details[\${rowIndex}][debit]" class="form-control input-debit" min="0" step="0.01" value="0" required>
                </td>
                <td>
                    <input type="number" name="details[\${rowIndex}][kredit]" class="form-control input-kredit" min="0" step="0.01" value="0" required>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-danger" onclick="hapusBaris(this)"><i class="bi bi-trash"></i></button>
                </td>
            `;
            wrapper.appendChild(row);
            rowIndex++;
            attachEvents();
        }

        function hapusBaris(button) {
            button.closest('tr').remove();
            hitungTotal();
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

            // Peringatan visual jika tidak balance
            if (totalD.toFixed(2) !== totalK.toFixed(2)) {
                document.getElementById('total-debit').className = 'text-end text-danger fw-bold';
                document.getElementById('total-kredit').className = 'text-end text-danger fw-bold';
            } else {
                document.getElementById('total-debit').className = 'text-end text-success fw-bold';
                document.getElementById('total-kredit').className = 'text-end text-success fw-bold';
            }
        }

        function attachEvents() {
            document.querySelectorAll('.input-debit, .input-kredit').forEach(el => {
                el.removeEventListener('input', hitungTotal); // Hindari duplikasi event
                el.addEventListener('input', hitungTotal);
            });
        }

        // Jalankan event listener saat pertama kali halaman dimuat
        document.addEventListener('DOMContentLoaded', function() {
            attachEvents();
        });
    </script>
</x-app-layout>