<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Gaharu') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:300,400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Figtree', sans-serif;
            min-height: 100vh;
            display: flex;
            background: #f7f6f4;
            color: #1a1a1a;
        }

        /* ── LEFT PANEL ── */
        .auth-left {
            width: 45%;
            min-height: 100vh;
            background: #dd7045;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 48px 52px;
            position: relative;
            overflow: hidden;
        }

        .auth-left::before {
            content: '';
            position: absolute;
            top: -120px;
            right: -120px;
            width: 420px;
            height: 420px;
            border-radius: 50%;
            background: rgba(255,255,255,0.07);
            pointer-events: none;
        }

        .auth-left::after {
            content: '';
            position: absolute;
            bottom: -80px;
            left: -80px;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(255,255,255,0.05);
            pointer-events: none;
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            position: relative;
            z-index: 1;
        }

        .brand-logo img,
        .brand-logo svg {
            height: 44px;
            width: auto;
            filter: brightness(0) invert(1);
        }

        .brand-name {
            font-size: 22px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: 0.5px;
        }

        .left-content {
            position: relative;
            z-index: 1;
        }

        .left-tagline {
            font-size: 34px;
            font-weight: 700;
            color: #ffffff;
            line-height: 1.25;
            margin-bottom: 16px;
            letter-spacing: -0.3px;
        }

        .left-sub {
            font-size: 15px;
            color: rgba(255,255,255,0.75);
            line-height: 1.65;
            max-width: 320px;
        }

        .left-stats {
            display: flex;
            gap: 32px;
            position: relative;
            z-index: 1;
        }

        .stat-item {
            display: flex;
            flex-direction: column;
        }

        .stat-number {
            font-size: 28px;
            font-weight: 700;
            color: #ffffff;
            line-height: 1;
        }

        .stat-label {
            font-size: 12px;
            color: rgba(255,255,255,0.65);
            margin-top: 4px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }

        .stat-divider {
            width: 1px;
            background: rgba(255,255,255,0.2);
            align-self: stretch;
        }

        /* ── RIGHT PANEL ── */
        .auth-right {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px 40px;
        }

        .auth-card {
            width: 100%;
            max-width: 400px;
        }

        .auth-title {
            font-size: 26px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 6px;
            letter-spacing: -0.3px;
        }

        .auth-subtitle {
            font-size: 14px;
            color: #606060;
            margin-bottom: 36px;
        }

        /* ── FORM ELEMENTS ── */
        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 7px;
            letter-spacing: 0.1px;
        }

        .form-input {
            width: 100%;
            height: 46px;
            padding: 0 14px;
            border: 1.5px solid #e0dbd5;
            border-radius: 10px;
            font-size: 14px;
            font-family: 'Figtree', sans-serif;
            color: #1a1a1a;
            background: #ffffff;
            transition: border-color 0.18s, box-shadow 0.18s;
            outline: none;
        }

        .form-input:focus {
            border-color: #dd7045;
            box-shadow: 0 0 0 3px rgba(221,112,69,0.12);
        }

        .form-input::placeholder {
            color: #b0a99f;
        }

        .form-error {
            font-size: 12px;
            color: #c0392b;
            margin-top: 5px;
        }

        /* ── CHECKBOX ── */
        .check-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 28px;
        }

        .check-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #606060;
            cursor: pointer;
        }

        .check-label input[type="checkbox"] {
            width: 16px;
            height: 16px;
            border-radius: 4px;
            accent-color: #dd7045;
            cursor: pointer;
        }

        .forgot-link {
            font-size: 13px;
            color: #dd7045;
            text-decoration: none;
            font-weight: 500;
            transition: opacity 0.15s;
        }

        .forgot-link:hover { opacity: 0.75; }

        /* ── BUTTON ── */
        .btn-primary {
            width: 100%;
            height: 48px;
            background: #dd7045;
            color: #ffffff;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            font-family: 'Figtree', sans-serif;
            cursor: pointer;
            transition: background 0.18s, transform 0.1s;
            letter-spacing: 0.2px;
        }

        .btn-primary:hover { background: #c45e33; }
        .btn-primary:active { transform: scale(0.99); }

        .btn-secondary {
            width: 100%;
            height: 48px;
            background: transparent;
            color: #606060;
            border: 1.5px solid #e0dbd5;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            font-family: 'Figtree', sans-serif;
            cursor: pointer;
            transition: border-color 0.18s, color 0.18s;
            margin-top: 12px;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-secondary:hover {
            border-color: #dd7045;
            color: #dd7045;
        }

        /* ── STATUS ALERT ── */
        .alert-status {
            background: #f0faf4;
            border: 1px solid #86efac;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 13px;
            color: #166534;
            margin-bottom: 20px;
        }

        /* ── RESPONSIVE ── */
        @media (max-width: 768px) {
            body { flex-direction: column; }
            .auth-left {
                width: 100%;
                min-height: auto;
                padding: 32px 28px 36px;
            }
            .left-tagline { font-size: 24px; }
            .left-stats { display: none; }
            .auth-right { padding: 36px 24px; }
        }
    </style>
</head>
<body>

    {{-- ── LEFT PANEL ── --}}
    <div class="auth-left">
        <a href="{{ route('dashboard') }}" class="brand-logo">
            <x-application-logo style="height:100px; width:auto; filter:brightness(0) invert(1);" />
        </a>

        <div class="left-content">
            <div class="left-tagline">
                Inventory, Purchase, &amp; Finance<br>Management System
            </div>
            <p class="left-sub">
                Kelola penjualan, persediaan, pembelian, dan laporan keuangan Gaharu dalam satu platform terintegrasi.
            </p>
        </div>

        <div class="left-stats">
            <div class="stat-item">
                <span class="stat-number">Real‑time</span>
                <span class="stat-label">Stock tracking</span>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
                <span class="stat-number">FIFO</span>
                <span class="stat-label">Cost method</span>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
                <span class="stat-number">ERP</span>
                <span class="stat-label">Integrated</span>
            </div>
        </div>
    </div>

    {{-- ── RIGHT PANEL ── --}}
    <div class="auth-right">
        <div class="auth-card">
            {{ $slot }}
        </div>
    </div>

</body>
</html>