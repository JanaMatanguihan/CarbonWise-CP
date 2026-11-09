<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reset Password - CarbonWise</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f7faf8;
            color: #26382d;
        }

        .page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .card {
            width: 100%;
            max-width: 440px;
            background: white;
            border-radius: 24px;
            padding: 42px;
            box-shadow: 0 12px 40px rgba(38, 93, 59, 0.10);
        }

        .logo-container {
            display: flex;
            justify-content: center;
            margin-bottom: 14px;
        }

        .logo {
            width: 64px;
            height: 64px;
            object-fit: contain;
        }

        .brand {
            text-align: center;
            color: #265d3b;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 28px;
        }

        .title {
            text-align: center;
            font-size: 25px;
            font-weight: 700;
            color: #26382d;
            margin: 0 0 8px;
        }

        .description {
            text-align: center;
            color: #718078;
            font-size: 14px;
            line-height: 1.6;
            margin: 0 0 30px;
        }

        .field {
            margin-bottom: 20px;
        }

        .label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #34463b;
            margin-bottom: 8px;
        }

        /* New container to hold the input and the icon */
        .input-container {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input {
            width: 100%;
            height: 48px;
            border: 1px solid #d6dfd9;
            border-radius: 10px;
            padding: 0 45px 0 14px; /* Added right padding so text doesn't hit the icon */
            font-size: 14px;
            color: #26382d;
            background: #fafcfb;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .input:focus {
            border-color: #3aa76d;
            box-shadow: 0 0 0 3px rgba(58, 167, 109, 0.12);
            background: white;
        }

        .input:disabled {
            background: #f1f4f2;
            color: #7b8780;
        }

        /* The Eye Icon */
        .toggle-password {
            position: absolute;
            right: 14px;
            cursor: pointer;
            color: #9aa59f;
            font-size: 18px;
            user-select: none;
            transition: color 0.2s;
        }

        .toggle-password:hover {
            color: #3aa76d;
        }

        .error {
            margin-top: 6px;
            font-size: 12px;
            color: #c0392b;
        }

        .helper-text {
            font-size: 11px; 
            color: #718078; 
            margin-top: 6px;
        }

        .button {
            width: 100%;
            height: 50px;
            border: none;
            border-radius: 10px;
            background: #3aa76d;
            color: white;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
        }

        .button:hover {
            background: #318f5d;
        }

        .button:active {
            transform: scale(0.99);
        }

        .back {
            text-align: center;
            margin-top: 22px;
            font-size: 13px;
            color: #718078;
        }

        .back a {
            color: #265d3b;
            font-weight: 700;
            text-decoration: none;
        }

        .back a:hover {
            text-decoration: underline;
        }

        .footer {
            text-align: center;
            margin-top: 24px;
            font-size: 11px;
            color: #9aa59f;
        }

        @media (max-width: 500px) {
            .page {
                padding: 20px 16px;
            }

            .card {
                padding: 30px 24px;
                border-radius: 20px;
            }

            .title {
                font-size: 22px;
            }
        }
    </style>
</head>

<body>

<div class="page">

    <div class="card">

        <!-- CarbonWise Logo -->
        <div class="logo-container">
            <img
                src="{{ asset('images/carbonwise-logo.png') }}"
                alt="CarbonWise"
                class="logo"
                onerror="this.style.display='none';"
            >
        </div>

        <div class="brand">
            CarbonWise
        </div>

        <!-- Title -->
        <h1 class="title">
            Reset Your Password
        </h1>

        <p class="description">
            Create a new password for your CarbonWise account.
            Make sure your password is secure and easy for you to remember.
        </p>

        <form method="POST" action="{{ route('password.store') }}">
            @csrf

            <!-- Reset Token -->
            <input
                type="hidden"
                name="token"
                value="{{ $request->route('token') }}"
            >

            <!-- Email -->
            <div class="field">
                <label for="email" class="label">
                    Email Address
                </label>

                <input
                    id="email"
                    class="input"
                    type="email"
                    name="email"
                    value="{{ old('email', $request->email) }}"
                    required
                    autofocus
                    autocomplete="username"
                >

                @if ($errors->get('email'))
                    @foreach ($errors->get('email') as $error)
                        <div class="error">
                            {{ $error }}
                        </div>
                    @endforeach
                @endif
            </div>

            <!-- Password -->
            <div class="field">
                <label for="password" class="label">
                    New Password
                </label>

                <div class="input-container">
                    <input
                        id="password"
                        class="input"
                        type="password"
                        name="password"
                        required
                        autocomplete="new-password"
                        placeholder="Enter your new password"
                    >
                    <!-- Eye Icon -->
                    <span class="toggle-password" onclick="togglePassword('password', 'eye-icon-1')">
                        <svg id="eye-icon-1" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </span>
                </div>
                
                <div class="helper-text">
                    Must be at least 6 characters, include 1 number, and 1 symbol (e.g., !@#$).
                </div>

                @if ($errors->get('password'))
                    @foreach ($errors->get('password') as $error)
                        <div class="error">
                            {{ $error }}
                        </div>
                    @endforeach
                @endif
            </div>

            <!-- Confirm Password -->
            <div class="field">
                <label for="password_confirmation" class="label">
                    Confirm New Password
                </label>

                <div class="input-container">
                    <input
                        id="password_confirmation"
                        class="input"
                        type="password"
                        name="password_confirmation"
                        required
                        autocomplete="new-password"
                        placeholder="Re-enter your new password"
                    >
                    <!-- Eye Icon -->
                    <span class="toggle-password" onclick="togglePassword('password_confirmation', 'eye-icon-2')">
                        <svg id="eye-icon-2" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </span>
                </div>

                @if ($errors->get('password_confirmation'))
                    @foreach ($errors->get('password_confirmation') as $error)
                        <div class="error">
                            {{ $error }}
                        </div>
                    @endforeach
                @endif
            </div>

            <!-- Submit -->
            <button type="submit" class="button">
                Reset Password
            </button>

        </form>

        <div class="back">
            Remember your password?
            <a href="{{ route('login') }}">Log In</a>
        </div>

        <div class="footer">
            CarbonWise · Personal Carbon Footprint Tracker
        </div>

    </div>

</div>

<!-- Script to toggle password visibility -->
<script>
    function togglePassword(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        
        if (input.type === "password") {
            input.type = "text";
            // Change to "Eye Off" SVG (with slash)
            icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
        } else {
            input.type = "password";
            // Change back to normal "Eye" SVG
            icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
        }
    }
</script>

</body>
</html>