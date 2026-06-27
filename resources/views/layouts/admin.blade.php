<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CarbonWise</title>

    @vite(['resources/css/app.css','resources/js/app.js'])
</head>

<body class="bg-gray-100">

<div class="flex h-screen">

    <!-- Sidebar -->
    <aside class="w-72 h-screen bg-green-800 text-white flex flex-col">
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

    <a class="flex items-center gap-3 px-6 py-3 hover:bg-green-700" href="#">
        <img src="{{ asset('icons/emissions.png') }}" class="w-5 h-5">
        <span>Emissions Overview</span>
    </a>

    <a class="flex items-center gap-3 px-6 py-3 hover:bg-green-700" href="#">
        <img src="{{ asset('icons/analytics.png') }}" class="w-5 h-5">
        <span>Analytics & Reports</span>
    </a>

    <a class="flex items-center gap-3 px-6 py-3 hover:bg-green-700" href="#">
        <img src="{{ asset('icons/forecast.png') }}" class="w-5 h-5">
        <span>Forecasting</span>
    </a>

    <a class="flex items-center gap-3 px-6 py-3 hover:bg-green-700" href="#">
        <img src="{{ asset('icons/mitigation.png') }}" class="w-5 h-5">
        <span>Mitigation Strategies</span>
    </a>

    <a class="flex items-center gap-3 px-6 py-3 hover:bg-green-700" href="#">
        <img src="{{ asset('icons/alerts.png') }}" class="w-5 h-5">
        <span>Alerts & Notifications</span>
    </a>

    <a class="flex items-center gap-3 px-6 py-3 hover:bg-green-700" href="#">
        <img src="{{ asset('icons/sdo.png') }}" class="w-5 h-5">
        <span>SDO Monitoring</span>
    </a>

    <a class="flex items-center gap-3 px-6 py-3 hover:bg-green-700" href="#">
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
            class="w-full bg-green-700 hover:bg-green-600 text-white py-3 rounded-xl font-semibold transition"
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

       @if (request()->routeIs('dashboard'))
<div class="flex items-center gap-3 bg-white border border-gray-300 rounded-xl px-4 py-3 shadow-sm">

    <!-- Calendar Icon -->
    <svg xmlns="http://www.w3.org/2000/svg"
         class="w-6 h-6 text-gray-700"
         fill="none"
         viewBox="0 0 24 24"
         stroke="currentColor">
        <path stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
    </svg>

    <!-- Month Picker -->
        <form method="GET" action="{{ route('dashboard') }}">
            <input
            id="monthPicker"
            name="month"
            type="month"
            value="{{ request('month', now()->format('Y-m')) }}"
            class="bg-transparent outline-none font-semibold cursor-pointer"
            onchange="this.form.submit()"
        />
        </form>

</div>

    <!-- Vertical Divider -->
    <div class="h-12 border-l border-gray-300"></div>
    @endif
       
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

      <div class="px-6 pb-6 pt-0">
    @yield('content')
</div>
    </main>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

@stack('scripts')

</body>
</html>