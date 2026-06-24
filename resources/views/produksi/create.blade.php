<x-app-layout>

    <div class="container mt-4 mb-5">
        <div class="card shadow-sm border-0">

            <div class="card-header bg-primary text-white py-3">
                <h4 class="mb-0">
                    <i class="bi bi-box-seam me-2"></i>
                    Input Hasil Produksi
                </h4>
                <small class="opacity-75">
                    Catat hasil produksi berdasarkan Work Order yang telah disetujui.
                </small>
            </div>

            <div class="card-body p-4">

                {{-- ALERT SUCCESS --}}
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                        <strong>Berhasil!</strong> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                {{-- ALERT ERROR --}}
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                        <strong>Gagal!</strong> {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                {{-- VALIDATION ERROR --}}
                @if($errors->any())
                    <div class="alert alert-warning alert-dismissible fade show shadow-sm" role="alert">
                        <strong>Data belum valid:</strong>
                        <ul class="mb-0 mt-2">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                {{-- LANGKAH 1 --}}
                <div class="card border-primary mb-4">
                    <div class="card-header bg-light">
                        <strong>
                            <span class="badge bg-primary rounded-pill me-2">1</span>
                            Pilih Work Order
                        </strong>
                    </div>

                    <div class="card-body">
                        <form action="{{ route('produksi.create') }}" method="GET">
                            <div class="row align-items-end">

                                <div class="col-md-9 mb-3 mb-md-0">
                                    <label class="form-label fw-semibold">
                                        Nomor Work Order Siap Produksi
                                    </label>

                                    <select name="work_order_id"
                                        class="form-select"
                                        required>
                                        <option value="">-- Pilih Nomor Work Order --</option>

                                        @foreach($workOrders as $wo)
                                            <option value="{{ $wo->id }}"
                                                {{ isset($selectedWoId) && $selectedWoId == $wo->id ? 'selected' : '' }}>

                                                {{ $wo->kode_wo ?? ($wo->no_wo ?? 'WO-BATCH-' . $wo->id) }}

                                                @if($wo->pesanan)
                                                    - {{ $wo->pesanan->customer->nama_customer ?? 'Pelanggan Umum' }}
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bi bi-search me-1"></i>
                                        Tampilkan Detail
                                    </button>
                                </div>

                            </div>
                        </form>
                    </div>
                </div>

                {{-- JIKA WO DIPILIH --}}
                @if(isset($selectedWoId) && $selectedWoId != null)

                    @php
                        $selectedWo = $workOrders->where('id', $selectedWoId)->first();
                        $totalTarget = $items->sum('total_target');
                    @endphp

                    {{-- RINGKASAN WO --}}
                    <div class="card border-info mb-4">
                        <div class="card-header bg-info text-dark">
                            <strong>
                                <i class="bi bi-clipboard-check me-2"></i>
                                Ringkasan Work Order
                            </strong>
                        </div>

                        <div class="card-body">
                            <div class="row">

                                <div class="col-md-3 mb-3 mb-md-0">
                                    <small class="text-muted d-block">Nomor Work Order</small>
                                    <strong class="text-primary">
                                        {{ $selectedWo->kode_wo ?? ($selectedWo->no_wo ?? 'WO-BATCH-' . $selectedWoId) }}
                                    </strong>
                                </div>

                                <div class="col-md-3 mb-3 mb-md-0">
                                    <small class="text-muted d-block">Pelanggan</small>
                                    <strong>
                                        {{ $selectedWo->pesanan->customer->nama_customer ?? 'Pelanggan Umum' }}
                                    </strong>
                                </div>

                                <div class="col-md-3 mb-3 mb-md-0">
                                    <small class="text-muted d-block">Total Target Produksi</small>
                                    <strong class="text-success">
                                        {{ (int) $totalTarget }} Unit
                                    </strong>
                                </div>

                                <div class="col-md-3">
                                    <small class="text-muted d-block">Status Work Order</small>
                                    <span class="badge bg-success">
                                        Siap Diproduksi
                                    </span>
                                </div>

                            </div>
                        </div>
                    </div>

                    {{-- FORM SIMPAN PRODUKSI --}}
                    <form action="{{ route('produksi.store') }}" method="POST">
                        @csrf

                        <input type="hidden" name="work_order_id" value="{{ $selectedWoId }}">

                        {{-- LANGKAH 2 --}}
                        <div class="card border-success mb-4">
                            <div class="card-header bg-light">
                                <strong>
                                    <span class="badge bg-success rounded-pill me-2">2</span>
                                    Data Produksi
                                </strong>
                            </div>

                            <div class="card-body">
                                <div class="row">

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">
                                            Tanggal Produksi
                                        </label>

                                        <input type="date"
                                            name="tanggal_produksi"
                                            class="form-control"
                                            value="{{ date('Y-m-d') }}"
                                            required>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">
                                            Gudang Tempat Produksi
                                        </label>

                                        <select name="gudang_id" class="form-select" required>
                                            @foreach($gudangs as $gudang)
                                                <option value="{{ $gudang->id }}"
                                                    {{ $gudang->id == 3 ? 'selected' : '' }}>
                                                    {{ $gudang->nama ?? ($gudang->nama_gudang ?? 'Gudang ' . $gudang->id) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                </div>
                            </div>
                        </div>

                        {{-- LANGKAH 3 --}}
                        <div class="card border-secondary mb-4">
                            <div class="card-header bg-light">
                                <strong>
                                    <span class="badge bg-dark rounded-pill me-2">3</span>
                                    Input Hasil Produksi
                                </strong>
                            </div>

                            <div class="card-body p-0">

                                <div class="table-responsive">
                                    <table class="table table-bordered mb-0 align-middle">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Produk Jadi</th>
                                                <th class="text-center" width="20%">Target WO</th>
                                                <th class="text-center" width="25%">Hasil Nyata</th>
                                                <th class="text-center" width="20%">Selisih</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            @if($items->isEmpty())
                                                <tr>
                                                    <td colspan="4" class="text-center text-danger py-4">
                                                        Tidak ada produk pada Work Order ini.
                                                    </td>
                                                </tr>
                                            @else
                                                @foreach($items as $index => $item)
                                                    <tr>
                                                        <td>
                                                            <input type="hidden"
                                                                name="produk_id[]"
                                                                value="{{ $item->produk_id }}">

                                                            <strong>
                                                                {{ $item->produk?->nama ?? 'Produk Tidak Terdefinisi' }}
                                                            </strong>
                                                        </td>

                                                        <td class="text-center">
                                                            <span class="badge bg-info text-dark fs-6">
                                                                {{ (int) $item->total_target }} Unit
                                                            </span>
                                                        </td>

                                                        <td>
                                                            <div class="input-group mx-auto" style="max-width: 180px;">
                                                                <input type="number"
                                                                    step="any"
                                                                    name="qty_hasil[]"
                                                                    value="{{ (int) $item->total_target }}"
                                                                    class="form-control text-center fw-bold qty-hasil"
                                                                    min="1"
                                                                    data-target="{{ (int) $item->total_target }}"
                                                                    data-index="{{ $index }}"
                                                                    required>

                                                                <span class="input-group-text">Unit</span>
                                                            </div>
                                                        </td>

                                                        <td class="text-center">
                                                            <span class="badge bg-success selisih-produksi"
                                                                id="selisih-{{ $index }}">
                                                                Sesuai Target
                                                            </span>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @endif
                                        </tbody>
                                    </table>
                                </div>

                            </div>
                        </div>

                        {{-- CATATAN PRODUKSI --}}
                        <div class="card border-warning mb-4">
                            <div class="card-header bg-light">
                                <strong>
                                    <i class="bi bi-pencil-square me-2"></i>
                                    Catatan Produksi
                                </strong>
                                <small class="text-muted">(Opsional)</small>
                            </div>

                            <div class="card-body">
                                <textarea name="catatan_produksi"
                                    class="form-control"
                                    rows="3"
                                    placeholder="Contoh: Terdapat produk gagal produksi atau bahan baku yang rusak."></textarea>
                            </div>
                        </div>

                        {{-- INFO HPP --}}
                        <div class="alert alert-info border-0 shadow-sm mb-4">
                            <h6 class="fw-bold mb-2">
                                <i class="bi bi-calculator me-2"></i>
                                Informasi Perhitungan HPP
                            </h6>

                            <div class="mb-1">
                                ✓ Pemakaian bahan baku dihitung otomatis menggunakan metode FIFO.
                            </div>

                            <div class="mb-1">
                                ✓ Biaya tenaga kerja langsung dan BOP dialokasikan otomatis oleh sistem.
                            </div>

                            <div>
                                ✓ Setelah disimpan, stok produk jadi akan diperbarui secara otomatis.
                            </div>
                        </div>

                        {{-- BUTTON --}}
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="{{ route('produksi.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i>
                                Kembali
                            </a>

                            <button type="submit"
                                class="btn btn-success px-4"
                                {{ $items->isEmpty() ? 'disabled' : '' }}>
                                <i class="bi bi-save me-1"></i>
                                Simpan Hasil Produksi
                            </button>
                        </div>

                    </form>

                @else

                    {{-- JIKA BELUM PILIH WO --}}
                    <div class="text-center border rounded p-5 bg-light">
                        <i class="bi bi-clipboard-data fs-1 text-secondary"></i>

                        <h5 class="mt-3 text-secondary">
                            Belum Ada Work Order Dipilih
                        </h5>

                        <p class="text-muted mb-0">
                            Pilih Nomor Work Order terlebih dahulu untuk menampilkan produk yang akan diproduksi.
                        </p>
                    </div>

                @endif

            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const inputs = document.querySelectorAll('.qty-hasil');

            inputs.forEach(function (input) {
                input.addEventListener('input', function () {
                    const target = parseFloat(this.dataset.target);
                    const hasil = parseFloat(this.value) || 0;
                    const index = this.dataset.index;

                    const badge = document.getElementById('selisih-' + index);
                    const selisih = hasil - target;

                    badge.classList.remove('bg-success', 'bg-warning', 'bg-danger', 'text-dark');

                    if (selisih === 0) {
                        badge.classList.add('bg-success');
                        badge.innerText = 'Sesuai Target';
                    } else if (selisih < 0) {
                        badge.classList.add('bg-warning', 'text-dark');
                        badge.innerText = 'Kurang ' + Math.abs(selisih) + ' Unit';
                    } else {
                        badge.classList.add('bg-danger');
                        badge.innerText = 'Lebih ' + selisih + ' Unit';
                    }
                });
            });
        });
    </script>

</x-app-layout>