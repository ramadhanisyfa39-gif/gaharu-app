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

        .input-group select,
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
            text-transform: uppercase;
        }

        .input-rupiah {
            text-align: right;
            font-weight: bold;
            color: #0f172a;
        }
    </style>

    <div class="form-container">
        <h2 style="margin-bottom: 20px; font-weight: bold;">
            {{ isset($payroll) ? 'Ubah Data Penggajian Karyawan' : 'Input Penggajian Karyawan' }}
        </h2>

        @if ($errors->any())
            <div class="alert alert-danger" style="margin-bottom: 20px; border-radius: 5px; background-color: #fde8e8; border-color: #f8b4b4; color: #9b1c1c; padding: 15px;">
                <ul style="margin: 0; padding-left: 20px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ isset($payroll) ? route('penggajian.update', $payroll->id) : route('penggajian.store') }}" method="POST">
            @csrf

            @if(isset($payroll))
            @method('PUT')
            @endif

            <input type="hidden" name="periode" value="{{ $target_periode }}">

            <div class="card-payroll">
                <div class="input-group">
                    <label>Nama Karyawan</label>
                    <select name="karyawan_id" class="input-group input" required {{ isset($payroll) ? 'disabled' : '' }}>
                        <option value="">-- Pilih Karyawan --</option>
                        @foreach($karyawans as $k)
                        <option value="{{ $k->id }}" data-gaji="{{ $k->gaji_pokok }}" {{ (isset($payroll) && $payroll->karyawan_id == $k->id) ? 'selected' : '' }}>
                            {{ $k->nama_karyawan }}
                        </option>
                        @endforeach
                    </select>

                    @if(isset($payroll))
                    <input type="hidden" name="karyawan_id" value="{{ $payroll->karyawan_id }}">
                    @endif
                </div>

                <div class="input-group">
                    <label>Periode Target (Bulan & Tahun)</label>
                    <input type="text" value="{{ $target_periode }}" disabled style="background-color: #f8fafc; color: #64748b; font-weight: 600;">
                </div>
            </div>

            <div class="grid-3">
                <div class="card-payroll">
                    <div class="card-title" style="border-bottom: 2px solid #10b981;">1. Penerimaan Tetap</div>
                    <div class="input-group">
                        <label>Gaji Pokok</label>
                        <input type="text" name="gaji_pokok" value="{{ isset($payroll) ? number_format($payroll->gaji_pokok, 0, ',', '.') : '0' }}" class="input-rupiah">
                    </div>
                    <div class="input-group">
                        <label>Tunjangan Transport</label>
                        <input type="text" name="tunjangan_transport" value="{{ isset($payroll) ? number_format($payroll->tunjangan_transport, 0, ',', '.') : '0' }}" class="input-rupiah">
                    </div>
                    <div class="input-group">
                        <label>Tunjangan Makan</label>
                        <input type="text" name="tunjangan_makan" value="{{ isset($payroll) ? number_format($payroll->tunjangan_makan, 0, ',', '.') : '0' }}" class="input-rupiah">
                    </div>
                </div>

                <div class="card-payroll">
                    <div class="card-title" style="border-bottom: 2px solid #3b82f6;">2. Penerimaan Tidak Tetap</div>
                    <div class="input-group">
                        <label>Lembur</label>
                        <input type="text" name="lembur" value="{{ isset($payroll) ? number_format($payroll->lembur, 0, ',', '.') : '0' }}" class="input-rupiah">
                    </div>
                    <div class="input-group">
                        <label>Bonus Target</label>
                        <input type="text" name="bonus_target" value="{{ isset($payroll) ? number_format($payroll->bonus_target, 0, ',', '.') : '0' }}" class="input-rupiah">
                    </div>
                    <div class="input-group">
                        <label>Bonus Tanggal Merah</label>
                        <input type="text" name="bonus_tanggal_merah" value="{{ isset($payroll) ? number_format($payroll->bonus_tanggal_merah, 0, ',', '.') : '0' }}" class="input-rupiah">
                    </div>
                    <div class="input-group">
                        <label>Bonus Birthday Service</label>
                        <input type="text" name="bonus_birthday" value="{{ isset($payroll) ? number_format($payroll->bonus_birthday, 0, ',', '.') : '0' }}" class="input-rupiah">
                    </div>
                    <div class="input-group">
                        <label>Bonus Lain-lain</label>
                        <input type="text" name="bonus_dll" value="{{ isset($payroll) ? number_format($payroll->bonus_dll, 0, ',', '.') : '0' }}" class="input-rupiah">
                    </div>
                </div>

                <div class="card-payroll">
                    <div class="card-title" style="border-bottom: 2px solid #ef4444;">3. Potongan</div>
                    <div class="input-group">
                        <label>Kerusakan Inventaris</label>
                        <input type="text" name="potongan_inventaris" value="{{ isset($payroll) ? number_format($payroll->potongan_inventaris, 0, ',', '.') : '0' }}" class="input-rupiah">
                    </div>
                    <div class="input-group">
                        <label>Keterlambatan</label>
                        <input type="text" name="potongan_terlambat" value="{{ isset($payroll) ? number_format($payroll->potongan_terlambat, 0, ',', '.') : '0' }}" class="input-rupiah">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-save">
                {{ isset($payroll) ? 'PERBAIKI DATA GAJI' : 'SIMPAN DATA GAJI' }}
            </button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('.input-rupiah');

            // Lakukan formatting awal untuk data edit yang dimuat dari database
            inputs.forEach(input => {
                let rawValue = input.value.replace(/[^0-9]/g, '');
                if (rawValue !== '' && rawValue !== '0') {
                    input.value = formatRupiah(rawValue, 'Rp. ');
                } else if (rawValue === '0') {
                    input.value = 'Rp. 0';
                }
            });

            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    if (this.value === 'Rp. 0') {
                        this.value = '';
                    }
                });

                input.addEventListener('blur', function() {
                    if (this.value === '') {
                        this.value = 'Rp. 0';
                    }
                });

                input.addEventListener('input', function(e) {
                    let rawValue = this.value.replace(/[^0-9]/g, '');
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

            // Bersihkan format masker rupiah sebelum dikirim ke backend controller
            const form = document.querySelector('form[action*="penggajian"]');
            if (form) {
                form.addEventListener('submit', function() {
                    inputs.forEach(input => {
                        input.value = input.value.replace(/[^0-9]/g, '');
                        if (input.value === '') {
                            input.value = '0';
                        }
                    });
                });
            }

            const selectKaryawan = document.querySelector('select[name="karyawan_id"]');
            const inputGajiPokok = document.querySelector('input[name="gaji_pokok"]');
            
            if (selectKaryawan && inputGajiPokok) {
                selectKaryawan.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    const gajiPokokVal = selectedOption.getAttribute('data-gaji');
                    
                    if (gajiPokokVal) {
                        inputGajiPokok.value = formatRupiah(gajiPokokVal, 'Rp. ');
                    } else {
                        inputGajiPokok.value = 'Rp. 0';
                    }
                });
            }
        });
    </script>
</x-app-layout>