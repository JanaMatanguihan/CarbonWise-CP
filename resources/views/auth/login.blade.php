
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CarbonWise | Admin Login</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #F5F6F5;
            color: #172F27;
        }

        /* =========================
           HEADER
        ========================= */

        .navbar {
            width: 100%;
            height: 95px;
            background: #FFFFFF;
            display: flex;
            align-items: center;
            padding: 0 8%;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand img {
            width: 42px;
            height: 42px;
            object-fit: contain;
        }

        .brand span {
            font-size: 23px;
            font-weight: 800;
            color: #172F27;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* =========================
           HERO SECTION
        ========================= */

        .hero {
            min-height: 570px;
            background: linear-gradient(
                115deg,
                #174E3B 0%,
                #205F49 55%,
                #286A50 100%
            );
            color: white;

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 65px 7% 95px;
            gap: 7%;

            position: relative;
            overflow: hidden;
        }

        .hero::after {
            content: "";
            position: absolute;
            bottom: -85px;
            left: -5%;
            width: 110%;
            height: 115px;
            background: #F5F6F5;
            border-radius: 50% 50% 0 0;
        }

        /* =========================
           LEFT CONTENT
        ========================= */

        .hero-left {
            flex: 0 1 570px;
            max-width: 570px;
            position: relative;
            z-index: 1;
        }

        .system-badge {
            display: inline-flex;
            align-items: center;
            gap: 9px;

            padding: 9px 17px;
            border-radius: 30px;

            background: rgba(255,255,255,0.13);

            font-size: 14px;
            font-weight: 700;

            margin-bottom: 25px;
        }

        .hero-left h1 {
            font-size: clamp(36px, 4vw, 53px);
            line-height: 1.12;
            font-weight: 800;

            margin: 0 0 22px;
            letter-spacing: -1.2px;
        }

        .hero-left h1 span {
            color: #62D58A;
        }

        .hero-left p {
            font-size: 17px;
            line-height: 1.65;
            color: #E1EEE7;

            max-width: 610px;
            margin: 0;
        }

        /* =========================
           LOGIN CARD
        ========================= */

        .login-card {
            flex: 0 1 440px;
            width: 100%;
            max-width: 440px;

            background: rgba(255,255,255,0.09);
            border: 1px solid rgba(255,255,255,0.22);
            border-radius: 24px;

            padding: 36px;

            box-shadow: 0 15px 40px rgba(0,0,0,0.12);
            backdrop-filter: blur(10px);

            position: relative;
            z-index: 1;
        }

        .card-heading {
            display: flex;
            align-items: center;
            gap: 15px;

            border-bottom: 1px solid rgba(255,255,255,0.18);

            padding-bottom: 22px;
            margin-bottom: 25px;
        }

        .card-heading h2 {
            font-size: 28px;
            font-weight: 800;
            margin: 0;
            color: #FFFFFF;
        }

        .heading-icon {
            width: 36px;
            height: 36px;
            color: #62D58A;
            flex-shrink: 0;
        }

        /* =========================
           ERROR MESSAGE
        ========================= */

        .error-message {
            background: rgba(255,220,220,0.16);
            border: 1px solid rgba(255,180,180,0.5);
            color: #FFE1E1;

            padding: 12px;
            border-radius: 10px;

            font-size: 14px;
            margin-bottom: 18px;
        }

        /* =========================
           INPUT FIELDS
        ========================= */

        .form-group {
            margin-bottom: 19px;
        }

        .form-group label {
            display: block;

            font-size: 14px;
            font-weight: 600;
            color: #EAF3ED;

            margin-bottom: 9px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);

            width: 20px;
            height: 20px;

            color: #D5E9DD;
            pointer-events: none;
        }

        .input-wrapper input {
            width: 100%;
            height: 54px;

            padding: 0 48px;

            border: 1px solid rgba(255,255,255,0.35);
            border-radius: 13px;

            background: rgba(255,255,255,0.06);
            color: #FFFFFF;

            font-size: 15px;
            outline: none;

            transition: 0.2s ease;
        }

        .input-wrapper input::placeholder {
            color: #D5E4DC;
        }

        .input-wrapper input:focus {
            border-color: #62D58A;
            box-shadow: 0 0 0 3px rgba(98,213,138,0.15);
        }

        .input-wrapper input[type="password"]::-ms-reveal,
        .input-wrapper input[type="password"]::-ms-clear {
            display: none !important;
        }

        /* =========================
           PASSWORD VISIBILITY
        ========================= */

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);

            background: transparent;
            border: none;

            color: #D5E9DD;
            cursor: pointer;

            padding: 5px;

            display: flex;
            align-items: center;
            justify-content: center;
        }

        .toggle-password:hover {
            color: #62D58A;
        }

        .toggle-password svg {
            width: 20px;
            height: 20px;
        }

        /* =========================
           FORGOT PASSWORD
        ========================= */

        .forgot-password {
            display: flex;
            justify-content: flex-end;
            margin: -2px 0 25px;
        }

        .forgot-password a {
            color: #65D98F;
            text-decoration: none;

            font-weight: 700;
            font-size: 14px;
        }

        .forgot-password a:hover {
            color: #A1F0B9;
            text-decoration: underline;
        }

        /* =========================
           LOGIN BUTTON
        ========================= */

        .login-button {
            width: 100%;
            height: 56px;

            border: none;
            border-radius: 13px;

            background: #FFFFFF;
            color: #174E3B;

            font-size: 17px;
            font-weight: 800;

            cursor: pointer;
            transition: 0.2s ease;

            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .login-button svg {
            width: 21px;
            height: 21px;
        }

        .login-button:hover {
            background: #DFF3E5;
            transform: translateY(-2px);
        }

        /* =========================
           ADMIN ACCESS MESSAGE
        ========================= */

        .authorized {
            text-align: center;
            color: #D5E9DD;

            font-size: 13px;
            line-height: 1.6;

            margin: 20px 0 0;
        }

        .authorized a {
            color: #65D98F;
            font-weight: 700;
            text-decoration: none;
        }

        .authorized a:hover {
            text-decoration: underline;
        }

        /* =========================
           RESPONSIVE DESIGN
        ========================= */

        @media (max-width: 900px) {
            .hero {
                flex-direction: column;
                align-items: center;
                justify-content: center;

                gap: 40px;
                padding: 55px 7% 110px;
            }

            .hero-left {
                width: 100%;
                max-width: 550px;
            }

            .login-card {
                width: 100%;
                max-width: 550px;
                flex: none;
            }
        }

        @media (max-width: 500px) {
            .navbar {
                height: 75px;
                padding: 0 6%;
            }

            .brand span {
                font-size: 19px;
            }

            .brand img {
                width: 35px;
                height: 35px;
            }

            .hero {
                padding: 40px 6% 100px;
            }

            .hero-left h1 {
                font-size: 35px;
            }

            .login-card {
                padding: 24px 19px;
            }

            .card-heading h2 {
                font-size: 23px;
            }
        }
    </style>
</head>

<body>

    <!-- =========================
         HEADER
    ========================= -->

    <nav class="navbar">
        <div class="brand">

            <img
                src="{{ asset('images/carbonwise-logo.png') }}"
                alt="CarbonWise Logo"
            >

            <span>CarbonWise</span>

        </div>
    </nav>

    <!-- =========================
         MAIN LOGIN SECTION
    ========================= -->

    <main class="hero">

        <!-- LEFT CONTENT -->

        <section class="hero-left">

            <div class="system-badge">

                <svg width="17" height="17"
                     viewBox="0 0 24 24"
                     fill="none" stroke="currentColor"
                     stroke-width="2"
                     stroke-linecap="round"
                     stroke-linejoin="round">

                    <path d="M20 4c-8 0-14 4-14 11a5 5 0 0 0 5 5c7 0 11-6 9-16Z"/>
                    <path d="M4 21c3-6 7-9 13-12"/>

                </svg>

                Sustainability Monitoring System

            </div>

            <h1>
                <span>CarbonWise</span><br>
                Admin Login
            </h1>

            <p>
                Access the administrative dashboard to manage users,
                monitor carbon logs, oversee emissions, and generate
                analytics and reports for the BatStateU community.
            </p>

        </section>

        <!-- ADMIN LOGIN CARD -->

        <section class="login-card">

            <div class="card-heading">

                <svg class="heading-icon"
                     xmlns="http://www.w3.org/2000/svg"
                     viewBox="0 0 24 24"
                     fill="none" stroke="currentColor"
                     stroke-width="1.8"
                     stroke-linecap="round"
                     stroke-linejoin="round">

                    <path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11Z"/>
                    <path d="m9 12 2 2 4-4"/>

                </svg>

                <h2>Admin Login</h2>

            </div>

            @if ($errors->any())
                <div class="error-message">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <!-- EMAIL -->

                <div class="form-group">

                    <label for="email">Email Address</label>

                    <div class="input-wrapper">

                        <svg class="input-icon"
                             xmlns="http://www.w3.org/2000/svg"
                             viewBox="0 0 24 24"
                             fill="none" stroke="currentColor"
                             stroke-width="1.8"
                             stroke-linecap="round"
                             stroke-linejoin="round">

                            <rect x="3" y="5" width="18" height="14" rx="2"/>
                            <path d="m3 7 9 6 9-6"/>

                        </svg>

                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="Enter your email address"
                            autocomplete="username"
                            required
                            autofocus
                        >

                    </div>

                </div>

                <!-- PASSWORD -->

                <div class="form-group">

                    <label for="password">Password</label>

                    <div class="input-wrapper">

                        <svg class="input-icon"
                             xmlns="http://www.w3.org/2000/svg"
                             viewBox="0 0 24 24"
                             fill="none" stroke="currentColor"
                             stroke-width="1.8"
                             stroke-linecap="round"
                             stroke-linejoin="round">

                            <rect x="4" y="10" width="16" height="11" rx="2"/>
                            <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                            <path d="M12 14v3"/>

                        </svg>

                        <input
                            id="password"
                            type="password"
                            name="password"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >

                        <button
                            type="button"
                            id="togglePassword"
                            class="toggle-password"
                            aria-label="Show password"
                            aria-pressed="false"
                        >

                            <svg id="eyeIcon"
                                 xmlns="http://www.w3.org/2000/svg"
                                 viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor"
                                 stroke-width="1.8"
                                 stroke-linecap="round"
                                 stroke-linejoin="round">

                                <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/>
                                <circle cx="12" cy="12" r="3"/>

                            </svg>

                        </button>

                    </div>

                </div>

                <!-- FORGOT PASSWORD -->

                <div class="forgot-password">

                    @if (Route::has('password.request'))

                        <a href="{{ route('password.request') }}">
                            Forgot Password?
                        </a>

                    @endif

                </div>

                <!-- LOGIN BUTTON -->

                <button type="submit" class="login-button">

                    <svg xmlns="http://www.w3.org/2000/svg"
                         viewBox="0 0 24 24"
                         fill="none" stroke="currentColor"
                         stroke-width="2"
                         stroke-linecap="round"
                         stroke-linejoin="round">

                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                        <path d="m10 17 5-5-5-5"/>
                        <path d="M15 12H3"/>

                    </svg>

                    Log In

                </button>

                <p class="authorized">
                    Authorized administrative personnel only.<br>
                    Need admin access? Contact your system administrator.
                </p>

            </form>

        </section>

    </main>

    <!-- =========================
         PASSWORD VISIBILITY SCRIPT
    ========================= -->

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const passwordInput = document.getElementById('password');
            const togglePassword = document.getElementById('togglePassword');
            const eyeIcon = document.getElementById('eyeIcon');

            if (!passwordInput || !togglePassword || !eyeIcon) {
                return;
            }

            const eyeOpen = `
                <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/>
                <circle cx="12" cy="12" r="3"/>
            `;

            const eyeClosed = `
                <path d="m3 3 18 18"/>
                <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/>
                <path d="M9.9 5.2A10.8 10.8 0 0 1 12 5c6.4 0 10 7 10 7a17 17 0 0 1-4 4.8"/>
                <path d="M6.6 6.6C3.6 8.5 2 12 2 12s3.6 7 10 7c1.2 0 2.3-.2 3.3-.6"/>
            `;

            togglePassword.addEventListener('click', function () {

                const showPassword = passwordInput.type === 'password';

                passwordInput.type = showPassword ? 'text' : 'password';

                eyeIcon.innerHTML = showPassword ? eyeClosed : eyeOpen;

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
