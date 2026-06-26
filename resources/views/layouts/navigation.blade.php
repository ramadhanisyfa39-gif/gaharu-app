<div class="d-flex">
    <div class="bg-white border-end visual-sidebar" style="width:260px; min-height:100vh; display: flex; flex-direction: column; justify-content: space-between;">

        <div>
            <div class="text-center py-4 border-bottom">
                <a href="{{ route('dashboard') }}" class="text-decoration-none">
                    <x-application-logo class="mx-auto" style="height:70px; width:auto;" />
                    <div class="mt-2 fw-bold text-dark tracking-wide">GAHARU ERP</div>
                </a>
            </div>

            <div class="p-3" style="max-height: calc(100vh - 200px); overflow-y: auto;">

                <div class="menu-group-title text-muted fw-bold small mb-2 text-uppercase">Master Data</div>
                <div class="menu-list d-flex flex-column gap-1 mb-4">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Dashboard</x-nav-link>
                    <x-nav-link :href="route('kategori.index')" :active="request()->routeIs('kategori.*')">Kategori</x-nav-link>
                    <x-nav-link :href="route('barang.index')" :active="request()->routeIs('barang.*')">Barang</x-nav-link>
                    <x-nav-link :href="route('suppliers.index')" :active="request()->routeIs('suppliers.*')">Supplier</x-nav-link>
                    <x-nav-link :href="route('customer.index')" :active="request()->routeIs('customer.*')">Customer</x-nav-link>
                    <x-nav-link :href="route('gudangs.index')" :active="request()->routeIs('gudangs.*')">Gudang</x-nav-link>
                    <x-nav-link :href="route('karyawan.index')" :active="request()->routeIs('karyawan.*')">Karyawan</x-nav-link>
                </div>

                <div class="menu-group-title text-muted fw-bold small mb-2 text-uppercase">Produksi</div>
                <div class="menu-list d-flex flex-column gap-1 mb-4">
                    <x-nav-link :href="route('resep.index')" :active="request()->routeIs('resep.*')">Resep</x-nav-link>
                    <x-nav-link :href="route('wo.index')" :active="request()->routeIs('wo.*')">Permintaan Produksi</x-nav-link>
                    <x-nav-link :href="route('pesanan.index')" :active="request()->routeIs('pesanan.*')">Pesanan B2B</x-nav-link>
                </div>

                <div class="menu-group-title text-muted fw-bold small mb-2 text-uppercase">Inventory</div>
                <div class="menu-list d-flex flex-column gap-1 mb-4">
                    <x-nav-link :href="route('pembelian.index')" :active="request()->routeIs('pembelian.*')">Pembelian</x-nav-link>
                    <x-nav-link :href="route('stok-gudang.index')" :active="request()->routeIs('stok-gudang.*')">Stok Gudang</x-nav-link>
                    <x-nav-link :href="route('pengeluaran-bahan-baku.index')" :active="request()->routeIs('pengeluaran-bahan-baku.*')">Pengeluaran Bahan Baku</x-nav-link>
                </div>

                <div class="menu-group-title text-muted fw-bold small mb-2 text-uppercase">Keuangan</div>
                <div class="menu-list d-flex flex-column gap-1 mb-4">
                    <x-nav-link :href="route('coa.index')" :active="request()->routeIs('coa.*')">COA</x-nav-link>
                    <x-nav-link :href="route('jurnal.index')" :active="request()->routeIs('jurnal.*')">Jurnal Umum</x-nav-link>
                    <x-nav-link :href="route('adjustment.index')" :active="request()->routeIs('adjustment.*')">Jurnal Penyesuaian</x-nav-link>
                    <x-nav-link :href="route('laporan.jurnal-pembelian.index')" :active="request()->routeIs('jurnal-pembelian.*')">Jurnal Pembelian</x-nav-link>
                    <x-nav-link :href="route('laporan.jurnal-penjualanpos.index')" :active="request()->routeIs('jurnal-penjualanpos.*')">Jurnal Penjualan POS</x-nav-link>
                    <x-nav-link :href="route('laporan.jurnal-penjualanb2b.index')" :active="request()->routeIs('jurnal-penjualanb2b.*')">Jurnal Penjualan B2B</x-nav-link>
                    <x-nav-link :href="route('laporan.bukupembantu-utang.index')" :active="request()->routeIs('bukupembantu-utang.*')">Buku Pembantu Utang</x-nav-link>
                    <x-nav-link :href="route('penggajian.index')" :active="request()->routeIs('penggajian.*')">Penggajian</x-nav-link>
                    <x-nav-link :href="route('closing.index')" :active="request()->routeIs('closing.*')">Penutupan Periode</x-nav-link>
                </div>

                <div class="menu-group-title text-muted fw-bold small mb-2 text-uppercase">Laporan Keuangan</div>
                <div class="menu-list d-flex flex-column gap-1 mb-4">
                    <x-nav-link :href="route('laporan.laba-rugi.index')" :active="request()->routeIs('laporan.laba-rugi.*')">Laporan Laba Rugi</x-nav-link>
                    <x-nav-link :href="route('laporan.neraca.index')" :active="request()->routeIs('laporan.neraca.*')">Neraca</x-nav-link>
                    <x-nav-link :href="route('laporan.arus-kas.index')" :active="request()->routeIs('laporan.arus-kas.*')">Laporan Arus Kas</x-nav-link>
                    <x-nav-link :href="route('laporan.buku-besar.index')" :active="request()->routeIs('laporan.buku-besar.*')">Buku Besar</x-nav-link>
                    <x-nav-link :href="route('laporan.neraca-saldo.index')" :active="request()->routeIs('laporan.neraca-saldo.*')">Neraca Saldo</x-nav-link>
                </div>

            </div>
        </div>

        <div class="p-3 border-top bg-light">
            <form method="POST" action="{{ route('logout') }}" class="m-0">
                @csrf
                <button type="submit" class="btn btn-link text-danger fw-bold w-100 text-start p-2 text-decoration-none d-flex align-items-center gap-2"
                    style="border: none; background: none;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-box-arrow-right" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0 -1 0v2z" />
                        <path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708l3-3z" />
                    </svg>
                    Keluar Sistem (Logout)
                </button>
            </form>
        </div>

    </div>
</div>