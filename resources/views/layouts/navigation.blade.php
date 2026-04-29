<div class="w-64 bg-white border-r min-h-screen">

    <!-- Logo -->
    <div class="p-4 border-b">
        <a href="{{ route('dashboard') }}">
            <x-application-logo class="h-10" />
        </a>
    </div>

    <!-- Menu -->
    <div class="flex flex-col p-4 space-y-2">

        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
            Dashboard
        </x-nav-link>

        <x-nav-link :href="route('kategori.index')" :active="request()->routeIs('kategori.*')">
            Kategori
        </x-nav-link>

        <x-nav-link :href="route('barang.index')" :active="request()->routeIs('barang.*')">
            Barang
        </x-nav-link>

        <x-nav-link :href="route('gudangs.index')" :active="request()->routeIs('gudangs.*')">
            Gudang
        </x-nav-link>

        <x-nav-link :href="route('karyawan.index')" :active="request()->routeIs('karyawan.*')">
            Karyawan
        </x-nav-link>

        <x-nav-link :href="route('customer.index')" :active="request()->routeIs('customer.*')">
            Customer
        </x-nav-link>

        <x-nav-link :href="route('coa.index')" :active="request()->routeIs('coa.*')">
            COA
        </x-nav-link>

        <x-nav-link :href="route('penggajian.index')" :active="request()->routeIs('penggajian.*')">
            Penggajian
        </x-nav-link>

        <x-nav-link :href="route('jurnal.index')" :active="request()->routeIs('jurnal.*')">
            Jurnal Umum
        </x-nav-link>

        <x-nav-link :href="route('pembelian.index')" :active="request()->routeIs('pembelian.*')">
            Pembelian
        </x-nav-link>

        <x-nav-link :href="route('stok-gudang.index')" :active="request()->routeIs('stok-gudang.*')">
            Stok Gudang
        </x-nav-link>
    </div>
</div>