<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CarbonWise Admin Login</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Hide the browser's built-in password visibility icons */
        input[type="password"]::-ms-reveal,
        input[type="password"]::-ms-clear,
        input::-ms-reveal,
        input::-ms-clear {
            display: none !important;
        }

        /* Keep the custom eye button as the only visibility control */
        #togglePassword {
            appearance: none;
            -webkit-appearance: none;
            border: none;
            background: transparent;
            padding: 4px;
            line-height: 0;
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen">

    <!-- Navigation -->
    <nav class="w-full bg-white shadow-md">
        <div class="max-w-7xl mx-auto grid grid-cols-3 items-center px-8 py-4">

            <div class="flex items-center gap-3">
                <img
                    src="{{ asset('images/carbonwise-logo.png') }}"
                    class="w-14 h-14"
                    alt="CarbonWise Logo"
                >

                <span class="text-3xl font-bold text-green-700">
                    CarbonWise
                </span>
            </div>

            <div class="flex justify-center gap-10 text-lg font-medium text-green-700">
                <a href="/" class="hover:text-green-900">Home</a>
                <a href="#features" class="hover:text-green-900">Features</a>
                <a href="#about" class="hover:text-green-900">About</a>
                <a href="#contact" class="hover:text-green-900">Contact</a>
            </div>

            <div></div>
        </div>
    </nav>

    <!-- Login Form -->
    <main class="flex justify-center items-center py-16">
        <div class="w-full max-w-xl bg-[#B5C9B8] rounded-[40px_0_40px_0] shadow-xl px-10 py-12">

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

                <!-- Email -->
                <div class="mb-5">
                    <label for="email" class="font-semibold block mb-2">
                        Admin Email
                    </label>

                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="Enter Admin Email"
                        class="w-full rounded-lg border p-3"
                        autocomplete="username"
                        required
                    >
                </div>

                <!-- Password -->
                <div class="mb-6">
                    <label for="password" class="font-semibold block mb-2">
                        Password
                    </label>

                    <div class="relative">
                        <input
                            id="password"
                            type="password"
                            name="password"
                            placeholder="Enter Password"
                            class="w-full rounded-lg border p-3 pr-12"
                            autocomplete="current-password"
                            required
                        >

                        <button
                            type="button"
                            id="togglePassword"
                            aria-label="Show password"
                            aria-pressed="false"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-green-700 focus:outline-none focus:ring-2 focus:ring-green-600 rounded"
                        >
                            <svg
                                id="passwordEyeIcon"
                                xmlns="http://www.w3.org/2000/svg"
                                width="20"
                                height="20"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Submit -->
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
    </main>

    <!-- Password Visibility Toggle -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const passwordInput = document.getElementById('password');
            const togglePassword = document.getElementById('togglePassword');
            const passwordEyeIcon = document.getElementById('passwordEyeIcon');

            if (!passwordInput || !togglePassword || !passwordEyeIcon) {
                return;
            }

            const eyeIcon = `
                <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"></path>
                <circle cx="12" cy="12" r="3"></circle>
            `;

            const eyeOffIcon = `
                <path d="m3 3 18 18"></path>
                <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"></path>
                <path d="M9.9 5.2A10.8 10.8 0 0 1 12 5c6.4 0 10 7 10 7a17.2 17.2 0 0 1-4 4.8"></path>
                <path d="M6.6 6.6C3.6 8.5 2 12 2 12s3.6 7 10 7c1.2 0 2.3-.2 3.3-.6"></path>
            `;

            togglePassword.addEventListener('click', function () {
                const showPassword = passwordInput.type === 'password';

                passwordInput.type = showPassword ? 'text' : 'password';

                passwordEyeIcon.innerHTML = showPassword
                    ? eyeOffIcon
                    : eyeIcon;

                togglePassword.setAttribute(
                    'aria-label',
                    showPassword ? 'Hide password' : 'Show password'
                );

                togglePassword.setAttribute(
                    'aria-pressed',
                    showPassword ? 'true' : 'false'
                );
            });
        });
    </script>

</body>
</html>