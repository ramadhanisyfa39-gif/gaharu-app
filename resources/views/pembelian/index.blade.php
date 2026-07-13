<x-app-layout>
    <x-slot name="header">Pembelian</x-slot>

    <div class="container">

        <h4>Data Pembelian</h4>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill"></i> {{ session('error') }}
            </div>
        @endif

        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <a href="{{ route('pembelian.create') }}" class="btn btn-primary mb-0">
                Tambah Pembelian
            </a>

            <form action="{{ route('pembelian.index') }}" method="GET" class="d-flex gap-2">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari kode/supplier..." value="{{ request('search') }}" style="width: 220px; border-radius: 6px;">
                <button type="submit" class="btn btn-sm btn-primary" style="border-radius: 6px; border: none; padding: 5px 15px;">Cari</button>
                @if(request('search'))
                    <a href="{{ route('pembelian.index') }}" class="btn btn-sm btn-secondary" style="border-radius: 6px; padding: 5px 15px;">Reset</a>
                @endif
            </form>
        </div>

        <table class="table table-bordered align-middle" style="font-size:13px;">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Tanggal</th>
                    <th>Supplier</th>
                    <th>Gudang</th>
                    <th class="text-end">Total</th>
                    <th class="text-end">Kekurangan</th>
                    <th class="text-center">Pembayaran</th>
                    <th class="text-center">Barang Diterima</th>
                    <th class="text-center" style="min-width:160px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pembelian as $item)
                    @php
                        // Hitung sisa/kekurangan pembayaran
                        $total      = (float) $item->total;
                        if ($item->metode_pembayaran === 'dp') {
                            if ($item->nominal_dp && $item->nominal_dp > 0) {
                                $nominalDp = (float) $item->nominal_dp;
                            } else {
                                $persenDp   = (int) ($item->persen_dp ?? 0);
                                $nominalDp  = $persenDp > 0 ? round($total * $persenDp / 100) : 0;
                            }
                        } else {
                            $nominalDp = 0;
                        }

                        $kekurangan = match(true) {
                            // COD atau sudah lunas → tidak ada kekurangan
                            $item->metode_pembayaran === 'cod' => 0,
                            $item->is_lunas                    => 0,
                            // DP: sisa = total - nominal DP
                            $item->metode_pembayaran === 'dp'  => $total - $nominalDp,
                            // Termin: full amount belum dibayar
                            $item->metode_pembayaran === 'termin' => $total,
                            // Belum dicatat
                            default => 0,
                        };

                        $adaKekurangan = $kekurangan > 0 && !$item->is_lunas;
                    @endphp
                    <tr>
                        <td class="font-monospace" style="font-size:12px;">{{ $item->kode_pembelian }}</td>
                        <td>{{ \Carbon\Carbon::parse($item->tanggal)->format('d M Y') }}</td>
                        <td>{{ $item->supplier->nama ?? '-' }}</td>
                        <td>{{ $item->gudang->nama ?? '-' }}</td>

                        {{-- TOTAL --}}
                        <td class="text-end fw-semibold">
                            Rp {{ number_format($item->total, 0, ',', '.') }}
                        </td>

                        {{-- KEKURANGAN --}}
                        <td class="text-end">
                            @if(!$item->metode_pembayaran)
                                <span class="text-muted" style="font-size:11px;">—</span>
                            @elseif($item->metode_pembayaran === 'cod' || $item->is_lunas)
                                <span class="badge bg-success" style="font-size:11px;">Lunas</span>
                            @elseif($adaKekurangan)
                                <span class="fw-semibold text-danger">
                                    Rp {{ number_format($kekurangan, 0, ',', '.') }}
                                </span>
                            @else
                                <span class="text-muted" style="font-size:11px;">—</span>
                            @endif
                        </td>

                        {{-- PEMBAYARAN --}}
                        <td class="text-center">
                            @if($item->metode_pembayaran)
                                @php
                                    $labelMetode = [
                                        'cod'    => ['text' => 'COD',   'class' => 'bg-success'],
                                        'dp'     => ['text' => 'DP ' . $item->persen_dp . '%', 'class' => 'bg-info'],
                                    ][$item->metode_pembayaran];
                                @endphp
                                <div class="d-flex flex-column align-items-center gap-1">
                                    <span class="badge {{ $labelMetode['class'] }}"
                                          style="cursor:pointer;"
                                          onclick="lihatDetailPembayaran({{ $item->id }})">
                                        {{ $labelMetode['text'] }} ℹ️
                                    </span>

                                    {{-- Tombol Lunasi --}}
                                    @if($adaKekurangan)
                                        <button type="button"
                                                class="btn btn-sm mt-1"
                                                style="background:#dd7045; color:#fff; font-size:11px; padding:2px 10px; border-radius:6px;"
                                                onclick="bukaModalLunasi(
                                                    {{ $item->id }},
                                                    '{{ $item->kode_pembelian }}',
                                                    {{ $kekurangan }},
                                                    '{{ $item->supplier->nama ?? '' }}'
                                                )">
                                            <i class="bi bi-cash me-1"></i>Lunasi
                                        </button>
                                    @elseif($item->is_lunas && $item->metode_pembayaran !== 'cod')
                                        <span class="badge bg-success" style="font-size:10px;">✓ Lunas</span>
                                    @endif
                                </div>
                            @else
                                <button type="button"
                                        class="btn btn-sm"
                                        style="background:#606060; color:#fff; font-size:11px; padding:2px 10px;"
                                        onclick="bukaPembayaran({{ $item->id }}, '{{ $item->kode_pembelian }}', {{ $item->total }})">
                                    + Catat
                                </button>
                            @endif
                        </td>

                        {{-- BARANG DITERIMA --}}
                        <td class="text-center">
                            @if($item->is_diterima)
                                <div class="d-flex flex-column align-items-center">
                                    <span class="badge bg-success">✓ Diterima</span>
                                    <small class="text-muted mt-1" style="font-size:10px;">
                                        {{ \Carbon\Carbon::parse($item->diterima_at)->format('d M Y') }}
                                    </small>
                                </div>
                            @else
                                <form action="{{ route('pembelian.terima', $item->id) }}"
                                      method="POST" class="d-inline"
                                      onsubmit="return confirm('Konfirmasi penerimaan barang {{ $item->kode_pembelian }}?\nStok akan langsung masuk ke gudang.')">
                                    @csrf
                                    <button type="submit"
                                            class="btn btn-sm"
                                            style="background:#606060; color:#fff; font-size:11px; padding:2px 10px;">
                                        Terima Barang
                                    </button>
                                </form>
                            @endif
                        </td>

                        {{-- AKSI --}}
                        <td>
                            <div class="d-flex gap-1 flex-wrap">
                                <a href="{{ route('pembelian.show', $item->id) }}"
                                   class="btn btn-sm"
                                   style="background:#606060; color:#fff; font-size:11px;">
                                    Detail
                                </a>

                                @if(!$item->isTerkunci())
                                    <a href="{{ route('pembelian.edit', $item->id) }}"
                                       class="btn btn-sm"
                                       style="background:#606060; color:#fff; font-size:11px;">
                                        Edit
                                    </a>
                                    <form action="{{ route('pembelian.destroy', $item->id) }}"
                                          method="POST" class="d-inline"
                                          onsubmit="return confirm('Yakin ingin menghapus {{ $item->kode_pembelian }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="btn btn-sm"
                                                style="background:#606060; color:#fff; font-size:11px;">
                                            Hapus
                                        </button>
                                    </form>
                                @else
                                    <button class="btn btn-sm" disabled
                                            style="background:#d0d0d0; color:#888; font-size:11px;">Edit</button>
                                    <button class="btn btn-sm" disabled
                                            style="background:#d0d0d0; color:#888; font-size:11px;">Hapus</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">Belum ada data pembelian.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-3">{{ $pembelian->links() }}</div>

    </div>

    {{-- ══════════════════ MODAL: DETAIL PEMBAYARAN ══════════════════ --}}
    <div class="modal fade" id="modalDetailPembayaran" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Pembayaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td class="text-muted" width="40%">Kode Pembelian</td><td><strong id="dp_kode"></strong></td></tr>
                        <tr><td class="text-muted">Total</td><td id="dp_total"></td></tr>
                        <tr><td class="text-muted">Metode</td><td id="dp_metode_badge"></td></tr>
                        <tr id="row_jatuh_tempo" class="d-none"><td class="text-muted">Jatuh Tempo</td><td id="dp_jatuh_tempo"></td></tr>
                        <tr id="row_nominal_dp" class="d-none"><td class="text-muted">Nominal DP</td><td id="dp_nominal"></td></tr>
                        <tr id="row_sisa_dp" class="d-none"><td class="text-muted">Sisa Pelunasan</td><td id="dp_sisa" class="fw-semibold text-danger"></td></tr>
                        <tr id="row_pelunasan_tgl" class="d-none"><td class="text-muted">Est. Pelunasan</td><td id="dp_pelunasan"></td></tr>
                        <tr id="row_catatan" class="d-none"><td class="text-muted">Catatan</td><td id="dp_catatan" class="fst-italic"></td></tr>
                        <tr><td class="text-muted">Dicatat Pada</td><td id="dp_dicatat_pada" class="text-muted small"></td></tr>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════ MODAL: CATAT PEMBAYARAN ══════════════════ --}}
    <div class="modal fade" id="modalPembayaran" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Catat Pembayaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formPembayaran" method="POST" action="">
                    @csrf
                    <div class="modal-body">
                        <p class="text-muted small" id="infoPembelian"></p>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Metode Pembayaran</label>
                            <div class="d-flex gap-2">
                                <input type="radio" class="btn-check" name="metode_pembayaran" id="opt_cod" value="cod" onchange="toggleFieldPembayaran('cod')">
                                <label class="btn btn-outline-success" for="opt_cod">COD</label>
                                <input type="radio" class="btn-check" name="metode_pembayaran" id="opt_termin" value="termin" onchange="toggleFieldPembayaran('termin')">
                                <label class="btn btn-outline-warning" for="opt_termin">Termin</label>
                                <input type="radio" class="btn-check" name="metode_pembayaran" id="opt_dp" value="dp" onchange="toggleFieldPembayaran('dp')">
                                <label class="btn btn-outline-info" for="opt_dp">DP</label>
                            </div>
                        </div>
                        <div id="field_termin" class="d-none mb-3">
                            <label class="form-label">Jatuh Tempo</label>
                            <input type="date" name="tanggal_jatuh_tempo" class="form-control">
                            <div class="d-flex gap-2 mt-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="isiJatuhTempo(14)">+14 hari</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="isiJatuhTempo(30)">+30 hari</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="isiJatuhTempo(60)">+60 hari</button>
                            </div>
                        </div>
                        <div id="field_dp" class="d-none">
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label fw-semibold">Persentase DP</label>
                                    <div class="input-group">
                                        <input type="number" name="persen_dp" id="inputPersenDP" class="form-control" min="1" max="99" placeholder="cth: 30" oninput="updateDariPersen()">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label fw-semibold">Nominal DP</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" name="nominal_dp" id="inputNominalDP" class="form-control" min="1" placeholder="cth: 150000" oninput="updateDariNominal()">
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted" id="keteranganDP"></small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Estimasi Pelunasan</label>
                                <input type="date" name="tanggal_pelunasan" class="form-control">
                            </div>
                        </div>
                        <div class="mb-1">
                            <label class="form-label">Catatan <span class="text-muted">(opsional)</span></label>
                            <textarea name="catatan_pembayaran" class="form-control" rows="2" placeholder="Mis: Transfer ke BCA 1234567..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ══════════════════ MODAL: LUNASI ══════════════════ --}}
    <div class="modal fade" id="modalLunasi" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background:#fff8f5; border-bottom:1px solid #f0ddd4;">
                    <h5 class="modal-title" style="color:#dd7045;">
                        <i class="bi bi-cash-coin me-2"></i>Catat Pelunasan
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formLunasi" method="POST" action="">
                    @csrf
                    <div class="modal-body">

                        {{-- Info ringkas --}}
                        <div class="p-3 mb-3 rounded" style="background:#f8f4f0; border:1px solid #eadfd4;">
                            <div class="row g-2" style="font-size:13px;">
                                <div class="col-6">
                                    <div class="text-muted" style="font-size:11px;">KODE PEMBELIAN</div>
                                    <div class="fw-semibold" id="lunasi_kode">—</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted" style="font-size:11px;">SUPPLIER</div>
                                    <div class="fw-semibold" id="lunasi_supplier">—</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted" style="font-size:11px;">SISA YANG HARUS DIBAYAR</div>
                                    <div class="fw-bold text-danger" id="lunasi_sisa" style="font-size:15px;">—</div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nominal Pelunasan</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="nominal_pelunasan" id="inputNominalLunasi"
                                       class="form-control" min="1" placeholder="0">
                            </div>
                            <small class="text-muted">Masukkan jumlah yang dibayarkan untuk pelunasan</small>
                        </div>

                        <div class="mb-1">
                            <label class="form-label">Catatan <span class="text-muted">(opsional)</span></label>
                            <textarea name="catatan_pelunasan" class="form-control" rows="2"
                                      placeholder="Mis: Transfer BCA tgl 29 Jun..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle me-1"></i>Tandai Lunas
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ══════════════════ SCRIPT ══════════════════ --}}
    <script>
        const dataPembayaran = @json($dataPembayaran);
        let totalAktif = 0;

        // ── Catat Pembayaran ──
        function bukaPembayaran(id, kode, total) {
            totalAktif = total;
            document.getElementById('formPembayaran').action = '/pembelian/' + id + '/catat-pembayaran';
            document.getElementById('infoPembelian').textContent = kode + ' · Total: Rp ' + Number(total).toLocaleString('id-ID');
            document.querySelectorAll('input[name=metode_pembayaran]').forEach(r => r.checked = false);
            document.getElementById('field_termin').classList.add('d-none');
            document.getElementById('field_dp').classList.add('d-none');
            document.getElementById('inputPersenDP').value = '';
            document.getElementById('inputNominalDP').value = '';
            document.getElementById('keteranganDP').textContent = '';
            // Reset required
            const tglPelunasan = document.querySelector('#formPembayaran input[name=tanggal_pelunasan]');
            const tglJatuhTempo = document.querySelector('#formPembayaran input[name=tanggal_jatuh_tempo]');
            if (tglPelunasan) tglPelunasan.removeAttribute('required');
            if (tglJatuhTempo) tglJatuhTempo.removeAttribute('required');
            new bootstrap.Modal(document.getElementById('modalPembayaran')).show();
        }

        function toggleFieldPembayaran(metode) {
            document.getElementById('field_termin').classList.toggle('d-none', metode !== 'termin');
            document.getElementById('field_dp').classList.toggle('d-none', metode !== 'dp');

            const tglPelunasan = document.querySelector('#formPembayaran input[name=tanggal_pelunasan]');
            if (tglPelunasan) {
                if (metode === 'dp' || metode === 'termin') {
                    tglPelunasan.setAttribute('required', 'required');
                } else {
                    tglPelunasan.removeAttribute('required');
                }
            }

            const tglJatuhTempo = document.querySelector('#formPembayaran input[name=tanggal_jatuh_tempo]');
            if (tglJatuhTempo) {
                if (metode === 'termin') {
                    tglJatuhTempo.setAttribute('required', 'required');
                } else {
                    tglJatuhTempo.removeAttribute('required');
                }
            }
        }

        function isiJatuhTempo(hari) {
            const tgl = new Date();
            tgl.setDate(tgl.getDate() + hari);
            document.querySelector('input[name=tanggal_jatuh_tempo]').value = tgl.toISOString().split('T')[0];
        }

        function updateDariPersen() {
            const persen = parseFloat(document.getElementById('inputPersenDP').value) || 0;
            const nominal = Math.round(totalAktif * persen / 100);
            document.getElementById('inputNominalDP').value = nominal > 0 ? nominal : '';
            hitungKeteranganDP(nominal);
        }

        function updateDariNominal() {
            const nominal = parseFloat(document.getElementById('inputNominalDP').value) || 0;
            const persen = totalAktif > 0 ? Math.round((nominal / totalAktif) * 100) : 0;
            document.getElementById('inputPersenDP').value = persen > 0 ? persen : '';
            hitungKeteranganDP(nominal);
        }

        function hitungKeteranganDP(nominal) {
            const sisa = totalAktif - nominal;
            document.getElementById('keteranganDP').innerHTML =
                'DP = Rp ' + nominal.toLocaleString('id-ID') + ' · Sisa = <strong class="text-danger">Rp ' + sisa.toLocaleString('id-ID') + '</strong>';
        }

        // ── Detail Pembayaran ──
        function lihatDetailPembayaran(id) {
            const data = dataPembayaran[id];
            if (!data) return;
            const total = parseFloat(data.total);
            document.getElementById('dp_kode').textContent         = data.kode;
            document.getElementById('dp_total').textContent        = 'Rp ' + total.toLocaleString('id-ID');
            document.getElementById('dp_dicatat_pada').textContent = data.dicatat_pada ?? '-';
            const badgeClass = { cod: 'bg-success', termin: 'bg-warning text-dark', dp: 'bg-info' };
            document.getElementById('dp_metode_badge').innerHTML =
                `<span class="badge ${badgeClass[data.metode]}">${data.label}</span>`;
            ['row_jatuh_tempo','row_nominal_dp','row_sisa_dp','row_pelunasan_tgl','row_catatan']
                .forEach(rowId => document.getElementById(rowId).classList.add('d-none'));
            if (data.metode === 'termin') {
                document.getElementById('row_jatuh_tempo').classList.remove('d-none');
                document.getElementById('dp_jatuh_tempo').textContent = data.tanggal_jatuh_tempo ?? '-';
                document.getElementById('row_sisa_dp').classList.remove('d-none');
                document.getElementById('dp_sisa').textContent = 'Rp ' + total.toLocaleString('id-ID');
                if (data.tanggal_pelunasan) {
                    document.getElementById('row_pelunasan_tgl').classList.remove('d-none');
                    document.getElementById('dp_pelunasan').textContent = data.tanggal_pelunasan;
                }
            }
            if (data.metode === 'dp') {
                const nominalDP = parseFloat(data.nominal_dp) || Math.round(total * (data.persen_dp || 0) / 100);
                const persenDP = data.persen_dp || (total > 0 ? Math.round((nominalDP / total) * 100) : 0);
                const sisa = total - nominalDP;
                document.getElementById('row_nominal_dp').classList.remove('d-none');
                document.getElementById('row_sisa_dp').classList.remove('d-none');
                document.getElementById('dp_nominal').textContent = 'Rp ' + nominalDP.toLocaleString('id-ID') + ' (' + persenDP + '%)';
                document.getElementById('dp_sisa').textContent   = 'Rp ' + sisa.toLocaleString('id-ID');
                if (data.tanggal_pelunasan) {
                    document.getElementById('row_pelunasan_tgl').classList.remove('d-none');
                    document.getElementById('dp_pelunasan').textContent = data.tanggal_pelunasan;
                }
            }
            if (data.catatan) {
                document.getElementById('row_catatan').classList.remove('d-none');
                document.getElementById('dp_catatan').textContent = data.catatan;
            }
            new bootstrap.Modal(document.getElementById('modalDetailPembayaran')).show();
        }

        // ── Modal Lunasi ──
        function bukaModalLunasi(id, kode, kekurangan, supplier) {
            document.getElementById('formLunasi').action = '/pembelian/' + id + '/lunasi';
            document.getElementById('lunasi_kode').textContent     = kode;
            document.getElementById('lunasi_supplier').textContent = supplier;
            document.getElementById('lunasi_sisa').textContent     = 'Rp ' + Number(kekurangan).toLocaleString('id-ID');
            document.getElementById('inputNominalLunasi').value    = kekurangan;
            new bootstrap.Modal(document.getElementById('modalLunasi')).show();
        }
    </script>

</x-app-layout>