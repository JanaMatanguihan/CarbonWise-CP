
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CarbonWise | Forgot Password</title>

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

        /* HEADER */
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

        /* HERO SECTION */
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

        /* LEFT CONTENT */
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

        /* FORM CARD */
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
            font-size: 26px;
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

        .description {
            color: #D5E9DD;
            font-size: 14px;
            line-height: 1.7;
            margin: 0 0 23px;
        }

        /* STATUS AND ERRORS */
        .error-message {
            background: rgba(255,220,220,0.16);
            border: 1px solid rgba(255,180,180,0.5);
            color: #FFE1E1;
            padding: 12px;
            border-radius: 10px;
            font-size: 14px;
            margin-bottom: 18px;
        }

        .status-message {
            background: rgba(98,213,138,0.14);
            border: 1px solid rgba(98,213,138,0.4);
            color: #C9F5D7;
            padding: 12px;
            border-radius: 10px;
            font-size: 14px;
            margin-bottom: 18px;
            line-height: 1.5;
        }

        /* EMAIL FIELD */
        .form-group {
            margin-bottom: 20px;
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

        /* SUBMIT BUTTON */
        .reset-button {
            width: 100%;
            min-height: 56px;
            border: none;
            border-radius: 13px;
            background: #FFFFFF;
            color: #174E3B;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
            transition: 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .reset-button svg {
            width: 20px;
            height: 20px;
        }

        .reset-button:hover {
            background: #DFF3E5;
            transform: translateY(-2px);
        }

        /* BACK TO LOGIN */
        .authorized {
            text-align: center;
            color: #D5E9DD;
            font-size: 13px;
            line-height: 1.6;
            margin: 22px 0 0;
        }

        .authorized a {
            color: #65D98F;
            font-weight: 700;
            text-decoration: none;
        }

        .authorized a:hover {
            color: #A1F0B9;
            text-decoration: underline;
        }

        /* RESPONSIVE */
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
                font-size: 22px;
            }
        }
    </style>
</head>

<body>

    <!-- HEADER -->
    <nav class="navbar">
        <div class="brand">
            <img
                src="{{ asset('images/carbonwise-logo.png') }}"
                alt="CarbonWise Logo"
            >
            <span>CarbonWise</span>
        </div>
    </nav>

    <!-- MAIN SECTION -->
    <main class="hero">

        <!-- LEFT CONTENT -->
        <section class="hero-left">

            <div class="system-badge">
                <svg width="17" height="17"
                     viewBox="0 0 24 24"
                     fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round"
                     stroke-linejoin="round">
                    <path d="M20 4c-8 0-14 4-14 11a5 5 0 0 0 5 5c7 0 11-6 9-16Z"/>
                    <path d="M4 21c3-6 7-9 13-12"/>
                </svg>
                Sustainability Monitoring System
            </div>

            <h1>
                <span>CarbonWise</span><br>
                Forgot Password?
            </h1>

            <p>
                Having trouble accessing your administrator account?
                Enter your registered email address and we'll send
                you instructions to reset your password.
            </p>

        </section>

        <!-- FORGOT PASSWORD CARD -->
        <section class="login-card">

            <div class="card-heading">

                <svg class="heading-icon"
                     xmlns="http://www.w3.org/2000/svg"
                     viewBox="0 0 24 24"
                     fill="none" stroke="currentColor"
                     stroke-width="1.8"
                     stroke-linecap="round"
                     stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M12 16v-4"/>
                    <path d="M12 8h.01"/>
                </svg>

                <h2>Recover Account</h2>

            </div>

            <p class="description">
                Enter the email address associated with your
                CarbonWise administrator account. A password
                reset link will be sent if the account is eligible.
            </p>

            <!-- SESSION STATUS -->
            @if (session('status'))
                <div class="status-message">
                    {{ session('status') }}
                </div>
            @endif

            <!-- VALIDATION ERRORS -->
            @if ($errors->any())
                <div class="error-message">
                    {{ $errors->first() }}
                </div>
            @endif

            <!-- RESET REQUEST FORM -->
            <form method="POST" action="{{ route('password.email') }}">
                @csrf

                <div class="form-group">

                    <label for="email">Registered Email Address</label>

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
                            placeholder="Enter your registered email"
                            autocomplete="username"
                            required
                            autofocus
                        >

                    </div>

                </div>

                <!-- SUBMIT BUTTON -->
                <button type="submit" class="reset-button">

                    <svg xmlns="http://www.w3.org/2000/svg"
                         viewBox="0 0 24 24"
                         fill="none" stroke="currentColor"
                         stroke-width="2"
                         stroke-linecap="round"
                         stroke-linejoin="round">
                        <path d="M22 2 11 13"/>
                        <path d="m22 2-7 20-4-9-9-4Z"/>
                    </svg>

                    Email Password Reset Link

                </button>

                <p class="authorized">
                    Remembered your password?
                    <a href="{{ route('login') }}">Back to Admin Login</a>
                </p>

            </form>

        </section>

    </main>

</body>
</html>
