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

    </div>
</div>