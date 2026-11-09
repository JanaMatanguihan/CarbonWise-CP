<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['user_token'])) {
    header('Location: dashboard.php');
    exit;
}

// Force consistent timezone handling
date_default_timezone_set('UTC');

// ==========================================
// --- LOAD ENVIRONMENT VARIABLES (.ENV) ---
// ==========================================
function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;

        list($name, $value) = explode('=', $line, 2) + [NULL, NULL];
        if ($name && $value !== NULL) {
            $name = trim($name);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            $_ENV[$name] = $value;
            putenv("{$name}={$value}");
        }
    }
}

// Check current directory first, then fallback to parent directory
if (file_exists(__DIR__ . '/.env')) {
    loadEnv(__DIR__ . '/.env');
} else {
    loadEnv(dirname(__DIR__) . '/.env');
}

function env($key, $default = '') {
    $value = $_ENV[$key] ?? getenv($key);
    if ($value === false || $value === null || $value === '') {
        return $default;
    }
    return trim($value, " \t\n\r\0\x0B\"'");
}

$error   = '';
$success = '';

// ==========================================
// --- CONFIGURATION FROM ENV ---
// ==========================================
$app_url          = env('APP_URL', 'https://carbonwise.up.railway.app');
$brevo_api_key    = env('BREVO_API_KEY');
$brevo_api_url    = env('BREVO_API_URL', 'https://api.brevo.com/v3');
$brevo_from_email = env('BREVO_FROM_EMAIL', env('MAIL_FROM_ADDRESS'));
$brevo_from_name  = env('BREVO_FROM_NAME', env('MAIL_FROM_NAME', 'CarbonWise'));

$db_host = env('DB_HOST');
$db_port = env('DB_PORT', '5432');
$db_name = env('DB_DATABASE', env('DB_NAME'));
$db_user = env('DB_USERNAME', env('DB_USER'));
$db_pass = env('DB_PASSWORD', env('DB_PASS'));

// Show the exact Brevo reason on localhost, or anywhere SHOW_MAIL_ERRORS=true (turn it off once emails work)
$cw_host       = $_SERVER['HTTP_HOST'] ?? '';
$cw_is_local   = (strpos($cw_host, 'localhost') === 0 || strpos($cw_host, '127.0.0.1') === 0);
$show_mail_err = $cw_is_local || strtolower(env('SHOW_MAIL_ERRORS', 'false')) === 'true';

// Fail fast if required settings are missing (no hardcoded fallbacks on purpose)
$missing_config = [];
foreach (['BREVO_API_KEY' => $brevo_api_key, 'BREVO_FROM_EMAIL' => $brevo_from_email, 'DB_HOST' => $db_host,
          'DB_DATABASE' => $db_name, 'DB_USERNAME' => $db_user, 'DB_PASSWORD' => $db_pass] as $k => $v) {
    if ($v === '' || $v === null) {
        $missing_config[] = $k;
    }
}
$config_ok = empty($missing_config);
if (!$config_ok) {
    error_log('CarbonWise config error: missing ' . implode(', ', $missing_config));
    $error = $show_mail_err
        ? 'Server configuration error. Missing: ' . implode(', ', $missing_config)
        : 'Server configuration error. Please contact the administrator.';
}

$endpoint_id = explode('.', (string) $db_host)[0] ?? '';

/**
 * Sends a transactional email using Brevo's v3 REST API via cURL.
 * Returns true on success. On failure it returns false and stores the exact
 * reason in $GLOBALS['brevo_last_error'] (also written to the PHP error log).
 */
function sendBrevoEmail($apiKey, $apiUrl, $senderEmail, $senderName, $recipientEmail, $subject, $htmlContent) {
    $GLOBALS['brevo_last_error'] = '';
    $endpoint = rtrim($apiUrl, '/') . '/smtp/email';

    $payload = [
        'sender'      => ['name' => $senderName, 'email' => $senderEmail],
        'to'          => [['email' => $recipientEmail]],
        'subject'     => $subject,
        'htmlContent' => $htmlContent
    ];

    $curlOptions = [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'accept: application/json',
            'api-key: ' . trim($apiKey),
            'content-type: application/json'
        ],
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT        => 20
    ];

    // XAMPP on Windows often has no CA bundle configured, which makes HTTPS calls fail
    // with "SSL certificate problem". If php.ini has none, try the common XAMPP locations.
    if (!ini_get('curl.cainfo') && !ini_get('openssl.cafile')) {
        $caCandidates = [
            'C:\\xampp\\php\\extras\\ssl\\cacert.pem',
            'C:\\xampp\\apache\\bin\\curl-ca-bundle.crt',
            'C:\\xampp\\php\\cacert.pem',
            'C:\\xampp\\cacert.pem',
            __DIR__ . '/cacert.pem'
        ];
        foreach ($caCandidates as $caFile) {
            if (is_file($caFile)) {
                $curlOptions[CURLOPT_CAINFO] = $caFile;
                break;
            }
        }
    }

    if (!function_exists('curl_init')) {
        $GLOBALS['brevo_last_error'] = 'The PHP cURL extension is not enabled (enable extension=curl in php.ini and restart Apache).';
        error_log('Brevo API Email Error: ' . $GLOBALS['brevo_last_error']);
        return false;
    }

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, $curlOptions);

    $response  = curl_exec($ch);
    $httpCode  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErrNo = curl_errno($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false || $curlErrNo !== 0) {
        $GLOBALS['brevo_last_error'] = "Could not reach Brevo (cURL error {$curlErrNo}): {$curlError}";
        error_log('Brevo API Email Error: ' . $GLOBALS['brevo_last_error']);
        return false;
    }

    if ($httpCode !== 200 && $httpCode !== 201) {
        $decoded = json_decode($response, true);
        $detail  = is_array($decoded)
            ? trim(($decoded['code'] ?? '') . ': ' . ($decoded['message'] ?? ''), ': ')
            : substr((string) $response, 0, 300);
        $GLOBALS['brevo_last_error'] = "Brevo rejected the request (HTTP {$httpCode}) " . $detail;
        error_log('Brevo API Email Error: ' . $GLOBALS['brevo_last_error']);
        return false;
    }

    return true;
}

// ==========================================
// --- HANDLE FORGOT PASSWORD REQUEST ---
// ==========================================
$submitted_email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $config_ok) {
    $submitted_email = trim($_POST['email'] ?? '');
    $email = strtolower($submitted_email);

    // Allow students to type just their SR-Code (e.g. 23-39638)
    if (preg_match('/^\d{2}-\d{4,6}$/', $email)) {
        $email .= '@g.batstate-u.edu.ph';
    }

    $now = time();

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (!empty($_SESSION['fp_last_request']) && ($now - (int) $_SESSION['fp_last_request']) < 60) {
        $error = "Please wait a minute before requesting another link.";
    } else {
        $_SESSION['fp_last_request'] = $now;

        // Same message whether or not the account exists, so nobody can find out who is registered
        $success = "If an account exists for that email, we have sent a message with the next steps. Please check your inbox (and your Spam folder).";

        try {
            $dsn_options = "sslmode=require";
            if (!empty($endpoint_id)) {
                $dsn_options .= ";options='endpoint={$endpoint_id}'";
            }

            $dsn = "pgsql:host={$db_host};port={$db_port};dbname={$db_name};{$dsn_options}";
            $pdo = new PDO($dsn, $db_user, $db_pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false
            ]);

            $stmt = $pdo->prepare("SELECT id, name, email, status, remember_token FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if ($user) {
                $base_domain = rtrim($app_url, '/');
                $safe_name   = htmlspecialchars((string) $user['name'], ENT_QUOTES, 'UTF-8');
                $is_pending  = (strcasecmp(trim((string) ($user['status'] ?? '')), 'Pending Verification') === 0);

                if ($is_pending) {
                    // Account was never verified: resend the verification link instead of a reset link
                    // (a reset would wipe the verification token and leave the account stuck).
                    $token = !empty($user['remember_token']) ? $user['remember_token'] : bin2hex(random_bytes(32));

                    if (empty($user['remember_token'])) {
                        $upd = $pdo->prepare("UPDATE users SET remember_token = :token, updated_at = :now WHERE id = :id");
                        $upd->execute([':token' => $token, ':now' => date('Y-m-d H:i:s'), ':id' => $user['id']]);
                    }

                    $link    = "{$base_domain}/verify_email.php?token={$token}";
                    $subject = "CarbonWise - Verify Your Email Address";
                    $html = "
                        <div style='font-family: Arial, sans-serif; padding: 20px; color: #333;'>
                            <h2 style='color: #098a38;'>Verify your CarbonWise account</h2>
                            <p>Hello {$safe_name},</p>
                            <p>We received a request to reset your password, but your email address has not been verified yet. Please verify it first by clicking the button below.</p>
                            <p style='margin: 30px 0;'>
                                <a href='{$link}' style='background-color: #098a38; color: #ffffff; padding: 12px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Verify Email Address</a>
                            </p>
                            <p>If the button does not work, copy and paste this link into your browser:</p>
                            <p><a href='{$link}'>{$link}</a></p>
                            <hr style='border: none; border-top: 1px solid #eee; margin-top: 20px;'>
                            <p style='font-size: 12px; color: #777;'>If you did not make this request, you can safely ignore this email.</p>
                        </div>";
                } else {
                    // Verified account: create a reset token (reset_password.php reads it from remember_token)
                    $token = bin2hex(random_bytes(32));
                    $upd = $pdo->prepare("UPDATE users SET remember_token = :token, updated_at = :now WHERE id = :id");
                    $upd->execute([':token' => $token, ':now' => date('Y-m-d H:i:s'), ':id' => $user['id']]);

                    $link    = "{$base_domain}/reset_password.php?token={$token}";
                    $subject = "CarbonWise - Reset Your Password";
                    $html = "
                        <div style='font-family: Arial, sans-serif; padding: 20px; color: #333;'>
                            <h2 style='color: #098a38;'>Reset your CarbonWise password</h2>
                            <p>Hello {$safe_name},</p>
                            <p>We received a request to reset the password for your CarbonWise account. Click the button below to choose a new password.</p>
                            <p style='margin: 30px 0;'>
                                <a href='{$link}' style='background-color: #098a38; color: #ffffff; padding: 12px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Reset Password</a>
                            </p>
                            <p>If the button does not work, copy and paste this link into your browser:</p>
                            <p><a href='{$link}'>{$link}</a></p>
                            <hr style='border: none; border-top: 1px solid #eee; margin-top: 20px;'>
                            <p style='font-size: 12px; color: #777;'>If you did not request a password reset, you can safely ignore this email. Your password will not change.</p>
                        </div>";
                }

                $mailSent = sendBrevoEmail(
                    $brevo_api_key,
                    $brevo_api_url,
                    $brevo_from_email,
                    $brevo_from_name,
                    $user['email'],
                    $subject,
                    $html
                );

                if (!$mailSent && $show_mail_err && !empty($GLOBALS['brevo_last_error'])) {
                    $success .= " [Debug] " . $GLOBALS['brevo_last_error'];
                }
            }

        } catch (PDOException $e) {
            error_log("Forgot Password PDO Error: " . $e->getMessage());
            $success = '';
            $error = "Unable to process your request right now. Please try again later.";
        } catch (Exception $e) {
            error_log("Forgot Password System Error: " . $e->getMessage());
            $success = '';
            $error = "An unexpected error occurred. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CarbonWise - Forgot Password</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2D6A4F;
            --primary-hover: #1B4332;
            --bg-body: #F4F6F6;
            --card-bg: #FFFFFF;
            --text-dark: #1A202C;
            --text-muted: #718096;
            --border-color: #E2E8F0;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        body {
            background-color: var(--bg-body);
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 70px;
            background: var(--card-bg);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            padding: 0 5%;
            z-index: 1000;
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-container img {
            height: 40px;
            width: auto;
            object-fit: contain;
        }

        .logo-text {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--primary-color);
            letter-spacing: 0.5px;
        }

        .page-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 90px 20px 40px 20px;
        }

        .auth-card {
            background: var(--card-bg);
            width: 100%;
            max-width: 440px;
            padding: 40px;
            border-radius: 16px;
            border: 1px solid var(--border-color);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
        }

        .auth-icon {
            width: 56px;
            height: 56px;
            margin: 0 auto 16px auto;
            border-radius: 50%;
            background: #EAF6EE;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .auth-card h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 8px;
            text-align: center;
        }

        .auth-subtitle {
            font-size: 0.875rem;
            color: var(--text-muted);
            margin-bottom: 24px;
            text-align: center;
            line-height: 1.4;
        }

        .form-group {
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-group label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        .form-group input {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.95rem;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            background-color: #FAFAFA;
        }

        .form-group input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(45, 106, 79, 0.15);
            background-color: #FFFFFF;
        }

        .submit-btn {
            width: 100%;
            padding: 12px;
            background-color: var(--primary-color);
            color: #FFFFFF;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
            margin-top: 10px;
        }

        .submit-btn:hover {
            background-color: var(--primary-hover);
        }

        .submit-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .switch-route-text {
            text-align: center;
            margin-top: 20px;
            font-size: 0.875rem;
        }

        .switch-route-text a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }

        .switch-route-text a:hover {
            text-decoration: underline;
        }

        /* --- RESPONSIVE MEDIA QUERIES --- */
        @media (max-width: 600px) {
            .navbar {
                padding: 0 16px;
                height: 60px;
            }

            .logo-container img {
                height: 32px;
            }

            .logo-text {
                font-size: 1.1rem;
            }

            .page-container {
                padding: 75px 16px 20px 16px;
            }

            .auth-card {
                padding: 24px 20px;
                border-radius: 12px;
            }

            .auth-card h2 {
                font-size: 1.3rem;
            }

            .auth-subtitle {
                font-size: 0.8rem;
                margin-bottom: 20px;
            }
        }
    </style>
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#1B4332">
</head>
<body>

    <div class="navbar">
        <div class="logo-container">
            <img src="logo.png" alt="CarbonWise Logo">
            <span class="logo-text">CarbonWise</span>
        </div>
    </div>

    <div class="page-container">
        <div class="auth-card">
            <div class="auth-icon"><i class="fa-solid fa-key"></i></div>
            <h2>Forgot Password?</h2>
            <p class="auth-subtitle">Enter your email or SR-Code and we will send you a link to reset your password.</p>

            <form action="forgot_password.php" method="POST" id="forgotForm">
                <div class="form-group">
                    <label for="emailField">Email or SR-Code</label>
                    <input type="text" id="emailField" name="email" placeholder="00-00000@g.batstate-u.edu.ph" value="<?= htmlspecialchars($submitted_email, ENT_QUOTES, 'UTF-8') ?>" autocomplete="email" required autofocus>
                </div>

                <button type="submit" class="submit-btn" id="submitBtn">Send Reset Link</button>
            </form>

            <p class="switch-route-text"><a href="login.php">Back to Login</a></p>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <script>
            Swal.fire({
                icon: 'warning',
                title: 'Unable to Continue',
                text: <?= json_encode($error) ?>,
                confirmButtonColor: '#098a38'
            });
        </script>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Check Your Email',
                text: <?= json_encode($success) ?>,
                confirmButtonColor: '#098a38'
            });
        </script>
    <?php endif; ?>

    <script>
        // Prevent double submits
        document.getElementById('forgotForm').addEventListener('submit', function () {
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.textContent = 'Sending...';
        });
    </script>

    <script src="pwa-register.js"></script>
</body>
</html>