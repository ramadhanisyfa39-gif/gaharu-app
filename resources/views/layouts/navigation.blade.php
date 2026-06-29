<div class="sidebar d-flex flex-column justify-content-between">
    <div>
        <div class="sidebar-logo">
            <a href="{{ route('dashboard') }}">
                <x-application-logo class="mx-auto" style="height:60px; width:auto;" />
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
                    'kategori.*','barang.*','suppliers.*',
                    'customer.*','gudangs.*','karyawan.*',
                    'resep.*','harga.*'
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
                    <a href="{{ route('gudangs.index') }}"   class="{{ request()->routeIs('gudangs.*')   ? 'active' : '' }}">Warehouse</a>
                    <a href="{{ route('suppliers.index') }}" class="{{ request()->routeIs('suppliers.*') ? 'active' : '' }}">Supplier</a>
                    <a href="{{ route('kategori.index') }}"  class="{{ request()->routeIs('kategori.*')  ? 'active' : '' }}">Category</a>
                    <a href="{{ route('barang.index') }}"    class="{{ request()->routeIs('barang.*')    ? 'active' : '' }}">Items</a>
                    <a href="{{ route('resep.index') }}"     class="{{ request()->routeIs('resep.*')     ? 'active' : '' }}">Recipe</a>
                    <a href="{{ route('harga.index') }}"     class="{{ request()->routeIs('harga.*')     ? 'active' : '' }}">POS Price</a>
                    <a href="{{ route('customer.index') }}"  class="{{ request()->routeIs('customer.*')  ? 'active' : '' }}">Customer</a>
                    <a href="{{ route('karyawan.index') }}"  class="{{ request()->routeIs('karyawan.*')  ? 'active' : '' }}">Employee</a>
                </div>
            </div>

            {{-- OPERATIONS (gabungan Transaksi + Inventory) --}}
            @php
                $opsActive = request()->routeIs([
                    'penjualan_pos.*','pesanan.*','pengiriman.*',
                    'wo.*','work-order.*','produksi.*',
                    'stock-opname.*','pembelian.*',
                    'stok-gudang.*','pengeluaran-bahan-baku.*',
                    'penggajian.*'
                ]);
            @endphp
            <div class="menu-group {{ $opsActive ? 'open' : '' }}">
                <div class="menu-parent d-flex align-items-center justify-content-between toggle-accordion">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-layers me-3 fs-5"></i>
                        <span>OPERATIONS</span>
                    </div>
                    <i class="bi {{ $opsActive ? 'bi-chevron-down' : 'bi-chevron-right' }} chevron-icon fs-7"></i>
                </div>
                <div class="submenu">

                    {{-- INVENTORY header --}}
                    <div class="submenu-divider">INVENTORY</div>
                    <a href="{{ route('pembelian.index') }}"
                       class="{{ request()->routeIs('pembelian.*') ? 'active' : '' }}">Purchase</a>
                    <a href="{{ route('pengeluaran-bahan-baku.index') }}"
                       class="{{ request()->routeIs('pengeluaran-bahan-baku.*') ? 'active' : '' }}">Material Output</a>
                    <a href="{{ route('stok-gudang.index') }}"
                       class="{{ request()->routeIs('stok-gudang.*') ? 'active' : '' }}">Warehouse Stock</a>
                    <a href="{{ route('stock-opname.index') }}"
                       class="{{ request()->routeIs('stock-opname.*') ? 'active' : '' }}">Stock Opname</a>

                    {{-- SALES header --}}
                    <div class="submenu-divider">SALES</div>
                    <a href="{{ route('penjualan_pos.index') }}"
                       class="{{ request()->routeIs('penjualan_pos.*') ? 'active' : '' }}">POS Sales</a>
                    <a href="{{ route('pesanan.index') }}"
                       class="{{ request()->routeIs('pesanan.*') ? 'active' : '' }}">B2B Orders</a>

                    {{-- PRODUCTION header --}}
                    <div class="submenu-divider">PRODUCTION</div>
                    <a href="{{ route('wo.index') }}"
                       class="{{ request()->routeIs(['wo.*','work-order.*']) ? 'active' : '' }}">Production Request</a>
                    <a href="{{ route('produksi.index') }}"
                       class="{{ request()->routeIs('produksi.*') ? 'active' : '' }}">Production</a>
                    <a href="{{ route('pengiriman.index') }}"
                       class="{{ request()->routeIs('pengiriman.*') ? 'active' : '' }}">Delivery</a>

                    {{-- OTHERS header --}}
                    <div class="submenu-divider">OTHERS</div>
                    <a href="{{ route('penggajian.index') }}"
                       class="{{ request()->routeIs('penggajian.*') ? 'active' : '' }}">Payroll</a>

                </div>
            </div>

            {{-- REPORTS (semua laporan jadi satu) --}}
            @php
                $reportsActive = request()->routeIs([
                    'reports.*','laporan.*',
                    'penjualan_pos.laporan',
                ]);
            @endphp
            <div class="menu-group {{ $reportsActive ? 'open' : '' }}">
                <div class="menu-parent d-flex align-items-center justify-content-between toggle-accordion">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-bar-chart-line me-3 fs-5"></i>
                        <span>REPORTS</span>
                    </div>
                    <i class="bi {{ $reportsActive ? 'bi-chevron-down' : 'bi-chevron-right' }} chevron-icon fs-7"></i>
                </div>
                <div class="submenu">

                    {{-- INVENTORY reports --}}
                    <div class="submenu-divider">INVENTORY</div>
                    <a href="{{ route('laporan.pembelian') }}"
                       class="{{ request()->routeIs('laporan.pembelian') ? 'active' : '' }}">
                        <i class="bi bi-cart3 me-2" style="font-size:12px;"></i>Purchase
                    </a>
                    <a href="{{ route('laporan.stok-gudang') }}"
                       class="{{ request()->routeIs('laporan.stok-gudang') ? 'active' : '' }}">
                        <i class="bi bi-boxes me-2" style="font-size:12px;"></i>Stock Position
                    </a>
                    <a href="{{ route('laporan.pengeluaran-bahan-baku') }}"
                       class="{{ request()->routeIs('laporan.pengeluaran-bahan-baku') ? 'active' : '' }}">
                        <i class="bi bi-box-arrow-up me-2" style="font-size:12px;"></i>Raw Material Output
                    </a>
                    <a href="{{ route('laporan.stock-opname') }}"
                       class="{{ request()->routeIs('laporan.stock-opname') ? 'active' : '' }}">
                        <i class="bi bi-clipboard-check me-2" style="font-size:12px;"></i>Stock Opname
                    </a>

                    {{-- SALES reports --}}
                    <div class="submenu-divider">SALES</div>
                    <a href="{{ route('penjualan_pos.laporan') }}"
                       class="{{ request()->routeIs('penjualan_pos.laporan') ? 'active' : '' }}">
                        <i class="bi bi-receipt me-2" style="font-size:12px;"></i>POS Sales Report
                    </a>
                    <a href="{{ route('laporan.penjualan') }}"
                       class="{{ request()->routeIs('laporan.penjualan') ? 'active' : '' }}">
                        <i class="bi bi-graph-up-arrow me-2" style="font-size:12px;"></i>B2B Sales Report
                    </a>
                    <a href="{{ route('laporan.hpp') }}"
                       class="{{ request()->routeIs('laporan.hpp') ? 'active' : '' }}">
                        <i class="bi bi-calculator me-2" style="font-size:12px;"></i>HPP Report
                    </a>

                    {{-- PRODUCTION reports --}}
                    <div class="submenu-divider">PRODUCTION</div>
                    <a href="{{ route('laporan.rekapitulasi') }}"
                       class="{{ request()->routeIs('laporan.rekapitulasi') ? 'active' : '' }}">
                        <i class="bi bi-gear me-2" style="font-size:12px;"></i>Production Report
                    </a>

                </div>
            </div>

            {{-- FINANCE --}}
            @php
                $financeActive = request()->routeIs([
                    'coa.*','jurnal.*','adjustment.*','closing.*'
                ]);
            @endphp
            <div class="menu-group {{ $financeActive ? 'open' : '' }}">
                <div class="menu-parent d-flex align-items-center justify-content-between toggle-accordion">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-wallet2 me-3 fs-5"></i>
                        <span>FINANCE</span>
                    </div>
                    <i class="bi {{ $financeActive ? 'bi-chevron-down' : 'bi-chevron-right' }} chevron-icon fs-7"></i>
                </div>
                <div class="submenu">
                    <a href="{{ route('coa.index') }}"
                       class="{{ request()->routeIs('coa.*') ? 'active' : '' }}">Chart of Accounts</a>
                    <a href="{{ route('jurnal.index') }}"
                       class="{{ request()->routeIs('jurnal.*') ? 'active' : '' }}">General Journal</a>
                    <a href="{{ route('adjustment.index') }}"
                       class="{{ request()->routeIs('adjustment.*') ? 'active' : '' }}">Adjustment</a>
                    <a href="{{ route('closing.index') }}"
                       class="{{ request()->routeIs('closing.*') ? 'active' : '' }}">Period Closing</a>
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
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.toggle-accordion').forEach(button => {
            button.addEventListener('click', () => {
                const group   = button.parentElement;
                const chevron = button.querySelector('.chevron-icon');
                group.classList.toggle('open');
                chevron.classList.toggle('bi-chevron-right', !group.classList.contains('open'));
                chevron.classList.toggle('bi-chevron-down',   group.classList.contains('open'));
            });
        });
    });
</script>