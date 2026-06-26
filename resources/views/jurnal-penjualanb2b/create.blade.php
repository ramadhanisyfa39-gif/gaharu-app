<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Review & Input Jurnal Penjualan B2B</h2>
    </x-slot>

    <div class="container py-4">
        <div class="card shadow">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Form Konfirmasi Jurnal Otomatis</h4>
                <span class="badge bg-light text-dark fw-bold">Status Pesanan: {{ strtoupper($statusPesanan) }}</span>
            </div>
            <div class="card-body">
                @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
                @endif

                <form action="{{ route('laporan.jurnal-penjualanb2b.store', $pembayaran->id) }}" method="POST" id="form-jurnal">
                    @csrf
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Tanggal Pembukuan</label>
                            <input type="date" name="tanggal" class="form-control" value="{{ isset($pembayaran) ? $pembayaran->tanggal_bayar : date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">No. Referensi</label>
                            <input type="text" name="no_ref" class="form-control" value="{{ strtolower($statusPesanan) === 'selesai' ? 'JV-REV-' . $pesanan->kode_pesanan : 'JV-DP-' . $pesanan->kode_pesanan . '-' . $pembayaran->id }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Deskripsi Jurnal</label>
                            <textarea name="deskripsi" class="form-control" rows="1" required>{{ strtolower($statusPesanan) === 'selesai' ? 'Pengakuan Pendapatan & HPP atas Penjualan B2B No. Invoice: ' . $pesanan->kode_pesanan : 'Pencatatan Uang Muka (DP) atas Penjualan B2B No. Invoice: ' . $pesanan->kode_pesanan }}</textarea>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered" id="jurnal-table">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50%">Akun (COA)</th>
                                    <th>Debit</th>
                                    <th>Kredit</th>
                                    <th style="width: 50px"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $renderDetails = old('details', $defaultDetails); @endphp
                                @foreach($renderDetails as $index => $val)
                                <tr>
                                    <td>
                                        <select name="details[{{ $index }}][account_id]" class="form-select select-coa" required>
                                            <option value="">-- Pilih Akun --</option>
                                            @foreach($coas as $coa)
                                            <option value="{{ $coa->id }}" {{ (isset($val['account_id']) && $val['account_id'] == $coa->id) ? 'selected' : '' }}>{{ $coa->kode }} - {{ $coa->nama }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="number" name="details[{{ $index }}][debit]" class="form-control text-end input-debit" value="{{ $val['debit'] ?? 0 }}" step="0.01" required></td>
                                    <td><input type="number" name="details[{{ $index }}][kredit]" class="form-control text-end input-kredit" value="{{ $val['kredit'] ?? 0 }}" step="0.01" required></td>
                                    <td class="text-center"><button type="button" class="btn btn-outline-danger btn-sm" onclick="this.parentElement.parentElement.remove(); hitungTotal();">×</button></td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-secondary fw-bold">
                                <tr>
                                    <td class="text-center">TOTAL</td>
                                    <td class="text-end text-danger" id="total-debit">Rp 0</td>
                                    <td class="text-end text-danger" id="total-kredit">Rp 0</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="mt-3 mb-4"><button type="button" class="btn btn-outline-primary btn-sm" onclick="addRow()">+ Tambah Baris Penyesuaian</button></div>
                    <div class="d-flex justify-content-between border-top pt-3">
                        <a href="{{ route('laporan.jurnal-penjualanb2b.index') }}" class="btn btn-light border">Kembali</a>
                        <button type="submit" class="btn btn-success px-5 shadow">Konfirmasi & Simpan Jurnal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let rowIndex = Number("{{ count($renderDetails) }}");

        function addRow() {
            let table = document.getElementById('jurnal-table').getElementsByTagName('tbody');
            let row = table.insertRow();
            row.innerHTML = `
            <td>
                <select name="details[${rowIndex}][account_id]" class="form-select select-coa" required>
                    <option value="">-- Pilih Akun --</option>
                    @foreach($coas as $coa)<option value="{{ $coa->id }}">{{ $coa->kode }} - {{ $coa->nama }}</option>@endforeach
                </select>
            </td>
            <td><input type="number" name="details[${rowIndex}][debit]" class="form-control text-end input-debit" value="0" step="0.01" required></td>
            <td><input type="number" name="details[${rowIndex}][kredit]" class="form-control text-end input-kredit" value="0" step="0.01" required></td>
            <td class="text-center"><button type="button" class="btn btn-outline-danger btn-sm" onclick="this.parentElement.parentElement.remove(); hitungTotal();">×</button></td>`;
            rowIndex++;
            attachEvents();
        }

        function hitungTotal() {
            let debits = document.querySelectorAll('.input-debit'),
                kredits = document.querySelectorAll('.input-kredit'),
                totalD = 0,
                totalK = 0;
            debits.forEach(i => totalD += parseFloat(i.value || 0));
            kredits.forEach(i => totalK += parseFloat(i.value || 0));
            document.getElementById('total-debit').innerText = "Rp " + totalD.toLocaleString('id-ID', {
                minimumFractionDigits: 2
            });
            document.getElementById('total-kredit').innerText = "Rp " + totalK.toLocaleString('id-ID', {
                minimumFractionDigits: 2
            });
            let className = (totalD.toFixed(2) !== totalK.toFixed(2)) ? 'text-end text-danger' : 'text-end text-success';
            document.getElementById('total-debit').className = className;
            document.getElementById('total-kredit').className = className;
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