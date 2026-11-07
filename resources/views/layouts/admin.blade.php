<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>CarbonWise</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

        /* =========================================
           THEME VARIABLES
        ========================================= */

        :root {
            --accent-color: #15803d;
            --accent-hover: #166534;
            --accent-green: #16a34a;
            --neon-green: #39FF88;

            --sidebar-active: rgba(57, 255, 136, 0.14);
            --sidebar-hover: rgba(57, 255, 136, 0.07);
            --sidebar-glow: rgba(57, 255, 136, 0.12);

            --page-background: #f1f1ee;
            --card-background: #ffffff;
            --topbar-background: #ffffff;

            --text-primary: #111111;
            --text-secondary: #6b7280;
            --text-muted: #9ca3af;

            --border-color: #e5e7eb;
            --input-bg: #ffffff;
            --input-border: #d1d5db;
        }

        [data-theme="dark"] {
            /* Keep the sidebar visibly forest-green in dark mode. */
            --accent-color: #121A16;
            --accent-hover: #162E24;
            --accent-green: #52B788;
            --neon-green: #39FF88;

            ---sidebar-active: rgba(57, 255, 136, 0.13);
            --sidebar-hover: rgba(57, 255, 136, 0.07);
            --sidebar-glow: rgba(57, 255, 136, 0.12);

            --page-background: #0A0F0D;
            --card-background: #121A16;
            --topbar-background: #121A16;

            --text-primary: #F3F4F6;
            --text-secondary: #9CA3AF;
            --text-muted: #9CA3AF;

            --border-color: #1B3A2B;
            --input-bg: #18241F;
            --input-border: #2D6A4F;
        }

        /* =========================================
           GLOBAL
        ========================================= */

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

        /* =========================================
           MAIN LAYOUT
        ========================================= */

        .carbon-layout {
            display: flex;
            width: 100%;
            height: 100vh;
            overflow: hidden;
        }

        /* =========================================
           SIDEBAR
        ========================================= */

        .carbon-sidebar {
            width: 280px;
            min-width: 280px;
            max-width: 280px;
            height: 100vh;
            flex-shrink: 0;
            background: var(--accent-color);
            color: #ffffff;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            position: relative;
            z-index: 100;
            transition: background-color 0.2s ease;
        }

        /* Logo area: aligned with desktop top bar */

        [data-theme="dark"] .carbon-sidebar-logo {
            border-bottom-color: rgba(82, 183, 136, 0.28);
        }

        [data-theme="dark"] .carbon-sidebar-footer {
            border-top-color: rgba(82, 183, 136, 0.28);
        }

        .carbon-sidebar-logo {
            height: 100px;
            min-height: 100px;
            padding: 14px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
        }

        .carbon-sidebar-logo img {
            width: 52px;
            height: 52px;
            object-fit: cover;
            border-radius: 50%;
            background: #ffffff;
            padding: 3px;
            flex-shrink: 0;
        }

        .carbon-sidebar-logo-text {
            font-size: 20px;
            line-height: 26px;
            font-weight: 700;
            color: #ffffff;
            white-space: nowrap;
        }

        /* Sidebar navigation */

        .carbon-sidebar-nav {
            flex: 1;
            padding: 18px 12px;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .carbon-sidebar-nav::-webkit-scrollbar {
            width: 4px;
        }

        .carbon-sidebar-nav::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.20);
            border-radius: 10px;
        }

        .carbon-nav-link {
            position: relative;
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
            min-height: 48px;
            padding: 11px 16px;
            margin-bottom: 5px;
            border-radius: 8px;
            color: rgba(255, 255, 255, 0.97);
            text-decoration: none;
            font-size: 14px;
            line-height: 21px;
            font-weight: 500;
            letter-spacing: 0;
            transition:
                background-color 0.18s ease,
                color 0.18s ease,
                box-shadow 0.18s ease,
                transform 0.18s ease;
        }

        .carbon-nav-link img {
            width: 22px;
            height: 22px;
            object-fit: contain;
            flex-shrink: 0;
            opacity: 0.96;
            transition:
                opacity 0.18s ease,
                transform 0.18s ease,
                filter 0.18s ease;
        }

        .carbon-nav-link span {
            min-width: 0;
            white-space: normal;
        }

        /* Disable extra pseudo-elements on navigation labels */

        .carbon-nav-link span::before,
        .carbon-nav-link span::after,
        .carbon-nav-link::after {
            content: none !important;
            display: none !important;
        }

        .carbon-nav-link:hover {
            color: #ffffff;
            background: var(--sidebar-hover);
            text-decoration: none;
        }

        .carbon-nav-link:hover img {
            opacity: 1;
            transform: scale(1.04);
        }

        /* Neon-green active highlight */

        .carbon-nav-link.active {
            color: #ffffff;
            background: var(--sidebar-active);
            padding-left: 50px;
            gap: 0;
            box-shadow:
                inset 0 0 0 1px rgba(57, 255, 136, 0.45),
                0 0 12px var(--sidebar-glow);
        }

        /* Remove the active item's menu icon.
           Keep the text aligned with the other menu labels. */

        .carbon-nav-link.active img {
            display: none !important;
        }

        /* Green indicator only: no extra window icon */

        .carbon-nav-link.active::before {
            content: "";
            display: block;
            position: absolute;
            left: 0;
            top: 7px;
            bottom: 7px;
            width: 3px;
            border-radius: 0 4px 4px 0;
            background: var(--neon-green);
            box-shadow: 0 0 7px rgba(57, 255, 136, 0.85);
        }

        /* =========================================
           SIDEBAR FOOTER
        ========================================= */

        .carbon-sidebar-footer {
            padding: 12px 12px 16px;
            border-top: 1px solid rgba(255, 255, 255, 0.20);
            flex-shrink: 0;
        }

        .carbon-sidebar-footer form {
            width: 100%;
            margin: 0;
        }

        /* Theme toggle */

        .carbon-theme-toggle {
            width: 100%;
            min-height: 48px;
            padding: 12px 16px;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            border: 0;
            border-radius: 8px;
            background: transparent;
            color: rgba(255, 255, 255, 0.97);
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            line-height: 21px;
            font-weight: 500;
            cursor: pointer;
            white-space: nowrap;
            text-align: center;
            transition: background-color 0.18s ease, color 0.18s ease;
        }

        .carbon-theme-toggle:hover {
            background: var(--sidebar-hover);
            color: #ffffff;
        }

        .carbon-theme-toggle svg {
            width: 22px;
            height: 22px;
            flex-shrink: 0;
        }

        [data-theme="dark"] .carbon-theme-toggle {
            color: #F3F4F6;
        }

        /* Logout */

        .carbon-logout-button {
            width: 100%;
            min-height: 48px;
            padding: 12px 16px;
            border: 0;
            border-radius: 8px;
            background: transparent;
            color: #ffffff;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            line-height: 21px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            transition:
                background-color 0.18s ease,
                box-shadow 0.18s ease,
                transform 0.18s ease;
        }

        .carbon-logout-button:hover {
            background: rgba(57, 255, 136, 0.08);
            box-shadow: 0 0 12px rgba(57, 255, 136, 0.08);
        }

        .carbon-logout-button:active {
            transform: scale(0.98);
        }

        .carbon-logout-icon {
            width: 22px;
            height: 22px;
            flex-shrink: 0;
        }

        .carbon-theme-toggle,
        .carbon-logout-button {
            box-sizing: border-box;
        }

        /* =========================================
           MAIN CONTENT AREA
        ========================================= */

        .carbon-main {
            flex: 1;
            min-width: 0;
            height: 100vh;
            overflow-y: auto;
            overflow-x: hidden;
            background: var(--page-background);
        }

        /* =========================================
           TOP BAR
        ========================================= */

        .carbon-topbar {
            min-height: 100px;
            height: 100px;
            background: var(--topbar-background);
            border-bottom: 1px solid var(--border-color);
            box-shadow: 0 3px 9px rgba(17, 24, 39, 0.08);
            padding: 18px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            position: sticky;
            top: 0;
            z-index: 90;
        }

        .carbon-topbar-left {
            display: flex;
            align-items: center;
            gap: 16px;
            min-width: 0;
        }

        .carbon-menu-button {
            width: 42px;
            height: 42px;
            border: 0;
            background: transparent;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            cursor: pointer;
            font-size: 26px;
            line-height: 1;
            flex-shrink: 0;
            transition: background-color 0.18s ease, color 0.18s ease;
        }

        .carbon-menu-button:hover {
            background: var(--sidebar-hover);
            color: var(--text-primary);
        }

        .carbon-page-heading {
            min-width: 0;
        }

        .carbon-page-title {
            margin: 0;
            font-size: 28px;
            line-height: 36px;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.35px;
        }

        .carbon-page-subtitle {
            margin: 4px 0 0;
            font-size: 14px;
            line-height: 20px;
            font-weight: 400;
            color: var(--text-secondary);
        }

        /* Top bar right */

        .carbon-topbar-right {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-shrink: 0;
        }

        .carbon-topbar-controls {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .carbon-topbar-select {
            height: 42px;
            min-width: 110px;
            padding: 0 14px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: var(--card-background);
            color: var(--text-primary);
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            line-height: 20px;
            font-weight: 500;
            outline: none;
            cursor: pointer;
        }

        .carbon-topbar-select:focus {
            border-color: #52B788;
            box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.10);
        }

        [data-theme="dark"] .carbon-topbar-select {
            border-color: var(--input-border);
        }

        [data-theme="dark"] .carbon-topbar select option {
            background: var(--card-background);
            color: var(--text-primary);
        }

        /* =========================================
           EXPORT BUTTON AND DROPDOWN
        ========================================= */

        .carbon-export-button {
            height: 42px;
            padding: 0 15px;
            border-radius: 8px;
            border: 1px solid #15803d;
            background: var(--card-background);
            color: var(--text-primary) !important;
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            line-height: 20px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: background-color 0.18s ease, box-shadow 0.18s ease;
        }

        .carbon-export-button:hover {
            background: #f0fdf4;
            color: #166534 !important;
            box-shadow: 0 2px 7px rgba(21, 128, 61, 0.12);
        }

        [data-theme="dark"] .carbon-export-button {
            color: #52B788 !important;
            border-color: #2D6A4F;
            background: #121A16;
        }

        [data-theme="dark"] .carbon-export-button:hover {
            color: #F3F4F6 !important;
            background: #162E24;
        }

        .carbon-export-button svg {
            color: inherit;
            stroke: currentColor;
        }

        .carbon-export-dropdown {
            position: relative;
            display: inline-flex;
            align-items: center;
            z-index: 500;
            isolation: isolate;
        }

        .carbon-export-dropdown::after {
            content: "";
            position: absolute;
            top: 100%;
            right: 0;
            width: 100%;
            height: 12px;
        }

        .carbon-export-menu {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            min-width: 200px;
            padding: 6px;
            margin: 0;
            background: #ffffff;
            color: #1f2937;
            border: 1px solid #d1d5db;
            border-radius: 12px;
            box-shadow: 0 14px 32px rgba(0, 0, 0, 0.20);
            overflow: hidden;
            z-index: 501;
            display: none;
        }

        .carbon-export-dropdown:hover .carbon-export-menu,
        .carbon-export-dropdown:focus-within .carbon-export-menu,
        .carbon-export-dropdown.is-open .carbon-export-menu {
            display: block;
        }

        .carbon-export-menu a {
            display: flex;
            align-items: center;
            min-height: 42px;
            width: 100%;
            padding: 10px 13px;
            border-radius: 8px;
            background: transparent;
            color: #1f2937 !important;
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            line-height: 1.45;
            font-weight: 500;
            text-decoration: none;
            white-space: nowrap;
            cursor: pointer;
            transition: background-color 0.15s ease, color 0.15s ease;
        }

        .carbon-export-menu a + a {
            margin-top: 3px;
        }

        .carbon-export-menu a:hover,
        .carbon-export-menu a:focus {
            background: #f0fdf4;
            color: #166534 !important;
            outline: none;
        }

        [data-theme="dark"] .carbon-export-menu {
            background: #121A16;
            color: #F3F4F6;
            border-color: #2D6A4F;
            box-shadow: 0 14px 32px rgba(0, 0, 0, 0.48);
        }

        [data-theme="dark"] .carbon-export-menu a {
            background: transparent;
            color: #F3F4F6 !important;
        }

        [data-theme="dark"] .carbon-export-menu a:hover,
        [data-theme="dark"] .carbon-export-menu a:focus {
            background: #1B3A2B;
            color: #A7F3D0 !important;
        }

        .carbon-export-button:focus-visible {
            outline: 2px solid #52B788;
            outline-offset: 3px;
        }

        /* =========================================
           ADMIN PROFILE
        ========================================= */

        .carbon-topbar-divider {
            height: 44px;
            border-left: 1px solid var(--border-color);
        }

        .carbon-admin-profile {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .carbon-admin-avatar {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            background: #ecfdf3;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex-shrink: 0;
            border: 1px solid #d1fae5;
        }

        .carbon-admin-avatar img {
            width: 30px;
            height: 30px;
            object-fit: contain;
        }

        .carbon-admin-name {
            font-size: 14px;
            line-height: 20px;
            font-weight: 700;
            color: var(--accent-green);
            white-space: nowrap;
        }

        .carbon-admin-role {
            margin-top: 2px;
            font-size: 12px;
            line-height: 17px;
            font-weight: 400;
            color: var(--text-muted);
            white-space: nowrap;
        }

        [data-theme="dark"] .carbon-admin-avatar {
            background: #162E24;
            border-color: #1B3A2B;
        }

        /* =========================================
           PAGE CONTENT
        ========================================= */

        .carbon-content {
            padding: 26px;
        }

        @media (min-width: 1280px) {
            .carbon-content {
                padding: 30px 36px;
            }
        }

        @media (min-width: 1536px) {
            .carbon-content {
                padding: 32px 42px;
            }
        }

        /* =========================================
           RESPONSIVE: TABLETS
        ========================================= */

        @media (max-width: 1100px) {
            .carbon-sidebar {
                width: 250px;
                min-width: 250px;
                max-width: 250px;
            }

            .carbon-page-title {
                font-size: 25px;
                line-height: 32px;
            }

            .carbon-page-subtitle {
                font-size: 13px;
            }

            .carbon-topbar {
                padding-left: 20px;
                padding-right: 20px;
                gap: 16px;
            }

            .carbon-topbar-right {
                gap: 14px;
            }
        }

        @media (max-width: 850px) {
            .carbon-sidebar {
                width: 230px;
                min-width: 230px;
                max-width: 230px;
            }

            /* Match the 90px tablet top bar */

            .carbon-sidebar-logo {
                height: 90px;
                min-height: 90px;
                padding: 14px;
            }

            .carbon-sidebar-logo img {
                width: 46px;
                height: 46px;
            }

            .carbon-sidebar-logo-text {
                font-size: 18px;
            }

            .carbon-nav-link {
                padding: 10px 12px;
                gap: 10px;
                font-size: 13px;
            }

            .carbon-nav-link img {
                width: 21px;
                height: 21px;
            }

            .carbon-nav-link.active {
                padding-left: 43px;
            }

            .carbon-topbar {
                min-height: 90px;
                height: 90px;
                padding: 14px 16px;
            }

            .carbon-page-title {
                font-size: 22px;
                line-height: 29px;
            }

            .carbon-page-subtitle {
                font-size: 12px;
                line-height: 18px;
            }

            .carbon-topbar-right {
                gap: 10px;
            }

            .carbon-topbar-select {
                min-width: 88px;
                height: 38px;
                font-size: 12px;
            }

            .carbon-admin-name {
                font-size: 12px;
            }

            .carbon-admin-role {
                font-size: 11px;
            }

            .carbon-admin-avatar {
                width: 40px;
                height: 40px;
            }

            .carbon-admin-avatar img {
                width: 26px;
                height: 26px;
            }
        }

        /* =========================================
           RESPONSIVE: MOBILE
        ========================================= */

        .carbon-sidebar-overlay {
            display: none;
        }

        @media (max-width: 700px) {
            body {
                overflow: hidden;
            }

            .carbon-sidebar {
                position: fixed;
                top: 0;
                left: 0;
                height: 100dvh;
                width: min(280px, 86vw);
                min-width: 0;
                max-width: none;
                transform: translateX(-105%);
                transition: transform 0.22s ease;
                box-shadow: 8px 0 24px rgba(0, 0, 0, 0.18);
            }

            .carbon-sidebar.open {
                transform: translateX(0);
            }

            /* Return to the desktop logo height inside the mobile drawer */

            .carbon-sidebar-logo {
                height: 100px;
                min-height: 100px;
                padding: 14px 20px;
            }

            .carbon-sidebar-logo img {
                width: 52px;
                height: 52px;
            }

            .carbon-sidebar-logo-text {
                font-size: 20px;
            }

            .carbon-nav-link {
                padding: 11px 16px;
                gap: 12px;
                font-size: 14px;
            }

            .carbon-nav-link img {
                width: 22px;
                height: 22px;
            }

            .carbon-nav-link.active {
                padding-left: 50px;
                gap: 0;
            }

            .carbon-sidebar-overlay {
                position: fixed;
                inset: 0;
                z-index: 99;
                background: rgba(0, 0, 0, 0.45);
                display: block;
                opacity: 0;
                visibility: hidden;
                transition: opacity 0.2s ease, visibility 0.2s ease;
            }

            .carbon-sidebar-overlay.active {
                opacity: 1;
                visibility: visible;
            }

            .carbon-topbar {
                min-height: 80px;
                height: auto;
                padding: 12px 14px;
                gap: 10px;
                align-items: center;
            }

            .carbon-topbar-left {
                gap: 8px;
            }

            .carbon-menu-button {
                width: 36px;
                height: 36px;
                font-size: 23px;
            }

            .carbon-page-title {
                font-size: 19px;
                line-height: 25px;
            }

            .carbon-page-subtitle {
                font-size: 11px;
                line-height: 16px;
            }

            .carbon-topbar-right {
                gap: 8px;
            }

            .carbon-topbar-controls {
                display: none;
            }

            .carbon-topbar-divider {
                display: none;
            }

            .carbon-admin-profile > div:last-child {
                display: none;
            }

            .carbon-admin-avatar {
                width: 36px;
                height: 36px;
            }

            .carbon-admin-avatar img {
                width: 24px;
                height: 24px;
            }

            .carbon-theme-toggle,
            .carbon-logout-button {
                width: 100%;
                min-width: 0;
                min-height: 46px;
                height: auto;
                padding: 10px;
                font-size: 13px;
                justify-content: center;
                gap: 10px;
            }

            .carbon-theme-toggle .theme-toggle-text {
                display: inline;
            }

            .carbon-theme-toggle svg,
            .carbon-logout-icon {
                width: 21px;
                height: 21px;
            }

            .carbon-content {
                padding: 16px 12px;
            }
        }

        /* Reduced motion accessibility */

        @media (prefers-reduced-motion: reduce) {
            .carbon-nav-link,
            .carbon-nav-link img,
            .carbon-sidebar,
            .carbon-sidebar-overlay,
            .carbon-theme-toggle,
            .carbon-logout-button {
                transition: none;
            }
        }
    </style>
</head>

<body>

<div class="carbon-layout">

    <!-- SIDEBAR -->

    <aside class="carbon-sidebar" id="carbonSidebar">

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

        <nav class="carbon-sidebar-nav" aria-label="Admin navigation">

            <a
                href="/dashboard"
                class="carbon-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                @if(request()->routeIs('dashboard')) aria-current="page" @endif
            >
                <img src="{{ asset('icons/dashboard.png') }}" alt="" aria-hidden="true">
                <span>Overview</span>
            </a>

            <a
                href="{{ route('admin.users') }}"
                class="carbon-nav-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}"
                @if(request()->routeIs('admin.users*')) aria-current="page" @endif
            >
                <img src="{{ asset('icons/user.png') }}" alt="" aria-hidden="true">
                <span>User Management</span>
            </a>

            <a
                href="{{ route('admin.emissions') }}"
                class="carbon-nav-link {{ request()->routeIs('admin.emissions*') ? 'active' : '' }}"
                @if(request()->routeIs('admin.emissions*')) aria-current="page" @endif
            >
                <img src="{{ asset('icons/emissions.png') }}" alt="" aria-hidden="true">
                <span>Emissions Overview</span>
            </a>

            <a
                href="{{ route('admin.analytics') }}"
                class="carbon-nav-link {{ request()->routeIs('admin.analytics*') ? 'active' : '' }}"
                @if(request()->routeIs('admin.analytics*')) aria-current="page" @endif
            >
                <img src="{{ asset('icons/analytics.png') }}" alt="" aria-hidden="true">
                <span>Analytics &amp; Reports</span>
            </a>

            <a
                href="{{ route('admin.forecasting') }}"
                class="carbon-nav-link {{ request()->routeIs('admin.forecasting*') ? 'active' : '' }}"
                @if(request()->routeIs('admin.forecasting*')) aria-current="page" @endif
            >
                <img src="{{ asset('icons/forecast.png') }}" alt="" aria-hidden="true">
                <span>Forecasting</span>
            </a>

            <a
                href="{{ route('admin.mitigation') }}"
                class="carbon-nav-link {{ request()->routeIs('admin.mitigation*') ? 'active' : '' }}"
                @if(request()->routeIs('admin.mitigation*')) aria-current="page" @endif
            >
                <img src="{{ asset('icons/mitigation.png') }}" alt="" aria-hidden="true">
                <span>Mitigation Strategies</span>
            </a>

            <a
                href="{{ route('admin.alerts') }}"
                class="carbon-nav-link {{ request()->routeIs('admin.alerts*') ? 'active' : '' }}"
                @if(request()->routeIs('admin.alerts*')) aria-current="page" @endif
            >
                <img src="{{ asset('icons/alerts.png') }}" alt="" aria-hidden="true">
                <span>Alerts &amp; Notifications</span>
            </a>

        </nav>

        <!-- Sidebar footer -->

        <div class="carbon-sidebar-footer">

            <!-- Dark mode toggle -->

            <button
                type="button"
                id="carbonThemeToggle"
                class="carbon-theme-toggle"
                aria-label="Toggle dark mode"
            >
                <svg
                    id="carbonThemeIcon"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="1.8"
                    aria-hidden="true"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364-.707-.707M6.343 6.343l-.707-.707m12.728 0-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z"
                    />
                </svg>

                <span id="carbonThemeText" class="theme-toggle-text">
                    Dark Mode
                </span>
            </button>

            <!-- Logout -->

            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <button type="submit" class="carbon-logout-button">
                    <svg
                        class="carbon-logout-icon"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="1.7"
                        aria-hidden="true"
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

                    <span>Log Out</span>
                </button>
            </form>

        </div>
    </aside>

    <!-- Mobile sidebar overlay -->

    <div
        id="carbonSidebarOverlay"
        class="carbon-sidebar-overlay"
        aria-hidden="true"
    ></div>

    <!-- MAIN -->

    <main class="carbon-main">

        <!-- Top bar -->

        <div class="carbon-topbar">

            <!-- Top bar left -->

            <div class="carbon-topbar-left">

                <button
                    type="button"
                    id="carbonMenuButton"
                    class="carbon-menu-button"
                    aria-label="Open menu"
                    aria-expanded="false"
                    aria-controls="carbonSidebar"
                >
                    ☰
                </button>

                <div class="carbon-page-heading">
                    <h2 class="carbon-page-title">
                        @yield('page-title', 'Overview')
                    </h2>

                    <p class="carbon-page-subtitle">
                        @yield('page-subtitle', 'Welcome Back, ' . Auth::user()->name . '!')
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

                        <form method="GET" class="flex items-center gap-3">

                            <select
                                name="year"
                                onchange="this.form.submit()"
                                class="carbon-topbar-select"
                                aria-label="Filter by year"
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

                            <select
                                name="month"
                                onchange="this.form.submit()"
                                class="carbon-topbar-select"
                                style="min-width: 135px;"
                                aria-label="Filter by month"
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
                            <div class="carbon-export-dropdown" id="carbonExportDropdown">

                                <button
                                    type="button"
                                    class="carbon-export-button"
                                    id="carbonExportButton"
                                    aria-haspopup="true"
                                    aria-expanded="false"
                                    aria-controls="carbonExportMenu"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="w-5 h-5"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        aria-hidden="true"
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
                                    class="carbon-export-menu"
                                    id="carbonExportMenu"
                                    role="menu"
                                    aria-labelledby="carbonExportButton"
                                >
                                    <a
                                        href="{{ route('admin.analytics.export.pdf') }}"
                                        role="menuitem"
                                    >
                                        PDF Report
                                    </a>

                                    <a
                                        href="{{ route('admin.analytics.export.excel') }}"
                                        role="menuitem"
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
                            alt="Administrator avatar"
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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const sidebar = document.querySelector('.carbon-sidebar');
        const menuButton = document.getElementById('carbonMenuButton');
        const overlay = document.getElementById('carbonSidebarOverlay');

        const themeButton = document.getElementById('carbonThemeToggle');
        const themeIcon = document.getElementById('carbonThemeIcon');
        const themeText = document.getElementById('carbonThemeText');

        const exportDropdown = document.getElementById('carbonExportDropdown');
        const exportButton = document.getElementById('carbonExportButton');

        /* Export dropdown */

        if (exportDropdown && exportButton) {
            exportButton.addEventListener('click', function (event) {
                event.stopPropagation();

                const isOpen = exportDropdown.classList.toggle('is-open');

                exportButton.setAttribute('aria-expanded', String(isOpen));
            });

            document.addEventListener('click', function (event) {
                if (!exportDropdown.contains(event.target)) {
                    exportDropdown.classList.remove('is-open');
                    exportButton.setAttribute('aria-expanded', 'false');
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    exportDropdown.classList.remove('is-open');
                    exportButton.setAttribute('aria-expanded', 'false');
                    exportButton.focus();
                }
            });

            exportDropdown.querySelectorAll('a').forEach(function (link) {
                link.addEventListener('click', function () {
                    exportDropdown.classList.remove('is-open');
                    exportButton.setAttribute('aria-expanded', 'false');
                });
            });
        }

        /* Mobile sidebar */

        function closeSidebar() {
            if (!sidebar || !overlay) return;

            sidebar.classList.remove('open');
            overlay.classList.remove('active');

            if (menuButton) {
                menuButton.setAttribute('aria-expanded', 'false');
                menuButton.setAttribute('aria-label', 'Open menu');
            }
        }

        function toggleSidebar() {
            if (!sidebar || !overlay) return;

            const isOpen = sidebar.classList.toggle('open');

            overlay.classList.toggle('active', isOpen);

            if (menuButton) {
                menuButton.setAttribute('aria-expanded', String(isOpen));
                menuButton.setAttribute(
                    'aria-label',
                    isOpen ? 'Close menu' : 'Open menu'
                );
            }
        }

        if (menuButton) {
            menuButton.addEventListener('click', toggleSidebar);
        }

        if (overlay) {
            overlay.addEventListener('click', closeSidebar);
        }

        if (sidebar) {
            sidebar.querySelectorAll('a').forEach(function (link) {
                link.addEventListener('click', function () {
                    if (window.matchMedia('(max-width: 700px)').matches) {
                        closeSidebar();
                    }
                });
            });
        }

        /* Theme toggle */

        function updateThemeToggle(theme) {
            if (!themeIcon || !themeText) return;

            if (theme === 'dark') {
                themeIcon.innerHTML =
                    '<path stroke-linecap="round" stroke-linejoin="round" d="M20.5 15.5A8.5 8.5 0 0 1 8.5 3.5 8.5 8.5 0 1 0 20.5 15.5Z" />';

                themeText.textContent = 'Light Mode';
            } else {
                themeIcon.innerHTML =
                    '<path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364-.707-.707M6.343 6.343l-.707-.707m12.728 0-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z" />';

                themeText.textContent = 'Dark Mode';
            }
        }

        function applyTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);

            try {
                localStorage.setItem('theme', theme);
            } catch (error) {
                /* Storage may be disabled. */
            }

            updateThemeToggle(theme);

            window.dispatchEvent(
                new CustomEvent('carbonwise:themechange', {
                    detail: { theme: theme }
                })
            );
        }

        let savedTheme = 'light';

        try {
            savedTheme = localStorage.getItem('theme') || 'light';
        } catch (error) {
            /* Use light mode if storage is unavailable. */
        }

        applyTheme(savedTheme === 'dark' ? 'dark' : 'light');

        if (themeButton) {
            themeButton.addEventListener('click', function () {
                const current =
                    document.documentElement.getAttribute('data-theme') || 'light';

                applyTheme(current === 'dark' ? 'light' : 'dark');
            });
        }

        /* Close the mobile sidebar when returning to desktop */

        window.addEventListener('resize', function () {
            if (!window.matchMedia('(max-width: 700px)').matches) {
                closeSidebar();
            }
        });
    });
</script>

</body>
</html>