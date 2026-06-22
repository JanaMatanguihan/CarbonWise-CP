<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CarbonWise Admin Login</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-100 min-h-screen">

<nav class="w-full bg-white shadow-md">
    <div class="max-w-7xl mx-auto flex items-center justify-between px-8 py-4">

        <!-- Logo -->
        <div class="flex items-center gap-3">
            <img src="{{ asset('images/carbonwise-logo.png') }}"
                 class="w-14 h-14"
                 alt="CarbonWise Logo">

            <span class="text-3xl font-bold text-green-700">
                CarbonWise
            </span>
        </div>

        <!-- Navigation Links -->
        <div class="flex gap-10 text-lg font-medium text-green-700">
            <a href="/">Home</a>
            <a href="#features">Features</a>
            <a href="#about">About</a>
            <a href="#contact">Contact</a>
        </div>

    </div>
</nav>
        <div class="flex justify-center items-center py-16">
    
<div class="w-full max-w-xl bg-[#B7CBB9] rounded-[40px] shadow-2xl px-10 py-12">

        <div class="flex justify-center mb-6">
            <img
                src="{{ asset('images/carbonwise-logo.png') }}"
                class="w-24 h-24 rounded-full bg-white p-2 shadow-md"
                alt="CarbonWise Logo"
            >
            </div>

    <h1 class="text-3xl font-bold text-center text-[#1F5A3C]">
    Welcome to CarbonWise
    </h1>

    <p class="text-center text-green-700 mt-2 mb-8">
        Sign in to the SDO Administrator Portal
    </p>

            @if ($errors->any())
            <div class="mb-4 rounded-lg bg-red-100 border border-red-300 text-red-700 p-3 text-sm">
                {{ $errors->first() }}
            </div>
        @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="mb-5">
            <label class="font-semibold block mb-2">
                    Admin Email
                </label>

                <input
                    type="email"
                    name="email"
                    placeholder="Enter Admin Email"
                    class="w-full rounded-lg border p-3"
                    autocomplete="email"
                    required
                >
        </div>

        <div class="mb-6">
    <label class="font-semibold block mb-2">
        Password
            </label>

            <div class="relative">
                <input
                    id="password"
                    type="password"
                    name="password"
                    placeholder="Enter Password"
                    class="w-full rounded-lg border p-3 pr-12"
                    required
                >

            <button
            type="button"
            id="togglePassword"
            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-green-700"
        >
            <i data-lucide="eye" class="w-5 h-5"></i>
        </button>
            </div>
        </div>

       <button
            type="submit"
            class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-lg"
        >
            Log In
        </button>

        <p class="text-center text-sm text-gray-600 mt-5">
            Authorized personnel only.
        </p>
    </form>
    </div>
</div>
        <script>
    document.getElementById('togglePassword').addEventListener('click', function () {
        const password = document.getElementById('password');

        if (password.type === 'password') {
            password.type = 'text';
        } else {
            password.type = 'password';
    }
});
</script>
</body>
</html>