<div class="bg-white border-end"
     style="width:250px; min-height:100vh;">

    <!-- LOGO -->
    <div class="text-center py-4 border-bottom">

    <a href="{{ route('dashboard') }}">

        <x-application-logo
            class="mx-auto"
            style="height:70px;width:auto;" />

        <div class="mt-2 fw-bold text-dark">
            GAHARU ERP
        </div>

    </a>

</div>

    <!-- MENU -->
    <div class="p-3">

        <!-- MASTER DATA -->
        <div class="menu-group-title">
            MASTER DATA
        </div>

        <div class="menu-list">

            <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                Dashboard
            </x-nav-link>

            <x-nav-link :href="route('kategori.index')" :active="request()->routeIs('kategori.*')">
                Kategori
            </x-nav-link>

            <x-nav-link :href="route('barang.index')" :active="request()->routeIs('barang.*')">
                Barang
            </x-nav-link>

            <x-nav-link :href="route('suppliers.index')" :active="request()->routeIs('suppliers.*')">
                Supplier
            </x-nav-link>

            <x-nav-link :href="route('customer.index')" :active="request()->routeIs('customer.*')">
                Customer
            </x-nav-link>

            <x-nav-link :href="route('gudangs.index')" :active="request()->routeIs('gudangs.*')">
                Gudang
            </x-nav-link>

            <x-nav-link :href="route('karyawan.index')" :active="request()->routeIs('karyawan.*')">
                Karyawan
            </x-nav-link>

        </div>

        <!-- PRODUKSI -->
        <div class="menu-group-title">
            PRODUKSI
        </div>

        <div class="menu-list">

            <x-nav-link :href="route('resep.index')" :active="request()->routeIs('resep.*')">
                Resep
            </x-nav-link>

            <x-nav-link :href="route('wo.index')" :active="request()->routeIs('work_order.*')">
                Permintaan Produksi
            </x-nav-link>

            <x-nav-link :href="route('pesanan.index')" :active="request()->routeIs('pesanan.*')">
                Pesanan B2B
            </x-nav-link>


        </div>

        <!-- INVENTORY -->
        <div class="menu-group-title">
            INVENTORY
        </div>

        <div class="menu-list">

            <x-nav-link :href="route('pembelian.index')" :active="request()->routeIs('pembelian.*')">
                Pembelian
            </x-nav-link>

            <x-nav-link :href="route('stok-gudang.index')" :active="request()->routeIs('stok-gudang.*')">
                Stok Gudang
            </x-nav-link>

            <x-nav-link
                :href="route('pengeluaran-bahan-baku.index')"
                :active="request()->routeIs('pengeluaran-bahan-baku.*')">

                Pengeluaran Bahan Baku

            </x-nav-link>

        </div>

        <!-- KEUANGAN -->
        <div class="menu-group-title">
            KEUANGAN
        </div>

        <div class="menu-list">

            <x-nav-link :href="route('coa.index')" :active="request()->routeIs('coa.*')">
                COA
            </x-nav-link>

            <x-nav-link :href="route('jurnal.index')" :active="request()->routeIs('jurnal.*')">
                Jurnal Umum
            </x-nav-link>

            <x-nav-link :href="route('penggajian.index')" :active="request()->routeIs('penggajian.*')">
                Penggajian
            </x-nav-link>

        </div>

    </div>

</div>
<hr class="my-3">

<form method="POST" action="{{ route('logout') }}">
    @csrf

    <button
        type="submit"
        style="
            width:100%;
            text-align:left;
            padding:10px 15px;
            color:#dc3545;
            font-weight:600;
            border:none;
            background:none;
        ">
        Logout
    </button>
</form>