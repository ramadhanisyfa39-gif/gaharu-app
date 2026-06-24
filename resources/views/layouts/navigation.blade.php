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

            {{-- REPORTS --}}
            @php
                $reportsActive = request()->routeIs('reports.*') || request()->routeIs('laporan.*');
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
                    <div class="submenu-divider">Persediaan</div>
                    <a href="{{ route('laporan.pembelian') }}"
                       class="{{ request()->routeIs('laporan.pembelian') ? 'active' : '' }}">
                        <i class="bi bi-cart3 me-2" style="font-size:12px;"></i>Pembelian
                    </a>
                    <a href="{{ route('laporan.stok-gudang') }}"
                       class="{{ request()->routeIs('laporan.stok-gudang') ? 'active' : '' }}">
                        <i class="bi bi-boxes me-2" style="font-size:12px;"></i>Posisi Stok Gudang
                    </a>
                    <a href="{{ route('laporan.pengeluaran-bahan-baku') }}"
                       class="{{ request()->routeIs('laporan.pengeluaran-bahan-baku') ? 'active' : '' }}">
                        <i class="bi bi-box-arrow-up me-2" style="font-size:12px;"></i>Pengeluaran Bahan Baku
                    </a>
                    <a href="{{ route('laporan.stock-opname') }}"
                       class="{{ request()->routeIs('laporan.stock-opname') ? 'active' : '' }}">
                        <i class="bi bi-clipboard-check me-2" style="font-size:12px;"></i>Stock Opname
                    </a>

                    <div class="submenu-divider" style="margin-top:4px;">Lainnya</div>
                    <a href="{{ route('reports.inventory') }}"
                       class="{{ request()->routeIs('reports.inventory') ? 'active' : '' }}">
                        <i class="bi bi-graph-up me-2" style="font-size:12px;"></i>Inventory (lama)
                    </a>
                    <a href="#">
                        <i class="bi bi-gear me-2" style="font-size:12px;"></i>Produksi
                    </a>
                    <a href="#">
                        <i class="bi bi-wallet2 me-2" style="font-size:12px;"></i>Keuangan
                    </a>
                </div>
            </div>

            {{-- MASTER DATA --}}
            @php
                $masterActive = request()->routeIs(['kategori.*', 'barang.*', 'suppliers.*', 'customer.*', 'gudangs.*', 'karyawan.*']);
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
                    <a href="{{ route('kategori.index') }}"  class="{{ request()->routeIs('kategori.*')  ? 'active' : '' }}">Kategori</a>
                    <a href="{{ route('barang.index') }}"    class="{{ request()->routeIs('barang.*')    ? 'active' : '' }}">Barang</a>
                    <a href="{{ route('suppliers.index') }}" class="{{ request()->routeIs('suppliers.*') ? 'active' : '' }}">Supplier</a>
                    <a href="{{ route('customer.index') }}"  class="{{ request()->routeIs('customer.*')  ? 'active' : '' }}">Customer</a>
                    <a href="{{ route('gudangs.index') }}"   class="{{ request()->routeIs('gudangs.*')   ? 'active' : '' }}">Gudang</a>
                    <a href="{{ route('karyawan.index') }}"  class="{{ request()->routeIs('karyawan.*')  ? 'active' : '' }}">Karyawan</a>
                </div>
            </div>

            {{-- PRODUKSI --}}
            @php
                $produksiActive = request()->routeIs(['pesanan.*', 'resep.*', 'wo.*', 'work-order.*']);
            @endphp
            <div class="menu-group {{ $produksiActive ? 'open' : '' }}">
                <div class="menu-parent d-flex align-items-center justify-content-between toggle-accordion">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-gear me-3 fs-5"></i>
                        <span>PRODUKSI</span>
                    </div>
                    <i class="bi {{ $produksiActive ? 'bi-chevron-down' : 'bi-chevron-right' }} chevron-icon fs-7"></i>
                </div>
                <div class="submenu">
                    <a href="{{ route('pesanan.index') }}" class="{{ request()->routeIs('pesanan.*') ? 'active' : '' }}">Pesanan B2B</a>
                    <a href="{{ route('resep.index') }}"   class="{{ request()->routeIs('resep.*')   ? 'active' : '' }}">Resep</a>
                    <a href="{{ route('wo.index') }}"      class="{{ request()->routeIs(['wo.*','work-order.*']) ? 'active' : '' }}">Permintaan Produksi</a>
                </div>
            </div>

            {{-- INVENTORY --}}
            @php
                $inventoryActive = request()->routeIs(['stock-opname.*', 'pembelian.*', 'stok-gudang.*', 'pengeluaran-bahan-baku.*']);
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
                    <a href="{{ route('stock-opname.index') }}"           class="{{ request()->routeIs('stock-opname.*')           ? 'active' : '' }}">Stock Opname</a>
                    <a href="{{ route('pembelian.index') }}"              class="{{ request()->routeIs('pembelian.*')              ? 'active' : '' }}">Pembelian</a>
                    <a href="{{ route('stok-gudang.index') }}"            class="{{ request()->routeIs('stok-gudang.*')            ? 'active' : '' }}">Stok Gudang</a>
                    <a href="{{ route('pengeluaran-bahan-baku.index') }}" class="{{ request()->routeIs('pengeluaran-bahan-baku.*') ? 'active' : '' }}">Pengeluaran Bahan Baku</a>
                </div>
            </div>

            {{-- KEUANGAN --}}
            @php
                $keuanganActive = request()->routeIs(['coa.*', 'jurnal.*', 'penggajian.*']);
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
                    <a href="{{ route('coa.index') }}"        class="{{ request()->routeIs('coa.*')        ? 'active' : '' }}">COA</a>
                    <a href="{{ route('jurnal.index') }}"     class="{{ request()->routeIs('jurnal.*')     ? 'active' : '' }}">Jurnal Umum</a>
                    <a href="{{ route('penggajian.index') }}" class="{{ request()->routeIs('penggajian.*') ? 'active' : '' }}">Penggajian</a>
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