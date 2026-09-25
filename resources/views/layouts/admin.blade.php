<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>CarbonWise</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>

        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

        /* Root colors */

        :root {

            --accent-color: #15803d;
            --accent-hover: #166534;

            --sidebar-active: rgba(255, 255, 255, 0.18);
            --sidebar-hover: rgba(255, 255, 255, 0.10);
            --sidebar-glow: rgba(255, 255, 255, 0.30);

            --page-background: #f1f1ee;
            --card-background: #ffffff;
            --topbar-background: #ffffff;

            --text-primary: #111111;
            --text-secondary: #6b7280;
            --text-muted: #9ca3af;

            --border-color: #e5e7eb;

        }

        /* Global */

        * {

            box-sizing: border-box;

            font-family: 'Poppins', sans-serif;

        }

        html,
        body {

            margin: 0;
            padding: 0;

            width: 100%;
            min-height: 100%;

            font-family: 'Poppins', sans-serif;

        }

        body {

            overflow: hidden;

            background: var(--page-background);

        }

        button,
        input,
        select,
        textarea {

            font-family: 'Poppins', sans-serif;

        }

        /* Main layout */

        .carbon-layout {

            display: flex;

            width: 100%;
            height: 100vh;

            overflow: hidden;

        }

        /* Sidebar */

        .carbon-sidebar {

            width: 310px;
            min-width: 310px;
            max-width: 310px;

            height: 100vh;

            flex-shrink: 0;

            background: var(--accent-color);

            color: #ffffff;

            display: flex;
            flex-direction: column;

            overflow: hidden;

            position: relative;

            z-index: 100;

        }

        /* Sidebar logo */

        .carbon-sidebar-logo {

            height: 105px;
            min-height: 105px;

            padding: 20px 24px;

            border-bottom:
                1px solid
                rgba(255, 255, 255, 0.15);

            display: flex;
            align-items: center;

            gap: 15px;

        }

        .carbon-sidebar-logo img {

            width: 66px;
            height: 66px;

            object-fit: cover;

            border-radius: 50%;

            background: #ffffff;

            padding: 3px;

            flex-shrink: 0;

        }

        .carbon-sidebar-logo-text {

            font-size: 23px;
            line-height: 28px;

            font-weight: 700;

            color: #ffffff;

            white-space: nowrap;

        }

        /* Sidebar navigation */

        .carbon-sidebar-nav {

            flex: 1;

            padding: 22px 12px;

            overflow-y: auto;
            overflow-x: hidden;

        }

        .carbon-sidebar-nav::-webkit-scrollbar {

            width: 4px;

        }

        .carbon-sidebar-nav::-webkit-scrollbar-thumb {

            background:
                rgba(255, 255, 255, 0.20);

            border-radius: 10px;

        }

        .carbon-nav-link {

            position: relative;

            display: flex;

            align-items: center;

            gap: 18px;

            width: 100%;

            min-height: 64px;

            padding: 14px 20px;

            margin-bottom: 8px;

            border-radius: 9px;

            color:
                rgba(255, 255, 255, 0.97);

            text-decoration: none;

            font-size: 18px;

            line-height: 26px;

            font-weight: 500;

            letter-spacing: 0.05px;

            transition:
                background-color 0.18s ease,
                color 0.18s ease,
                box-shadow 0.18s ease,
                transform 0.18s ease;

        }

        .carbon-nav-link img {

            width: 28px;
            height: 28px;

            object-fit: contain;

            flex-shrink: 0;

            opacity: 0.96;

            transition:
                opacity 0.18s ease,
                transform 0.18s ease,
                filter 0.18s ease;

        }

        .carbon-nav-link span {

            white-space: nowrap;

        }

        .carbon-nav-link:hover {

            color: #ffffff;

            background:
                var(--sidebar-hover);

            text-decoration: none;

        }

        .carbon-nav-link:hover img {

            opacity: 1;

            transform: scale(1.06);

        }

        .carbon-nav-link.active {

            color: #ffffff;

            background:
                var(--sidebar-active);

            box-shadow:

                0 0 0 1px
                rgba(255, 255, 255, 0.08),

                0 5px 15px
                rgba(0, 0, 0, 0.12),

                0 0 20px
                var(--sidebar-glow);

        }

        .carbon-nav-link.active::before {

            content: "";

            position: absolute;

            left: 0;

            top: 5px;
            bottom: 5px;

            width: 5px;

            border-radius:
                0 6px 6px 0;

            background: #ffffff;

            box-shadow:

                0 0 8px
                rgba(255, 255, 255, 0.95),

                0 0 16px
                rgba(255, 255, 255, 0.60);

        }

        .carbon-nav-link.active img {

            opacity: 1;

            transform: scale(1.08);

            filter: brightness(1.12);

        }

        /* Sidebar footer */

        .carbon-sidebar-footer {

            padding:
                16px
                16px
                24px;

            border-top:
                1px solid
                rgba(255, 255, 255, 0.20);

            flex-shrink: 0;

        }

        /* Logout */

        .carbon-logout-button {

            width: 100%;

            min-height: 70px;

            padding:
                14px
                20px;

            border: 0;

            border-radius: 9px;

            background:
                transparent;

            color: #ffffff;

            font-family:
                'Poppins',
                sans-serif;

            font-size: 19px;

            line-height: 28px;

            font-weight: 500;

            cursor: pointer;

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 16px;

            transition:
                background-color 0.18s ease,
                box-shadow 0.18s ease,
                transform 0.18s ease;

        }

        .carbon-logout-button:hover {

            background:
                rgba(255, 255, 255, 0.10);

            box-shadow:
                0 0 14px
                rgba(255, 255, 255, 0.10);

        }

        .carbon-logout-button:active {

            transform:
                scale(0.98);

        }

        .carbon-logout-icon {

            width: 27px;
            height: 27px;

            flex-shrink: 0;

        }

        /* Main area */

        .carbon-main {

            flex: 1;

            min-width: 0;

            height: 100vh;

            overflow-y: auto;
            overflow-x: hidden;

            background:
                var(--page-background);

        }

        /* Top bar */

        .carbon-topbar {

            min-height: 120px;
            height: 120px;

            background:
                var(--topbar-background);

            border-bottom:
                1px solid
                var(--border-color);

            box-shadow:
                0 3px 9px
                rgba(17, 24, 39, 0.08);

            padding:
                18px
                30px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 28px;

            position: sticky;

            top: 0;

            z-index: 90;

        }

        /* Top bar left */

        .carbon-topbar-left {

            display: flex;

            align-items: center;

            gap: 18px;

            min-width: 0;

        }

        .carbon-menu-button {

            width: 46px;
            height: 46px;

            border: 0;

            background:
                transparent;

            color: #374151;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 8px;

            cursor: pointer;

            font-size: 28px;

            line-height: 1;

            flex-shrink: 0;

            transition:
                background-color 0.18s ease,
                color 0.18s ease;

        }

        .carbon-menu-button:hover {

            background:
                #f3f4f6;

            color:
                #111827;

        }

        .carbon-page-heading {

            min-width: 0;

        }

        .carbon-page-title {

            margin: 0;

            font-size: 34px;

            line-height: 42px;

            font-weight: 700;

            color:
                var(--text-primary);

            letter-spacing:
                -0.45px;

        }

        .carbon-page-subtitle {

            margin:
                5px 0 0;

            font-size: 16px;

            line-height: 22px;

            font-weight: 400;

            color:
                var(--text-secondary);

        }

        /* Top bar right */

        .carbon-topbar-right {

            display: flex;

            align-items: center;

            gap: 24px;

            flex-shrink: 0;

        }

        .carbon-topbar-controls {

            display: flex;

            align-items: center;

            gap: 13px;

        }

        .carbon-topbar-select {

            height: 48px;

            min-width: 125px;

            padding:
                0 17px;

            border:
                1px solid
                #dfe3e8;

            border-radius: 8px;

            background:
                #ffffff;

            color:
                #374151;

            font-family:
                'Poppins',
                sans-serif;

            font-size: 14px;

            line-height: 20px;

            font-weight: 500;

            outline: none;

            cursor: pointer;

        }

        .carbon-topbar-select:focus {

            border-color:
                #86efac;

            box-shadow:
                0 0 0 2px
                rgba(34, 197, 94, 0.10);

        }

        /* Export button */

        .carbon-export-button {

            height: 48px;

            padding:
                0 17px;

            border-radius: 8px;

            border:
                1px solid
                #15803d;

            background:
                #ffffff;

            color:
                #15803d;

            font-family:
                'Poppins',
                sans-serif;

            font-size: 14px;

            line-height: 20px;

            font-weight: 500;

            display: flex;

            align-items: center;

            gap: 8px;

            cursor: pointer;

            transition:
                background-color 0.18s ease,
                box-shadow 0.18s ease;

        }

        .carbon-export-button:hover {

            background:
                #f0fdf4;

            box-shadow:
                0 2px 7px
                rgba(21, 128, 61, 0.12);

        }

        /* Top bar divider */

        .carbon-topbar-divider {

            height: 50px;

            border-left:
                1px solid
                var(--border-color);

        }

        /* Admin profile */

        .carbon-admin-profile {

            display: flex;

            align-items: center;

            gap: 12px;

        }

        .carbon-admin-avatar {

            width: 52px;
            height: 52px;

            border-radius: 50%;

            background:
                #ecfdf3;

            display: flex;

            align-items: center;

            justify-content: center;

            overflow: hidden;

            flex-shrink: 0;

            border:
                1px solid
                #d1fae5;

        }

        .carbon-admin-avatar img {

            width: 33px;
            height: 33px;

            object-fit: contain;

        }

        .carbon-admin-name {

            font-size: 16px;

            line-height: 22px;

            font-weight: 700;

            color:
                #15803d;

            white-space: nowrap;

        }

        .carbon-admin-role {

            margin-top: 2px;

            font-size: 13px;

            line-height: 19px;

            font-weight: 400;

            color:
                #9ca3af;

            white-space: nowrap;

        }

        /* Main content */

        .carbon-content {

            padding:
                28px;

        }

        @media (min-width: 1280px) {

            .carbon-content {

                padding:
                    32px 40px;

            }

        }

        @media (min-width: 1536px) {

            .carbon-content {

                padding:
                    34px 48px;

            }

        }

        /* Responsive */

        @media (max-width: 1100px) {

            .carbon-sidebar {

                width: 250px;
                min-width: 250px;
                max-width: 250px;

            }

            .carbon-page-title {

                font-size: 28px;

                line-height: 36px;

            }

            .carbon-page-subtitle {

                font-size: 14px;

            }

            .carbon-nav-link {

                font-size: 16px;

            }

            .carbon-topbar {

                padding-left: 22px;

                padding-right: 22px;

            }

        }

        @media (max-width: 850px) {

            .carbon-sidebar {

                width: 230px;
                min-width: 230px;
                max-width: 230px;

            }

            .carbon-sidebar-logo {

                padding:
                    16px;

            }

            .carbon-sidebar-logo img {

                width: 55px;
                height: 55px;

            }

            .carbon-sidebar-logo-text {

                font-size: 19px;

            }

            .carbon-nav-link {

                padding:
                    12px 14px;

                gap: 12px;

                font-size: 15px;

            }

            .carbon-nav-link img {

                width: 24px;
                height: 24px;

            }

            .carbon-topbar {

                min-height: 100px;
                height: 100px;

                padding:
                    15px 18px;

            }

            .carbon-page-title {

                font-size: 25px;

                line-height: 32px;

            }

            .carbon-page-subtitle {

                font-size: 13px;

                line-height: 19px;

            }

            .carbon-topbar-right {

                gap: 12px;

            }

            .carbon-topbar-select {

                min-width: 95px;

                height: 42px;

                font-size: 12px;

            }

            .carbon-admin-name {

                font-size: 13px;

            }

            .carbon-admin-role {

                font-size: 11px;

            }

        }

    </style>

</head>

<body>

<div class="carbon-layout">

    <!-- Sidebar -->

    <aside class="carbon-sidebar">

        <!-- Logo -->

        <div class="carbon-sidebar-logo">

            <img
                src="{{ asset('images/carbonwise-logo.png') }}"
                alt="CarbonWise Logo"
            >

            <div class="carbon-sidebar-logo-text">
                CarbonWise
            </div>

        </div>

        <!-- Navigation -->

        <nav class="carbon-sidebar-nav">

            <!-- Overview -->

            <a
                href="/dashboard"
                class="carbon-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
            >

                <img
                    src="{{ asset('icons/dashboard.png') }}"
                    alt="Overview"
                >

                <span>
                    Overview
                </span>

            </a>

            <!-- User Management -->

            <a
                href="{{ route('admin.users') }}"
                class="carbon-nav-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}"
            >

                <img
                    src="{{ asset('icons/user.png') }}"
                    alt="User Management"
                >

                <span>
                    User Management
                </span>

            </a>

            <!-- Emissions Overview -->

            <a
                href="{{ route('admin.emissions') }}"
                class="carbon-nav-link {{ request()->routeIs('admin.emissions*') ? 'active' : '' }}"
            >

                <img
                    src="{{ asset('icons/emissions.png') }}"
                    alt="Emissions Overview"
                >

                <span>
                    Emissions Overview
                </span>

            </a>

            <!-- Analytics -->

            <a
                href="{{ route('admin.analytics') }}"
                class="carbon-nav-link {{ request()->routeIs('admin.analytics*') ? 'active' : '' }}"
            >

                <img
                    src="{{ asset('icons/analytics.png') }}"
                    alt="Analytics & Reports"
                >

                <span>
                    Analytics &amp; Reports
                </span>

            </a>

            <!-- Forecasting -->

            <a
                href="{{ route('admin.forecasting') }}"
                class="carbon-nav-link {{ request()->routeIs('admin.forecasting*') ? 'active' : '' }}"
            >

                <img
                    src="{{ asset('icons/forecast.png') }}"
                    alt="Forecasting"
                >

                <span>
                    Forecasting
                </span>

            </a>

            <!-- Mitigation -->

            <a
                href="{{ route('admin.mitigation') }}"
                class="carbon-nav-link {{ request()->routeIs('admin.mitigation*') ? 'active' : '' }}"
            >

                <img
                    src="{{ asset('icons/mitigation.png') }}"
                    alt="Mitigation Strategies"
                >

                <span>
                    Mitigation Strategies
                </span>

            </a>

            <!-- Alerts -->

            <a
                href="{{ route('admin.alerts') }}"
                class="carbon-nav-link {{ request()->routeIs('admin.alerts*') ? 'active' : '' }}"
            >

                <img
                    src="{{ asset('icons/alerts.png') }}"
                    alt="Alerts & Notifications"
                >

                <span>
                    Alerts &amp; Notifications
                </span>

            </a>

        </nav>

        <!-- Sidebar footer -->

        <div class="carbon-sidebar-footer">

            <!-- Logout -->

            <form
                method="POST"
                action="{{ route('logout') }}"
            >

                @csrf

                <button
                    type="submit"
                    class="carbon-logout-button"
                >

                    <svg
                        class="carbon-logout-icon"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="1.7"
                    >

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M8 5H5.5A1.5 1.5 0 004 6.5v11A1.5 1.5 0 005.5 19H8"
                        />

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M13 8l4 4-4 4"
                        />

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M17 12H8"
                        />

                    </svg>

                    <span>
                        Log Out
                    </span>

                </button>

            </form>

        </div>

    </aside>

    <!-- Main -->

    <main class="carbon-main">

        <!-- Top bar -->

        <div class="carbon-topbar">

            <!-- Top bar left -->

            <div class="carbon-topbar-left">

                <button
                    type="button"
                    class="carbon-menu-button"
                    aria-label="Open menu"
                >
                    ☰
                </button>

                <div class="carbon-page-heading">

                    <h2 class="carbon-page-title">

                        @yield(
                            'page-title',
                            'Overview'
                        )

                    </h2>

                    <p class="carbon-page-subtitle">

                        @yield(
                            'page-subtitle',
                            'Welcome Back, ' . Auth::user()->name . '!'
                        )

                    </p>

                </div>

            </div>

            <!-- Top bar right -->

            <div class="carbon-topbar-right">

                @if (
                    request()->routeIs('dashboard') ||
                    request()->routeIs('admin.analytics')
                )

                    <div class="carbon-topbar-controls">

                        <!-- Date filters -->

                        <form
                            method="GET"
                            class="flex items-center gap-3"
                        >

                            <!-- Year -->

                            <select
                                name="year"
                                onchange="this.form.submit()"
                                class="carbon-topbar-select"
                            >

                                @for($y = 2021; $y <= 2026; $y++)

                                    <option
                                        value="{{ $y }}"
                                        {{ request('year', 2026) == $y ? 'selected' : '' }}
                                    >

                                        {{ $y }}

                                    </option>

                                @endfor

                            </select>

                            <!-- Month -->

                            <select
                                name="month"
                                onchange="this.form.submit()"
                                class="carbon-topbar-select"
                                style="min-width: 145px;"
                            >

                                <option
                                    value="all"
                                    {{ request('month') == 'all' || !request('month') ? 'selected' : '' }}
                                >
                                    All Months
                                </option>

                                @foreach([
                                    1 => 'January',
                                    2 => 'February',
                                    3 => 'March',
                                    4 => 'April',
                                    5 => 'May',
                                    6 => 'June',
                                    7 => 'July',
                                    8 => 'August',
                                    9 => 'September',
                                    10 => 'October',
                                    11 => 'November',
                                    12 => 'December'
                                ] as $num => $name)

                                    <option
                                        value="{{ $num }}"
                                        {{ request('month') == $num ? 'selected' : '' }}
                                    >

                                        {{ $name }}

                                    </option>

                                @endforeach

                            </select>

                        </form>

                        <!-- Export -->

                        @if(request()->routeIs('admin.analytics'))

                            <div class="relative group">

                                <button
                                    type="button"
                                    class="carbon-export-button"
                                >

                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="w-5 h-5"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >

                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4"
                                        />

                                    </svg>

                                    Export Report

                                </button>

                                <div
                                    class="
                                        hidden
                                        group-hover:block
                                        absolute
                                        right-0
                                        mt-2
                                        w-48
                                        bg-white
                                        border
                                        border-gray-200
                                        rounded-lg
                                        shadow-lg
                                        z-50
                                    "
                                >

                                    <a
                                        href="{{ route('admin.analytics.export.pdf') }}"
                                        class="
                                            block
                                            px-5
                                            py-3
                                            text-sm
                                            text-gray-700
                                            hover:bg-gray-100
                                        "
                                    >
                                        PDF Report
                                    </a>

                                    <a
                                        href="{{ route('admin.analytics.export.excel') }}"
                                        class="
                                            block
                                            px-5
                                            py-3
                                            text-sm
                                            text-gray-700
                                            hover:bg-gray-100
                                        "
                                    >
                                        Excel Report
                                    </a>

                                </div>

                            </div>

                        @endif

                    </div>

                @endif

                <!-- Divider -->

                <div class="carbon-topbar-divider"></div>

                <!-- Admin profile -->

                <div class="carbon-admin-profile">

                    <div class="carbon-admin-avatar">

                        <img
                            src="{{ asset('icons/avatar.png') }}"
                            alt="SDO Lipa"
                        >

                    </div>

                    <div>

                        <div class="carbon-admin-name">

                            {{ Auth::user()->name }}

                        </div>

                        <div class="carbon-admin-role">

                            Administrator

                        </div>

                    </div>

                </div>

            </div>

        </div>

        <!-- Page content -->

        <div class="carbon-content">

            @yield('content')

        </div>

    </main>

</div>

<!-- Libraries -->

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Page-specific scripts -->

@stack('scripts')

</body>

</html>