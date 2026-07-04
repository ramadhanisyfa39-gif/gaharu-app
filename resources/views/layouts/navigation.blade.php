<div class="sidebar d-flex flex-column justify-content-between">
    <div>
        <div class="sidebar-logo">
            <a href="{{ route('dashboard') }}">
                <x-application-logo
                    class="mx-auto"
                    style="height:60px; width:auto;" />
            </a>
        </div>

        <div class="sidebar-menu">

            {{-- DASHBOARD --}}
            <div class="menu-group">
                <a href="{{ route('dashboard') }}"
                    class="menu-parent text-decoration-none d-flex align-items-center justify-content-start {{ request()->routeIs('dashboard') ? 'active-menu-root' : '' }}"
                    style="color: {{ request()->routeIs('dashboard') ? '#d88656' : '#ffffff' }};">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-house-door me-3 fs-5"></i>
                        <span>DASHBOARD</span>
                    </div>
                </a>
            </div>

            {{-- MASTER DATA --}}
            @php
            $masterActive = request()->routeIs([
            'kategori.*',
            'barang.*',
            'suppliers.*',
            'customer.*',
            'gudangs.*',
            'karyawan.*',
            'resep.*',
            'harga.*'
            ]);
            @endphp

            <div class="menu-group {{ $masterActive ? 'open' : '' }}">
                <div class="menu-parent d-flex align-items-center justify-content-between toggle-accordion">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-database me-3 fs-5"></i>
                        <span>MASTER DATA</span>
                    </div>
                    <i class="bi {{ $masterActive ? 'bi-chevron-down' : 'bi-chevron-right' }} chevron-icon fs-7"></i>
                </div>

                <div class="submenu">
                    <a href="{{ route('kategori.index') }}"
                        class="{{ request()->routeIs('kategori.*') ? 'active' : '' }}">
                        Kategori
                    </a>

                    <a href="{{ route('barang.index') }}"
                        class="{{ request()->routeIs('barang.*') ? 'active' : '' }}">
                        Barang
                    </a>

                    <a href="{{ route('resep.index') }}"
                        class="{{ request()->routeIs('resep.*') ? 'active' : '' }}">
                        Resep
                    </a>

                    <a href="{{ route('harga.index') }}"
                        class="{{ request()->routeIs('harga.*') ? 'active' : '' }}">
                        Harga Barang POS
                    </a>

                    <a href="{{ route('suppliers.index') }}"
                        class="{{ request()->routeIs('suppliers.*') ? 'active' : '' }}">
                        Supplier
                    </a>

                    <a href="{{ route('customer.index') }}"
                        class="{{ request()->routeIs('customer.*') ? 'active' : '' }}">
                        Customer
                    </a>

                    <a href="{{ route('gudangs.index') }}"
                        class="{{ request()->routeIs('gudangs.*') ? 'active' : '' }}">
                        Gudang
                    </a>

                    <a href="{{ route('karyawan.index') }}"
                        class="{{ request()->routeIs('karyawan.*') ? 'active' : '' }}">
                        Karyawan
                    </a>

                    <div class="submenu">
                        <a href="{{ route('coa.index') }}"
                            class="{{ request()->routeIs('coa.*') ? 'active' : '' }}">
                            COA
                        </a>
                    </div>
                </div>

                {{-- TRANSAKSI --}}
                @php
                $transaksiActive = request()->routeIs([
                'penjualan_pos.*',
                'pesanan.*',
                'pengiriman.*',
                'wo.*',
                'work-order.*',
                'produksi.*'
                ]);
                @endphp

                <div class="menu-group {{ $transaksiActive ? 'open' : '' }}">
                    <div class="menu-parent d-flex align-items-center justify-content-between toggle-accordion">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-arrow-left-right me-3 fs-5"></i>
                            <span>TRANSAKSI</span>
                        </div>
                        <i class="bi {{ $transaksiActive ? 'bi-chevron-down' : 'bi-chevron-right' }} chevron-icon fs-7"></i>
                    </div>

                    <div class="submenu">
                        <a href="{{ route('penjualan_pos.index') }}"
                            class="{{ request()->routeIs('penjualan_pos.*') ? 'active' : '' }}">
                            Penjualan POS
                        </a>

                        <a href="{{ route('pesanan.index') }}"
                            class="{{ request()->routeIs('pesanan.*') ? 'active' : '' }}">
                            Pesanan B2B
                        </a>

                        <a href="{{ route('wo.index') }}"
                            class="{{ request()->routeIs(['wo.*', 'work-order.*']) ? 'active' : '' }}">
                            Permintaan Produksi
                        </a>

                        <a href="{{ route('produksi.index') }}"
                            class="{{ request()->routeIs('produksi.*') ? 'active' : '' }}">
                            Produksi
                        </a>

                        <a href="{{ route('pengiriman.index') }}"
                            class="{{ request()->routeIs('pengiriman.*') ? 'active' : '' }}">
                            Pengiriman
                        </a>

                        <a href="{{ route('penggajian.index') }}"
                            class="{{ request()->routeIs('penggajian.*') ? 'active' : '' }}">
                            Penggajian
                        </a>
                    </div>
                </div>

                {{-- LAPORAN TRANSAKSI --}}
                @php
                $laporanTransaksiActive = request()->routeIs([
                'penjualan_pos.laporan',
                'laporan.penjualan',
                'laporan.hpp',
                'laporan.rekapitulasi'
                ]);
                @endphp

                <div class="menu-group {{ $laporanTransaksiActive ? 'open' : '' }}">
                    <div class="menu-parent d-flex align-items-center justify-content-between toggle-accordion">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-clipboard-data me-3 fs-5"></i>
                            <span>LAPORAN TRANSAKSI</span>
                        </div>
                        <i class="bi {{ $laporanTransaksiActive ? 'bi-chevron-down' : 'bi-chevron-right' }} chevron-icon fs-7"></i>
                    </div>

                    <div class="submenu">
                        <a href="{{ route('penjualan_pos.laporan') }}"
                            class="{{ request()->routeIs('penjualan_pos.laporan') ? 'active' : '' }}">
                            Laporan Penjualan POS
                        </a>

                        <a href="{{ route('laporan.penjualan') }}"
                            class="{{ request()->routeIs('laporan.penjualan') ? 'active' : '' }}">
                            Laporan Penjualan B2B
                        </a>

                        <a href="{{ route('laporan.hpp') }}"
                            class="{{ request()->routeIs('laporan.hpp') ? 'active' : '' }}">
                            Laporan HPP
                        </a>

                        <a href="{{ route('laporan.rekapitulasi') }}"
                            class="{{ request()->routeIs('laporan.rekapitulasi') ? 'active' : '' }}">
                            Laporan Produksi
                        </a>
                    </div>
                </div>

                {{-- INVENTORY --}}
                @php
                $inventoryActive = request()->routeIs([
                'stock-opname.*',
                'pembelian.*',
                'stok-gudang.*',
                'pengeluaran-bahan-baku.*'
                ]);
                @endphp

                <div class="menu-group {{ $inventoryActive ? 'open' : '' }}">
                    <div class="menu-parent d-flex align-items-center justify-content-between toggle-accordion">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-box-seam me-3 fs-5"></i>
                            <span>INVENTORY</span>
                        </div>
                        <i class="bi {{ $inventoryActive ? 'bi-chevron-down' : 'bi-chevron-right' }} chevron-icon fs-7"></i>
                    </div>

                    <div class="submenu">
                        <a href="{{ route('stock-opname.index') }}"
                            class="{{ request()->routeIs('stock-opname.*') ? 'active' : '' }}">
                            Stock Opname
                        </a>

                        <a href="{{ route('pembelian.index') }}"
                            class="{{ request()->routeIs('pembelian.*') ? 'active' : '' }}">
                            Pembelian
                        </a>

                        <a href="{{ route('stok-gudang.index') }}"
                            class="{{ request()->routeIs('stok-gudang.*') ? 'active' : '' }}">
                            Stok Gudang
                        </a>

                        <a href="{{ route('pengeluaran-bahan-baku.index') }}"
                            class="{{ request()->routeIs('pengeluaran-bahan-baku.*') ? 'active' : '' }}">
                            Pengeluaran Bahan Baku
                        </a>
                    </div>
                </div>

                {{-- KEUANGAN --}}
                @php
                $keuanganActive = request()->routeIs([
                'coa.*',
                'jurnal.*',
                'penggajian.*',
                'adjustment.*',
                'closing.*'
                ]);
                @endphp

                <div class="menu-group {{ $keuanganActive ? 'open' : '' }}">
                    <div class="menu-parent d-flex align-items-center justify-content-between toggle-accordion">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-wallet2 me-3 fs-5"></i>
                            <span>KEUANGAN</span>
                        </div>
                        <i class="bi {{ $keuanganActive ? 'bi-chevron-down' : 'bi-chevron-right' }} chevron-icon fs-7"></i>
                    </div>
                    <div class="submenu">

                        <a href="{{ route('jurnal.index') }}"
                            class="{{ request()->routeIs('jurnal.*') ? 'active' : '' }}">
                            Jurnal Umum
                        </a>

                        <a href="{{ route('adjustment.index') }}"
                            class="{{ request()->routeIs('adjustment.*') ? 'active' : '' }}">
                            Jurnal Penyesuaian
                        </a>

                        <a href="{{ route('jurnal-pembelian.index') }}"
                            class="{{ request()->routeIs('jurnal-pembelian.*') ? 'active' : '' }}">
                            Jurnal Pembelian
                        </a>

                        <a href="{{ route('jurnal-penjualanpos.index') }}"
                            class="{{ request()->routeIs('jurnal-penjualanpos.*') ? 'active' : '' }}">
                            Jurnal Penjualan POS
                        </a>

                        <a href="{{ route('jurnal-penjualanb2b.index') }}"
                            class="{{ request()->routeIs('jurnal-penjualanb2b.*') ? 'active' : '' }}">
                            Jurnal Penjualan B2B
                        </a>

                        <a href="{{ route('bukupembantu-uangmuka.index') }}"
                            class="{{ request()->routeIs('bukupembantu-uangmuka.*') ? 'active' : '' }}">
                            Buku Pembantu Uang Muka Pembelian
                        </a>
                    </div>

                </div>
            </div>

        </div>
    </div>

    {{-- LOGOUT --}}
    <div style="padding:24px 0; border-top:1px solid #4a4a4a;">
        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="logout-btn d-flex align-items-center justify-content-center">
                <i class="bi bi-box-arrow-left me-2"></i>
                Logout
            </button>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.toggle-accordion').forEach(button => {
            button.addEventListener('click', () => {
                const group = button.parentElement;
                const chevron = button.querySelector('.chevron-icon');

                group.classList.toggle('open');

                chevron.classList.toggle(
                    'bi-chevron-right',
                    !group.classList.contains('open')
                );

                chevron.classList.toggle(
                    'bi-chevron-down',
                    group.classList.contains('open')
                );
            });
        });
    });
</script>