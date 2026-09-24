<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verified - CarbonWise</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto,
                         Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #F4F6F4;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
            color: #1F2933;
        }
        .card {
            background: white;
            border-radius: 24px;
            padding: 40px 32px;
            max-width: 420px;
            width: 100%;
            text-align: center;
            box-shadow: 0 4px 24px rgba(0,0,0,0.06);
        }
        .icon {
            width: 72px;
            height: 72px;
            background: #E8F5EE;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        .icon svg { width: 36px; height: 36px; stroke: #3AA76D; }
        h1 {
            font-size: 22px;
            font-weight: 700;
            color: #1F2933;
            margin-bottom: 10px;
        }
        p {
            font-size: 14px;
            line-height: 1.6;
            color: #4B5563;
            margin-bottom: 24px;
        }
        .cta {
            display: inline-block;
            background: #3AA76D;
            color: white !important;
            text-decoration: none;
            font-weight: 600;
            padding: 14px 28px;
            border-radius: 12px;
            font-size: 14px;
            transition: background 0.2s;
        }
        .cta:hover { background: #2E8B57; }
        .hint {
            font-size: 12px;
            color: #9CA3AF;
            margin-top: 18px;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                 stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
        </div>

        <h1>Email Verified!</h1>

        <p>{{ $message ?? 'Your email has been verified successfully.' }}</p>

        @isset($deepLink)
            <a class="cta" href="{{ $deepLink }}" id="openApp">Open CarbonWise App</a>
            <p class="hint">
                If the app doesn't open automatically, tap the button above.<br>
                You can also log in now on the CarbonWise mobile app.
            </p>
        @endisset
    </div>

    @isset($deepLink)
    <script>
        window.addEventListener('load', function () {
            setTimeout(function () {
                window.location.href = "{{ $deepLink }}";
            }, 900);
        });
    </script>
    @endisset
</body>
</html>