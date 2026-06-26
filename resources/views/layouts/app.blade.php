<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<style>
    body {
        background: #f4f6f9;
    }

    .card {
        border: none;
        border-radius: 12px;
    }

    .table {
        margin-bottom: 0;
    }

    .table thead th {
        background: #1f2937;
        color: white;
        border: none;
    }

    .border-end {
        border-right: 1px solid #e5e7eb !important;
    }

    main {
        padding: 24px !important;
    }

    .menu-group-title {
        background: #4b5563;
        /* abu tua */
        color: white;
        font-size: 12px;
        font-weight: 700;
        padding: 10px 14px;
        border-radius: 6px;
        margin-top: 18px;
        margin-bottom: 8px;
        letter-spacing: 1px;
        text-transform: uppercase;
    }

    .menu-list {
        display: flex;
        flex-direction: column;
        gap: 2px;
        padding-left: 12px;
        /* menjorok ke kanan */
        margin-top: 6px;
    }

    .menu-list a {
        display: block;
        padding: 8px 12px;
        border-radius: 6px;
        text-decoration: none;
        color: #374151;
        font-size: 14px;
        transition: all .2s ease;
    }

    .menu-list a:hover {
        background: #f3f4f6;
        color: #111827;
    }

    .menu-list a.active {
        background: #e5e7eb;
        border-left: 4px solid #374151;
        font-weight: 600;
    }
</style>

</style>

</style>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased">
    <div class="flex min-h-screen bg-gray-100">

        <!-- SIDEBAR -->
        @include('layouts.navigation')

        <!-- CONTENT -->
        <div class="flex-fill d-flex flex-column">

            <!-- TOPBAR -->
            <div class="bg-white shadow-sm px-4 py-3 d-flex justify-content-end align-items-center">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="text-sm text-gray-600">
                            {{ Auth::user()->name }}
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            Profile
                        </x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                onclick="event.preventDefault(); this.closest('form').submit();">
                                Log Out
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- MAIN CONTENT -->
            <main class="p-6">
                {{ $slot }}
            </main>

        </div>
    </div>
</body>

</html>