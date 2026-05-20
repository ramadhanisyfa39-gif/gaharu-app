<x-app-layout>
    <style>
        .form-container {
            max-width: 900px;
            margin: 20px auto;
            padding: 20px;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .card-payroll {
            background: white;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            margin-bottom: 20px;
        }

        .card-title {
            font-weight: bold;
            color: #1e293b;
            border-bottom: 1px solid #f1f5f9;
            margin-bottom: 15px;
            padding-bottom: 10px;
            text-transform: uppercase;
            font-size: 14px;
        }

        .input-group {
            margin-bottom: 15px;
        }

        .input-group label {
            display: block;
            font-size: 13px;
            margin-bottom: 5px;
            color: #64748b;
        }

        .input-group input {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 5px;
            padding: 8px;
        }

        .btn-save {
            background: #0f172a;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            font-weight: bold;
        }

        .input-rupiah {
            text-align: right;
            font-weight: bold;
            color: #0f172a;
        }
    </style>

    <div class="form-container">
        <h2 style="margin-bottom: 20px; font-weight: bold;">Input Penggajian Karyawan</h2>

        <form action="{{ route('penggajian.store') }}" method="POST">
            @csrf

            <div class="card-payroll">
                <div class="input-group">
                    <label>Nama Karyawan</label>
                    <select name="karyawan_id" class="input-group input" required>
                        @foreach($karyawans as $k)
                        <option value="{{ $k->id }}">{{ $k->nama_karyawan }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="input-group">
                    <label>Bulan</label>
                    <input type="month" name="periode" class="input-group input" required>
                </div>
            </div>
    </div>

    <div class="grid-3">
        <div class="card-payroll">
            <div class="card-title" style="border-color: #10b981;">1. Penerimaan Tetap</div>
            <div class="input-group">
                <label>Gaji Pokok</label>
                <input type="text" name="gaji_pokok" value="0" class="input-rupiah">
            </div>
            <div class="input-group">
                <label>Tunjangan Transport</label>
                <input type="text" name="tunjangan_transport" value="0" class="input-rupiah">
            </div>
            <div class="input-group">
                <label>Tunjangan Makan</label>
                <input type="text" name="tunjangan_makan" value="0" class="input-rupiah">
            </div>
        </div>

        <div class="card-payroll">
            <div class="card-title" style="border-color: #3b82f6;">2. Penerimaan Tidak Tetap</div>
            <div class="input-group">
                <label>Lembur</label>
                <input type="text" name="lembur" value="0" class="input-rupiah">
            </div>
            <div class="input-group">
                <label>Bonus Target</label>
                <input type="text" name="bonus_target" value="0" class="input-rupiah">
            </div>
            <div class="input-group">
                <label>Bonus Tanggal Merah</label>
                <input type="text" name="bonus_tanggal_merah" value="0" class="input-rupiah">
            </div>
            <div class="input-group">
                <label>Bonus Birthday Service</label>
                <input type="text" name="bonus_birthday" value="0" class="input-rupiah">
            </div>
            <div class="input-group">
                <label>Bonus Lain-lain</label>
                <input type="text" name="bonus_dll" value="0" class="input-rupiah">
            </div>
        </div>

        <div class="card-payroll">
            <div class="card-title" style="border-color: #ef4444;">3. Potongan</div>
            <div class="input-group">
                <label>Kerusakan Inventaris</label>
                <input type="text" name="potongan_inventaris" value="0" class="input-rupiah">
            </div>
            <div class="input-group">
                <label>Keterlambatan</label>
                <input type="text" name="potongan_terlambat" value="0" class="input-rupiah">
            </div>
        </div>
    </div>

    <button type="submit" class="btn-save">SIMPAN & CETAK SLIP GAJI</button>
    </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('.input-rupiah');

            inputs.forEach(input => {
                input.addEventListener('input', function(e) {
                    // Ambil hanya angka dari input
                    let rawValue = this.value.replace(/[^0-9]/g, '');

                    // Format ulang ke Rupiah
                    if (rawValue !== '') {
                        this.value = formatRupiah(rawValue, 'Rp. ');
                    } else {
                        this.value = '';
                    }
                });
            });

            function formatRupiah(angka, prefix) {
                let number_string = angka.replace(/[^0-9]/g, ''),
                    sisa = number_string.length % 3,
                    rupiah = number_string.substr(0, sisa),
                    ribuan = number_string.substr(sisa).match(/\d{3}/gi);

                if (ribuan) {
                    let separator = sisa ? '.' : '';
                    rupiah += separator + ribuan.join('.');
                }

                return prefix + rupiah;
            }

            // --- BAGIAN PALING PENTING ---
            // Sebelum form dikirim ke controller, kita hilangkan "Rp." dan titiknya
            const form = document.querySelector('form');
            form.addEventListener('submit', function() {
                inputs.forEach(input => {
                    // Mengubah "Rp. 1.000.000" menjadi "1000000" agar DB tidak error
                    input.value = input.value.replace(/[^0-9]/g, '');
                });
            });
        });
    </script>
</x-app-layout>