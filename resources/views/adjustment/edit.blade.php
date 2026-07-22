<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit Jurnal Penyesuaian: {{ $jurnal->no_ref }}
        </h2>
    </x-slot>

    <div class="container py-4">
        <div class="card shadow">
            <div class="card-header bg-warning text-dark">
                <h4 class="mb-0">Koreksi Jurnal Penyesuaian: {{ $jurnal->no_ref }}</h4>
            </div>
            <div class="card-body">
                @if(session('error'))
                <div class="alert alert-danger mb-3">
                    {{ session('error') }}
                </div>
                @endif

                @if($errors->any())
                <div class="alert alert-danger mb-3">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <form action="{{ route('adjustment.update', $jurnal->id) }}" method="POST" id="form-adjustment">
                    @csrf
                    @method('PUT')

                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Tanggal</label>
                            <input type="date" name="tanggal" class="form-control" value="{{ old('tanggal', $jurnal->tanggal) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">No. Referensi</label>
                            <input type="text" name="no_ref" class="form-control" value="{{ $jurnal->no_ref }}" readonly>
                            <small class="text-muted">Nomor referensi tidak dapat diubah.</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Deskripsi / Keterangan</label>
                            <input type="text" name="deskripsi" class="form-control" value="{{ old('deskripsi', $jurnal->deskripsi) }}" required>
                        </div>
                    </div>

                    <h5 class="fw-bold mb-3">Item Jurnal Penyesuaian</h5>
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
                                @foreach(old('details', $jurnal->details) as $index => $detail)
                                <tr>
                                    <td>
                                        <select name="details[{{ $index }}][account_id]" class="form-select" required>
                                            <option value="">-- Pilih Akun --</option>
                                            @foreach($coas as $coa)
                                            <option value="{{ $coa->id }}" {{ (isset($detail['account_id']) ? $detail['account_id'] : $detail->account_id) == $coa->id ? 'selected' : '' }}>
                                                [{{ $coa->kode }}] {{ $coa->nama }}
                                            </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" name="details[{{ $index }}][debit]" class="form-control input-debit" min="0" step="0.01" value="{{ number_format(isset($detail['debit']) ? $detail['debit'] : $detail->debit, 2, '.', '') }}" required>
                                    </td>
                                    <td>
                                        <input type="number" name="details[{{ $index }}][kredit]" class="form-control input-kredit" min="0" step="0.01" value="{{ number_format(isset($detail['kredit']) ? $detail['kredit'] : $detail->kredit, 2, '.', '') }}" required>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-danger {{ $index < 2 ? 'disabled' : '' }}" onclick="hapusBaris(this)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
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

                    <div class="d-flex justify-content-between pt-3 border-top">
                        <a href="{{ route('adjustment.index') }}" class="btn btn-light border px-4">Batal</a>
                        <div class="d-flex gap-2">
                            <button type="submit" name="action" value="draft" class="btn btn-secondary px-4 fw-bold">
                                Simpan sebagai Draft
                            </button>
                            <button type="submit" name="action" value="post" class="btn btn-warning px-4 fw-bold shadow">
                                Perbarui & Post ke Buku Besar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let rowIndex = {{ count(old('details', $jurnal->details)) }};

        function tambahBaris() {
            let wrapper = document.getElementById('wrapper-items');
            let row = document.createElement('tr');

            row.innerHTML = `
                <td>
                    <select name="details[${rowIndex}][account_id]" class="form-select" required>
                        <option value="">-- Pilih Akun --</option>
                        @foreach($coas as $coa)
                        <option value="{{ $coa->id }}">[{{ $coa->kode }}] {{ $coa->nama }}</option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <input type="number" name="details[${rowIndex}][debit]" class="form-control input-debit" min="0" step="0.01" value="0.00" required>
                </td>
                <td>
                    <input type="number" name="details[${rowIndex}][kredit]" class="form-control input-kredit" min="0" step="0.01" value="0.00" required>
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
            let totalRows = document.querySelectorAll('#wrapper-items tr').length;
            if (totalRows <= 2) {
                alert('Jurnal Penyesuaian minimal harus memiliki 2 item (Debit & Kredit)!');
                return;
            }
            button.closest('tr').remove();
            hitungTotal();
        }

        function hitungTotal() {
            let debits = document.querySelectorAll('.input-debit');
            let kredits = document.querySelectorAll('.input-kredit');
            let totalD = 0;
            let totalK = 0;

            debits.forEach(i => {
                let val = parseFloat((i.value || '0').replace(',', '.'));
                totalD += isNaN(val) ? 0 : val;
            });

            kredits.forEach(i => {
                let val = parseFloat((i.value || '0').replace(',', '.'));
                totalK += isNaN(val) ? 0 : val;
            });

            document.getElementById('total-debit').innerText = "Rp " + totalD.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
            document.getElementById('total-kredit').innerText = "Rp " + totalK.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 });

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
                el.removeEventListener('input', hitungTotal);
                el.addEventListener('input', hitungTotal);
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            attachEvents();
            hitungTotal();
        });
    </script>
</x-app-layout>