<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    @vite([
    'resources/css/app.css',
    'resources/js/app.js'
    ])

    <style>
        /* ── GLOBAL ── */
        body {
            background: #fbf9f6;
            margin: 0;
            font-family: 'Figtree', sans-serif;
        }

        main {
            padding: 24px 32px !important;
        }

        /* ── CARD & TABLE ── */
        .card {
            border: 1px solid #eadfd4;
            border-radius: 16px;
            background: white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, .03);
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background: #5a3416;
            color: white;
            border: none;
        }

        /* ── SIDEBAR ── */
        .sidebar {
            width: 260px;
            min-height: 100vh;
            background: #606060;
            /* abu-abu */
            border-right: none;
            color: #ffffff;
            flex-shrink: 0;
        }

        .sidebar-logo {
            text-align: center;
            padding: 24px 10px;
            border-bottom: 1px solid #4a4a4a;
        }

        .sidebar-logo a {
            text-decoration: none;
        }

        /* ── MENU PARENT (main menu) ── */
        .sidebar-menu {
            padding: 16px 0;
        }

        .menu-group {
            position: relative;
            margin-bottom: 2px;
        }

        .menu-parent {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 20px;
            /* rata kiri, tidak center */
            color: #ffffff;
            font-weight: 700;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: color .2s;
            text-decoration: none;
        }

        .menu-parent:hover,
        .menu-parent.active-menu-root {
            color: #d88656;
            /* terracota saat hover / aktif */
        }

        /* chevron ikut warna parent */
        .menu-parent .chevron-icon {
            color: inherit;
        }

        /* ── SUBMENU (rincian menu) ── */
        .submenu {
            display: none;
            flex-direction: column;
            list-style: none;
            padding-left: 0;
            margin: 0 0 8px 0;
            background: #545454;
            /* sedikit lebih gelap dari sidebar */
        }

        .submenu-divider {
            padding: 8px 24px 4px 24px;
            font-size: 10px;
            font-weight: 700;
            color: #a08060;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            margin-top: 4px;
            pointer-events: none;
            user-select: none;
        }
        
        .submenu-divider:first-child {
            margin-top: 0;
        }

        .menu-group.open .submenu {
            display: flex;
        }

        /* label divider di dalam submenu */
        .submenu-divider {
            padding: 6px 20px 2px 44px;
            font-size: 10px;
            font-weight: 700;
            color: #a0a0a0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* link submenu — menjorok lebih ke kanan dari parent */
        .submenu a {
            display: block;
            padding: 9px 20px 9px 44px;
            /* indent 44px vs parent 20px */
            text-decoration: none;
            color: #e0e0e0;
            font-size: 13.5px;
            font-weight: 400;
            transition: color .2s, background .2s;
        }

        .submenu a:hover {
            color: #d88656;
            background: rgba(255, 255, 255, .06);
        }

        .submenu a.active {
            color: #d88656;
            font-weight: 600;
            background: rgba(216, 134, 86, .12);
            border-left: 3px solid #d88656;
            padding-left: 41px;
            /* kompensasi border 3px */
        }

        /* ── LOGOUT BTN ── */
        .logout-btn {
            width: calc(100% - 40px);
            margin: 0 20px;
            border: none;
            background: #d88656;
            color: white;
            border-radius: 8px;
            padding: 10px;
            font-weight: 600;
            transition: background .3s;
            cursor: pointer;
        }

        .logout-btn:hover {
            background: #c87443;
        }

        /* ── LAYOUT & CONTENT ── */
        .content-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .topbar {
            background: white;
            padding: 16px 32px;
            border-bottom: 1px solid #eadfd4;
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }

        .page-header-container {
            padding: 32px 32px 0 32px;
        }

        .page-header-container h2 {
            color: #111;
            font-size: 26px;
            font-weight: 800;
            margin: 0;
        }

        /* ── BUTTON & BADGE ── */
        .btn-primary {
            background: #d88656 !important;
            border: none !important;
            border-radius: 8px;
        }

        .btn-primary:hover {
            background: #c87443 !important;
        }

        .badge.bg-success {
            background: #d88656 !important;
        }
    </style>
</head>

<body>

    <div class="d-flex min-vh-100">

        @include('layouts.navigation')

        <div class="content-wrapper">

            <header class="topbar">
                <div class="d-flex align-items-center">
                    <div class="text-end me-3">
                        <div class="fw-bold text-dark text-capitalize" style="font-size: 14px;">
                            {{ Auth::user()->nama }}
                        </div>
                        <div class="text-muted text-uppercase font-monospace" style="font-size: 11px; letter-spacing: 0.5px;">
                            {{ Auth::user()->role->nama ?? Auth::user()->role->name ?? 'STAFF' }}
                        </div>
                    </div>

                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="btn border-0 p-0 rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                                <i class="bi bi-person-fill text-secondary fs-4"></i>
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
                                    Logout
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                </div>
            </header>

            @isset($header)
            <div class="page-header-container">
                {{ $header }}
            </div>
            @endisset

            <main>
                {{ $slot }}
            </main>

        </div>
    </div>

</body>

</html>