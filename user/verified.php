<?php
// verified.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear any previous registration errors from the session if they exist
if (isset($_SESSION['reg_error_message'])) {
    unset($_SESSION['reg_error_message']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Confirmed | CarbonWise</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }

        body {
            background-color: #f4f6f8;
            color: #2d3748;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .success-card {
            background: #ffffff;
            max-width: 450px;
            width: 100%;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            text-align: center;
        }

        .card-header {
            background-color: #1e5631;
            padding: 30px;
        }

        .logo-text {
            color: #ffffff;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .logo-text span {
            font-weight: 300;
            opacity: 0.9;
        }

        .card-body {
            padding: 40px 32px;
        }

        /* Matches the green accent circles used on the dashboard widgets */
        .icon-circle {
            width: 70px;
            height: 70px;
            background-color: #e6f4ea;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px auto;
        }

        .icon-circle svg {
            width: 36px;
            height: 36px;
            fill: #1e5631;
        }

        h2 {
            font-size: 22px;
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 12px;
        }

        p {
            font-size: 15px;
            line-height: 22px;
            color: #4a5568;
            margin-bottom: 32px;
        }

        .btn-login {
            display: block;
            background-color: #1e5631;
            color: #ffffff;
            text-decoration: none;
            padding: 14px 24px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 6px;
            box-shadow: 0 4px 6px -1px rgba(30, 86, 49, 0.2);
            transition: background-color 0.2s ease, transform 0.1s ease;
        }

        .btn-login:hover {
            background-color: #153e22;
        }

        .btn-login:active {
            transform: scale(0.98);
        }

        .footer {
            margin-top: 24px;
            font-size: 12px;
            color: #a0aec0;
        }
    </style>
</head>
<body>

    <div class="success-card">
        <div class="card-header">
            <div class="logo-text">CarbonWise</div>
        </div>

        <div class="card-body">
            <div class="icon-circle">
                <svg viewBox="0 0 24 24">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                </svg>
            </div>

            <h2>Account Verified!</h2>
            <p>Your institutional email has been successfully confirmed. You are now permitted to access the platform layout and track your department's eco-metrics.</p>

            <a href="login.php" class="btn-login">Proceed to Login</a>
            
            <div class="footer">
                Batangas State University Eco-Tracking Platform
            </div>
        </div>
    </div>

    <script>
        // Optional client-side verification logging
        // Supabase sends access tokens via the URL hash fragment (#access_token=...) on confirmation.
        // If your app uses frontend tokens later, this ensures they are preserved in console debugs.
        if (window.location.hash) {
            console.log("Authorization handshake tokens successfully received from Supabase Auth.");
        }
    </script>
</body>
</html>