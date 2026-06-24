<x-app-layout>
    <x-slot name="header">
        Pembelian
    </x-slot>

    <div class="container">

        <h4>Data Pembelian</h4>

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <a href="{{ route('pembelian.create') }}" class="btn btn-primary mb-3">
            Tambah Pembelian
        </a>

        <table class="table table-bordered align-middle">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Tanggal</th>
                    <th>Supplier</th>
                    <th>Gudang</th>
                    <th>Total</th>
                    <th>Pembayaran</th>
                    <th width="320">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pembelian as $item)
                    @php
                        $sudahDibayar = !is_null($item->metode_pembayaran);
                    @endphp
                    <tr>
                        <td>{{ $item->kode_pembelian }}</td>
                        <td>{{ $item->tanggal }}</td>
                        <td>{{ $item->supplier->nama ?? '-' }}</td>
                        <td>{{ $item->gudang->nama ?? '-' }}</td>
                        <td>Rp {{ number_format($item->total, 0, ',', '.') }}</td>

                        {{-- KOLOM PEMBAYARAN --}}
                        <td>
                            @if($item->metode_pembayaran)
                                @php
                                    $labelMetode = [
                                        'cod'    => ['text' => 'COD',    'class' => 'bg-success'],
                                        'termin' => ['text' => 'Termin', 'class' => 'bg-warning text-dark'],
                                        'dp'     => ['text' => 'DP ' . $item->persen_dp . '%', 'class' => 'bg-info'],
                                    ][$item->metode_pembayaran];
                                @endphp
                                <span
                                    class="badge {{ $labelMetode['class'] }}"
                                    style="cursor:pointer;"
                                    onclick="lihatDetailPembayaran({{ $item->id }})">
                                    {{ $labelMetode['text'] }}
                                </span>
                            @else
                                <button
                                    type="button"
                                    class="btn btn-sm"
                                    style="background-color:#606060; color:#fff; border:none;"
                                    onclick="bukaPembayaran({{ $item->id }}, '{{ $item->kode_pembelian }}', {{ $item->total }})">
                                    + Catat
                                </button>
                            @endif
                        </td>

                        {{-- KOLOM AKSI --}}
                        <td>
                            <div class="d-flex gap-1 flex-wrap">

                                {{-- Detail --}}
                                <a href="{{ route('pembelian.show', $item->id) }}"
                                   class="btn btn-sm"
                                   style="background-color:#606060; color:#fff; border:none;">
                                    Detail
                                </a>

                                {{-- Edit --}}
                                @if($item->isEditable())
                                    <a href="{{ route('pembelian.edit', $item->id) }}"
                                       class="btn btn-sm"
                                       style="background-color:#606060; color:#fff; border:none;">
                                        Edit
                                    </a>
                                @else
                                    <button class="btn btn-sm" style="background-color:#606060; color:#fff; border:none;" disabled>
                                        Edit Terkunci
                                    </button>
                                @endif

                                {{-- Hapus --}}
                                @if($item->isEditable())
                                    @if($sudahDibayar)
                                        {{-- Sudah ada pembayaran → blokir dengan popup peringatan --}}
                                        <button
                                            type="button"
                                            class="btn btn-sm"
                                            style="background-color:#606060; color:#fff; border:none;"
                                            onclick="peringatanHapusTerkunci('{{ $item->kode_pembelian }}')">
                                            Hapus
                                        </button>
                                    @else
                                        {{-- Belum ada pembayaran → boleh hapus normal --}}
                                        <form
                                            action="{{ route('pembelian.destroy', $item->id) }}"
                                            method="POST"
                                            class="d-inline"
                                            onsubmit="return confirm('Yakin ingin menghapus pembelian ini? Stok akan ikut dikurangi.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="btn btn-sm"
                                                    style="background-color:#606060; color:#fff; border:none;">
                                                Hapus
                                            </button>
                                        </form>
                                    @endif
                                @else
                                    <button class="btn btn-sm" style="background-color:#606060; color:#fff; border:none;" disabled>
                                        Delete Terkunci
                                    </button>
                                @endif

                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center">Belum ada data pembelian.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-3">
            {{ $pembelian->links() }}
        </div>

    </div>

    {{-- ====================================================== --}}
    {{-- MODAL: PERINGATAN HAPUS (pembayaran sudah tercatat)    --}}
    {{-- ====================================================== --}}
    <div class="modal fade" id="modalPeringatanHapus" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title d-flex align-items-center gap-2">
                        <span style="font-size:1.4rem;">⚠️</span>
                        <span>Tidak Dapat Dihapus</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-2">
                    <p class="mb-1">
                        Pembelian <strong id="kodePeringatan"></strong> tidak dapat dihapus karena
                        <span class="text-danger fw-semibold">pembayaran sudah tercatat</span>.
                    </p>
                    <p class="text-muted small mb-0">
                        Untuk menghapus, hapus catatan pembayaran terlebih dahulu atau hubungi administrator.
                    </p>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button"
                            class="btn btn-sm px-4"
                            style="background-color:#606060; color:#fff; border:none;"
                            data-bs-dismiss="modal">
                        Mengerti
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ====================================================== --}}
    {{-- MODAL: DETAIL PEMBAYARAN (read-only, buka dari badge)  --}}
    {{-- ====================================================== --}}
    <div class="modal fade" id="modalDetailPembayaran" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Pembayaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted" width="40%">Kode Pembelian</td>
                            <td><strong id="dp_kode"></strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Total</td>
                            <td id="dp_total"></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Metode</td>
                            <td id="dp_metode_badge"></td>
                        </tr>
                        <tr id="row_jatuh_tempo" class="d-none">
                            <td class="text-muted">Jatuh Tempo</td>
                            <td id="dp_jatuh_tempo"></td>
                        </tr>
                        <tr id="row_nominal_dp" class="d-none">
                            <td class="text-muted">Nominal DP</td>
                            <td id="dp_nominal"></td>
                        </tr>
                        <tr id="row_sisa_dp" class="d-none">
                            <td class="text-muted">Sisa Pelunasan</td>
                            <td id="dp_sisa"></td>
                        </tr>
                        <tr id="row_pelunasan" class="d-none">
                            <td class="text-muted">Est. Pelunasan</td>
                            <td id="dp_pelunasan"></td>
                        </tr>
                        <tr id="row_catatan" class="d-none">
                            <td class="text-muted">Catatan</td>
                            <td id="dp_catatan" class="fst-italic"></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Dicatat Pada</td>
                            <td id="dp_dicatat_pada" class="text-muted small"></td>
                        </tr>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button"
                            class="btn btn-sm px-3"
                            style="background-color:#606060; color:#fff; border:none;"
                            data-bs-dismiss="modal">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ====================================================== --}}
    {{-- MODAL: CATAT PEMBAYARAN (form input)                   --}}
    {{-- ====================================================== --}}
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
                                <input type="radio" class="btn-check" name="metode_pembayaran"
                                       id="opt_cod" value="cod"
                                       onchange="toggleFieldPembayaran('cod')">
                                <label class="btn btn-outline-success" for="opt_cod">COD</label>

                                <input type="radio" class="btn-check" name="metode_pembayaran"
                                       id="opt_termin" value="termin"
                                       onchange="toggleFieldPembayaran('termin')">
                                <label class="btn btn-outline-warning" for="opt_termin">Termin</label>

                                <input type="radio" class="btn-check" name="metode_pembayaran"
                                       id="opt_dp" value="dp"
                                       onchange="toggleFieldPembayaran('dp')">
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
                            <div class="mb-3">
                                <label class="form-label">Persentase DP</label>
                                <div class="input-group">
                                    <input type="number" name="persen_dp" id="inputPersenDP"
                                           class="form-control" min="1" max="99"
                                           placeholder="cth: 50" oninput="hitungNominalDP()">
                                    <span class="input-group-text">%</span>
                                </div>
                                <small class="text-muted" id="keteranganDP"></small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Estimasi Pelunasan</label>
                                <input type="date" name="tanggal_pelunasan" class="form-control">
                            </div>
                        </div>

                        <div class="mb-1">
                            <label class="form-label">Catatan <span class="text-muted">(opsional)</span></label>
                            <textarea name="catatan_pembayaran" class="form-control" rows="2"
                                      placeholder="Mis: Transfer ke BCA 1234567..."></textarea>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button"
                                class="btn btn-sm px-3"
                                style="background-color:#606060; color:#fff; border:none;"
                                data-bs-dismiss="modal">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-primary btn-sm px-3">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ====================================================== --}}
    {{-- SCRIPT                                                  --}}
    {{-- ====================================================== --}}
    <script>
        const dataPembayaran = @json($dataPembayaran);
        let totalAktif = 0;

        // ── Popup peringatan hapus terkunci (sudah ada pembayaran) ──
        function peringatanHapusTerkunci(kode) {
            document.getElementById('kodePeringatan').textContent = kode;
            new bootstrap.Modal(document.getElementById('modalPeringatanHapus')).show();
        }

        // ── Buka modal CATAT (form input) ──
        function bukaPembayaran(id, kode, total) {
            totalAktif = total;
            document.getElementById('formPembayaran').action = '/pembelian/' + id + '/catat-pembayaran';
            document.getElementById('infoPembelian').textContent = kode + ' · Total: Rp ' + Number(total).toLocaleString('id-ID');
            document.querySelectorAll('input[name=metode_pembayaran]').forEach(r => r.checked = false);
            document.getElementById('field_termin').classList.add('d-none');
            document.getElementById('field_dp').classList.add('d-none');
            new bootstrap.Modal(document.getElementById('modalPembayaran')).show();
        }

        function toggleFieldPembayaran(metode) {
            document.getElementById('field_termin').classList.toggle('d-none', metode !== 'termin');
            document.getElementById('field_dp').classList.toggle('d-none', metode !== 'dp');
        }

        function isiJatuhTempo(hari) {
            const tgl = new Date();
            tgl.setDate(tgl.getDate() + hari);
            document.querySelector('input[name=tanggal_jatuh_tempo]').value = tgl.toISOString().split('T')[0];
        }

        function hitungNominalDP() {
            const persen    = parseInt(document.getElementById('inputPersenDP').value) || 0;
            const nominalDP = Math.round(totalAktif * persen / 100);
            const sisa      = totalAktif - nominalDP;
            document.getElementById('keteranganDP').textContent =
                'DP = Rp ' + nominalDP.toLocaleString('id-ID') + ' · Sisa = Rp ' + sisa.toLocaleString('id-ID');
        }

        // ── Buka modal DETAIL (read-only, klik badge) ──
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

            ['row_jatuh_tempo', 'row_nominal_dp', 'row_sisa_dp', 'row_pelunasan', 'row_catatan']
                .forEach(rowId => document.getElementById(rowId).classList.add('d-none'));

            if (data.metode === 'termin') {
                document.getElementById('row_jatuh_tempo').classList.remove('d-none');
                document.getElementById('dp_jatuh_tempo').textContent = data.tanggal_jatuh_tempo ?? '-';
            }

            if (data.metode === 'dp') {
                const nominalDP = Math.round(total * data.persen_dp / 100);
                const sisa      = total - nominalDP;
                document.getElementById('row_nominal_dp').classList.remove('d-none');
                document.getElementById('row_sisa_dp').classList.remove('d-none');
                document.getElementById('dp_nominal').textContent = 'Rp ' + nominalDP.toLocaleString('id-ID') + ' (' + data.persen_dp + '%)';
                document.getElementById('dp_sisa').textContent   = 'Rp ' + sisa.toLocaleString('id-ID');
                if (data.tanggal_pelunasan) {
                    document.getElementById('row_pelunasan').classList.remove('d-none');
                    document.getElementById('dp_pelunasan').textContent = data.tanggal_pelunasan;
                }
            }

            if (data.catatan) {
                document.getElementById('row_catatan').classList.remove('d-none');
                document.getElementById('dp_catatan').textContent = data.catatan;
            }

            new bootstrap.Modal(document.getElementById('modalDetailPembayaran')).show();
        }
    </script>

</x-app-layout>