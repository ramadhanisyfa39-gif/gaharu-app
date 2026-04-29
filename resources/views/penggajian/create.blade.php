@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-dark text-white p-3">
            <h4 class="mb-0">Input Pembayaran Gaji</h4>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('penggajian.store') }}" method="POST">
                @csrf
                <div class="row g-3">
                    <!-- Pilih Karyawan -->
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Karyawan</label>
                        <select name="karyawan_id" class="form-select" required>
                            <option value="">-- Pilih Karyawan --</option>
                            @foreach($karyawans as $k)
                            <option value="{{ $k->id }}">{{ $k->nama_karyawan }} ({{ $k->jabatan }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Periode -->
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Periode (Bulan-Tahun)</label>
                        <input type="month" name="periode_bulan_tahun" class="form-control" required>
                    </div>

                    <!-- Tanggal Transfer -->
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Tanggal Transfer</label>
                        <input type="date" name="tanggal_transfer" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>

                    <hr class="my-4">

                    <!-- Komponen Gaji -->
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Gaji Pokok</label>
                        <input type="number" name="gaji_pokok" id="gaji_pokok" class="form-control calc" value="0" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Lembur</label>
                        <input type="number" name="lembur" id="lembur" class="form-control calc" value="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Potongan</label>
                        <input type="number" name="potongan" id="potongan" class="form-control calc" value="0">
                    </div>

                    <div class="col-12 mt-4">
                        <div class="alert alert-info d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Total Gaji Bersih:</h5>
                            <h3 class="mb-0 fw-bold" id="label_total">Rp 0</h3>
                            <input type="hidden" name="total_gaji_bersih" id="total_gaji_bersih" value="0">
                        </div>
                    </div>

                    <div class="col-12 text-end">
                        <a href="{{ route('penggajian.index') }}" class="btn btn-light border px-4">Batal</a>
                        <button type="submit" class="btn btn-primary px-5 shadow">Simpan Gaji </Data></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const inputs = document.querySelectorAll('.calc');
    const labelTotal = document.getElementById('label_total');
    const inputTotal = document.getElementById('total_gaji_bersih');

    function hitungGaji() {
        let gapok = parseFloat(document.getElementById('gaji_pokok').value) || 0;
        let lembur = parseFloat(document.getElementById('lembur').value) || 0;
        let potongan = parseFloat(document.getElementById('potongan').value) || 0;

        let bersih = gapok + lembur - potongan;

        labelTotal.innerText = "Rp " + bersih.toLocaleString('id-ID');
        inputTotal.value = bersih;
    }

    inputs.forEach(input => {
        input.addEventListener('input', hitungGaji);
    });
</script>
@endsection