<!DOCTYPE html>

@php
    $setting = \App\Models\Setting::first();
@endphp

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CarbonWise</title>

    @vite(['resources/css/app.css','resources/js/app.js'])

   <style>
        :root{
            --accent-color: {{ $setting->accent_color ?? '#15803d' }};
            --accent-hover: {{ $setting->accent_color ?? '#15803d' }};
        }
        </style>
</head>

<body class="bg-gray-100">

<div class="flex h-screen">

    <!-- Sidebar -->
   <aside
    class="w-72 h-screen text-white flex flex-col"
    style="background-color: var(--accent-color);">

        <div class="p-5 border-b border-green-700 flex items-center gap-3">

            <img
                src="{{ asset('images/carbonwise-logo.png') }}"
                class="w-14 h-14 rounded-full bg-white p-1"
            >

            <div>
                <h1 class="font-bold text-xl">
                    CarbonWise
                </h1>
            </div>

        </div>

        <nav class="flex-1 py-4 overflow-hidden">
            
    <a class="flex items-center gap-3 px-6 py-3 hover:bg-green-700" href="/dashboard">
        <img src="{{ asset('icons/dashboard.png') }}" class="w-5 h-5">
        <span>Overview</span>
    </a>

    <a
    href="{{ route('admin.users') }}"
    class="flex items-center gap-3 px-6 py-3 hover:bg-green-700"
>
    <img src="{{ asset('icons/user.png') }}" class="w-5 h-5">
    <span>User Management</span>
    </a>

    <a class="flex items-center gap-3 px-6 py-3 hover:bg-green-700" href="{{ route('admin.emissions') }}">
        <img src="{{ asset('icons/emissions.png') }}" class="w-5 h-5">
        <span>Emissions Overview</span>
    </a>

    <a class="flex items-center gap-3 px-6 py-3 hover:bg-green-700"  href="{{ route('admin.analytics') }}">
        <img src="{{ asset('icons/analytics.png') }}" class="w-5 h-5">
        <span>Analytics & Reports</span>
    </a>

    <a class="flex items-center gap-3 px-6 py-3 hover:bg-green-700" href="#">
        <img src="{{ asset('icons/forecast.png') }}" class="w-5 h-5">
        <span>Forecasting</span>
    </a>

    <a class="flex items-center gap-3 px-6 py-3 hover:bg-green-700" href="{{ route('admin.mitigation') }}">
        <img src="{{ asset('icons/mitigation.png') }}" class="w-5 h-5">
        <span>Mitigation Strategies</span>
    </a>

    <a class="flex items-center gap-3 px-6 py-3 hover:bg-green-700" href="{{ route('admin.alerts') }}">
        <img src="{{ asset('icons/alerts.png') }}" class="w-5 h-5">
        <span>Alerts & Notifications</span>
    </a>

    <a class="flex items-center gap-3 px-6 py-3 hover:bg-green-700" href="#">
        <img src="{{ asset('icons/sdo.png') }}" class="w-5 h-5">
        <span>SDO Monitoring</span>
    </a>

    <a class="flex items-center gap-3 px-6 py-3 hover:bg-green-700" href="{{ route('admin.settings') }}">
        <img src="{{ asset('icons/settings.png') }}" class="w-5 h-5">
        <span>Settings</span>
    </a>

    <a class="flex items-center gap-3 px-6 py-3 hover:bg-green-700" href="#">
        <img src="{{ asset('icons/audit.png') }}" class="w-5 h-5">
        <span>Audit Logs</span>
    </a>

    <a class="flex items-center gap-3 px-6 py-3 hover:bg-green-700" href="#">
        <img src="{{ asset('icons/help.png') }}" class="w-5 h-5">
        <span>Help & Support</span>
    </a>

</nav>

        <div class="mt-auto p-4 border-t border-green-700">
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button
            type="submit"
            class="w-full text-white py-3 rounded-xl font-semibold transition"
            style="background: var(--accent-color);"
        >
            Log Out
        </button>
    </form>
</div>

    </aside>

    <!-- Main -->
    <main class="flex-1 overflow-y-auto">

       <div class="bg-white shadow px-8 py-4 flex justify-between items-center">

    <!-- Left -->
    <div class="flex items-center gap-4">

        <button class="text-2xl text-gray-700">
            ☰
        </button>

        <div>
            <h2 class="text-3xl font-bold leading-none">
                @yield('page-title', 'Overview')
            </h2>

            <p class="text-base text-gray-500 mt-1">
                @yield('page-subtitle', 'Welcome Back, ' . Auth::user()->name . '!')
            </p>
        </div>

    </div>

    <!-- Right -->
    <div class="flex items-center gap-8">

      @if (request()->routeIs('dashboard') || request()->routeIs('admin.analytics'))

<div class="flex items-center gap-5">


    <!-- Date Picker -->
<form 
    method="GET"
    class="
    bg-white
    border
    border-gray-300
    rounded-lg
    shadow-sm
    h-[42px]
    px-5
    flex
    items-center
    gap-3
    "
>


    <svg xmlns="http://www.w3.org/2000/svg"
        class="w-4 h-4 text-gray-700"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor">


        <path stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>


    </svg>


    <input
        type="month"
        name="month"
        value="{{ request('month') }}"
        onchange="this.form.submit()"
        class="
        w-[130px]
        bg-transparent
        text-sm
        outline-none
        cursor-pointer
        "
    >


</form>

    <!-- Export Dropdown -->
        @if(request()->routeIs('admin.analytics'))

        <div class="relative group">


            <button
                class="
                bg-white
                border
                border-green-700
                text-green-700
                px-5
                py-2
                rounded-lg
                shadow-sm
                text-sm
                flex
                items-center
                gap-2
                "
            >


                <svg xmlns="http://www.w3.org/2000/svg"
                    class="w-4 h-4"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor">


                    <path stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4"/>


                </svg>


                Export Report


            </button>



            <!-- Dropdown -->
            <div
                class="
                hidden
                group-hover:block
                absolute
                right-0
                mt-2
                w-44
                bg-white
                border
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


    <!-- Vertical Divider -->
    <div class="h-12 border-l border-gray-300"></div>
       

        <!-- Admin Info -->
        <div class="flex items-center gap-3">

            <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                👤
            </div>

            <div>
                <div class="font-bold text-green-700">
                    {{ Auth::user()->name }}
                </div>

                <div class="text-sm text-gray-500">
                    Administrator
                </div>
            </div>

        </div>

    </div>

</div>

      <div class="px-4 pb-4 pt-0">
    @yield('content')
</div>
    </main>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

@stack('scripts')

</body>
</html>