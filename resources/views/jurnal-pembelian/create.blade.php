<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Review & Input Jurnal Pembelian</h2>
    </x-slot>

    <div class="container py-4">
        <div class="card shadow border-0 rounded-3">
            <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
                <h4 class="mb-0 fw-bold">Form Konfirmasi Jurnal Pembelian Bahan Baku</h4>
                <span class="badge bg-light text-dark border font-monospace px-2.5 py-1.5 fs-6">Nota: {{ $pembelian->kode_pembelian }}</span>
            </div>
            <div class="card-body p-4">
                @if($errors->any())
                <div class="alert alert-danger border-0 shadow-sm mb-4">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <form action="{{ route('jurnal-pembelian.store', $pembelian->id) }}" method="POST" id="form-jurnal">
                    @csrf

                    <input type="hidden" name="tahap" value="{{ $tahap }}">

                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Tanggal Pembukuan</label>
                            <input type="date" name="tanggal" class="form-control"
                                value="{{ isset($pembelian) ? \Carbon\Carbon::parse($pembelian->tanggal)->format('Y-m-d') : date('Y-m-d') }}" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">No. Referensi</label>
                            <input type="text" name="no_ref" class="form-control font-monospace fw-bold"
                                value="{{ isset($pembelian) ? 'JV-PEMB-' . $pembelian->kode_pembelian : '' }}" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Deskripsi Jurnal</label>
                            <textarea name="deskripsi" class="form-control" rows="1" required>{{ isset($pembelian) ? 'Pembukuan jurnal khusus pembelian bahan baku atas No. Invoice: ' . $pembelian->kode_pembelian . ' [Supplier: ' . ($pembelian->supplier->nama ?? '-') . ']' : '' }}</textarea>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered align-middle" id="jurnal-table">
                            <thead class="table-light text-secondary small text-uppercase fw-bold">
                                <tr>
                                    <th style="width: 50%">Akun (COA)</th>
                                    <th style="width: 20%">Debit</th>
                                    <th style="width: 20%">Kredit</th>
                                    <th style="width: 50px"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                $renderDetails = old('details', $defaultDetails);
                                @endphp
                                @foreach($renderDetails as $index => $value)
                                <tr>
                                    <td>
                                        <select name="details[{{ $index }}][account_id]" class="form-select select-coa" required>
                                            <option value="">-- Pilih Akun --</option>
                                            @foreach($coas as $coa)
                                            <option value="{{ $coa->id }}" {{ (isset($value['account_id']) && $value['account_id'] == $coa->id) ? 'selected' : '' }}>
                                                {{ $coa->kode }} - {{ $coa->nama }}
                                            </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="number" name="details[{{ $index }}][debit]" class="form-control text-end input-debit fw-bold text-success" value="{{ $value['debit'] ?? 0 }}" step="0.01" required></td>
                                    <td><input type="number" name="details[{{ $index }}][kredit]" class="form-control text-end input-kredit fw-bold text-danger" value="{{ $value['kredit'] ?? 0 }}" step="0.01" required></td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-outline-danger btn-sm border-0" onclick="this.parentElement.parentElement.remove(); hitungTotal();">×</button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-secondary fw-bold fs-6">
                                <tr>
                                    <td class="text-center">TOTAL</td>
                                    <td class="text-end text-danger" id="total-debit">Rp 0</td>
                                    <td class="text-end text-danger" id="total-kredit">Rp 0</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="mt-3 mb-4">
                        <button type="button" class="btn btn-outline-primary btn-sm fw-bold px-3 shadow-sm" onclick="addRow()">
                            <i class="fas fa-plus me-1"></i> Tambah Baris Penyesuaian
                        </button>
                    </div>

                    <div class="d-flex justify-content-between border-top pt-3">
                        <a href="{{ route('jurnal-pembelian.index') }}" class="btn btn-light border fw-semibold">Kembali</a>
                        <button type="submit" class="btn btn-success px-5 fw-bold shadow">Konfirmasi & Simpan Jurnal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let rowIndex = Number("{{ count($renderDetails) }}");

        function addRow() {
            // PERBAIKAN: Menggunakan indeks agar menunjuk langsung ke elemen tbody tunggal
            let tableBody = document.getElementById('jurnal-table').getElementsByTagName('tbody')[0];
            let row = tableBody.insertRow();
            row.innerHTML = `
                <td>
                    <select name="details[${rowIndex}][account_id]" class="form-select select-coa" required>
                        <option value="">-- Pilih Akun --</option>
                        @foreach($coas as $coa)
                            <option value="{{ $coa->id }}">{{ $coa->kode }} - {{ $coa->nama }}</option>
                        @endforeach
                    </select>
                </td>
                <td><input type="number" name="details[${rowIndex}][debit]" class="form-control text-end input-debit fw-bold text-success" value="0" step="0.01" required></td>
                <td><input type="number" name="details[${rowIndex}][kredit]" class="form-control text-end input-kredit fw-bold text-danger" value="0" step="0.01" required></td>
                <td class="text-center">
                    <button type="button" class="btn btn-outline-danger btn-sm border-0" onclick="this.parentElement.parentElement.remove(); hitungTotal();">×</button>
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

            document.getElementById('total-debit').innerText = "Rp " + totalD.toLocaleString('id-ID', {
                minimumFractionDigits: 2
            });
            document.getElementById('total-kredit').innerText = "Rp " + totalK.toLocaleString('id-ID', {
                minimumFractionDigits: 2
            });

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