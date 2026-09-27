<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['user_token'])) {
    header('Location: dashboard.php');
    exit;
}

// ==========================================
// --- LOAD ENVIRONMENT VARIABLES (.ENV) ---
// ==========================================
/**
 * Custom lightweight .env loader (No Composer required)
 */
function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue; // Skip comments and empty lines
        
        list($name, $value) = explode('=', $line, 2) + [NULL, NULL];
        if ($name && $value !== NULL) {
            $name = trim($name);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            $_ENV[$name] = $value;
            putenv("{$name}={$value}");
        }
    }
}

// Load .env from root directory (parent folder of /user)
loadEnv(dirname(__DIR__) . '/.env');

/**
 * Helper to fetch environment variables safely with fallback
 */
function env($key, $default = '') {
    $value = $_ENV[$key] ?? getenv($key);
    return ($value !== false && $value !== null) ? trim($value, '"\'') : $default;
}

$error = '';
$success = '';

// ==========================================
// --- CONFIGURATION FROM ENV ---
// ==========================================
$brevo_api_key   = env('BREVO_API_KEY', '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');
$brevo_api_url   = env('BREVO_API_URL', 'https://api.brevo.com/v3');
$brevo_from_email = env('BREVO_FROM_EMAIL', 'noreplycarbonwise@gmail.com');
$brevo_from_name  = env('BREVO_FROM_NAME', 'CarbonWise');

$db_host     = env('DB_HOST');
$db_port     = env('DB_PORT', '5432');
$db_name     = env('DB_DATABASE', 'neondb');
$db_user     = env('DB_USERNAME'); 
$db_pass     = env('DB_PASSWORD'); 

// Extract Neon endpoint ID if available in host string
$endpoint_id = explode('.', $db_host)[0] ?? '';

/**
 * Sends a transactional email using Brevo's v3 REST API via cURL
 */
function sendBrevoEmail($apiKey, $apiUrl, $senderEmail, $senderName, $recipientEmail, $subject, $htmlContent) {
    $endpoint = rtrim($apiUrl, '/') . '/smtp/email';

    $payload = [
        'sender' => [
            'name'  => $senderName,
            'email' => $senderEmail
        ],
        'to' => [
            [
                'email' => $recipientEmail
            ]
        ],
        'subject'     => $subject,
        'htmlContent' => $htmlContent
    ];

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'accept: application/json',
            'api-key: ' . $apiKey,
            'content-type: application/json'
        ],
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_TIMEOUT        => 10
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($httpCode === 201 || $httpCode === 200);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['username_or_email'] ?? '');

    if (empty($identifier)) {
        $error = "Please enter your SR-Code or email address.";
    } else {
        // Format raw SR-Codes (e.g., 21-12345) to institutional emails
        if (preg_match('/^\d{2}-\d{5}$/', $identifier)) {
            $formatted_email = $identifier . "@g.batstate-u.edu.ph";
        } else {
            $formatted_email = $identifier;
        }

        try {
            $dsn_options = "sslmode=require";
            if (!empty($endpoint_id)) {
                $dsn_options .= ";options='endpoint={$endpoint_id}'";
            }

            $dsn = "pgsql:host={$db_host};port={$db_port};dbname={$db_name};{$dsn_options}";
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false
            ];

            $pdo = new PDO($dsn, $db_user, $db_pass, $options);

            // Search for user
            $stmt = $pdo->prepare("
                SELECT id, email FROM users 
                WHERE LOWER(email) = LOWER(:identifier) 
                   OR LOWER(email) = LOWER(:formatted_email)
                   OR LOWER(sr_code) = LOWER(:identifier)
                LIMIT 1
            ");
            
            $stmt->execute([
                ':identifier'      => $identifier,
                ':formatted_email' => $formatted_email
            ]);

            $user = $stmt->fetch();

            if ($user) {
                // Generate secure random token and expiration (1 hour from now)
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

                // Save token to database
                $updateStmt = $pdo->prepare("
                    UPDATE users 
                    SET reset_token = :token, reset_token_expires = :expires 
                    WHERE id = :id
                ");
                $updateStmt->execute([
                    ':token'   => $token,
                    ':expires' => $expires,
                    ':id'      => $user['id']
                ]);

                // Construct reset link
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                $host = $_SERVER['HTTP_HOST'];
                $reset_link = "{$protocol}://{$host}/reset_password.php?token={$token}";

                // HTML Body for Brevo Email
                $subject = "CarbonWise - Password Reset Request";
                $htmlBody = "
                    <div style='font-family: Arial, sans-serif; padding: 20px; color: #333;'>
                        <h2 style='color: #098a38;'>CarbonWise Password Reset</h2>
                        <p>Hello,</p>
                        <p>We received a request to reset your password. Click the button below to set a new password. This link will expire in 1 hour.</p>
                        <p style='margin: 30px 0;'>
                            <a href='{$reset_link}' style='background-color: #098a38; color: #ffffff; padding: 12px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Reset Password</a>
                        </p>
                        <p>If the button doesn't work, copy and paste this link into your browser:</p>
                        <p><a href='{$reset_link}'>{$reset_link}</a></p>
                        <hr style='border: none; border-top: 1px solid #eee; margin-top: 20px;'>
                        <p style='font-size: 12px; color: #777;'>If you did not request a password reset, you can safely ignore this email.</p>
                    </div>
                ";

                // Send email via Brevo REST API using .env values
                $mailSent = sendBrevoEmail(
                    $brevo_api_key, 
                    $brevo_api_url,
                    $brevo_from_email, 
                    $brevo_from_name, 
                    $user['email'], 
                    $subject, 
                    $htmlBody
                );

                if ($mailSent) {
                    $success = "A password reset link has been sent to your email address.";
                } else {
                    $error = "Failed to send the password reset email. Please try again later.";
                }
            } else {
                // Prevent email enumeration while informing user
                $success = "If an account with that SR-Code or email exists, a password reset link will be sent.";
            }

        } catch (PDOException $e) {
            $error = "Unable to process your request right now. Please try again later.";
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
</head>
<body>

    <div class="navbar" style="position: fixed; top: 0; left: 0; width: 100%; z-index: 1000; box-sizing: border-box;">
        <div class="logo-container" style="display: flex; align-items: center; gap: 12px;">
            <img src="logo.png" alt="CarbonWise Logo" style="height: 42px; width: auto; object-fit: contain;">
            <span class="logo-text">CarbonWise</span>
        </div>
    </div>

    <div class="page-container" style="padding-top: 120px;">
        <div class="auth-card">
            <h2>Reset Password</h2>
            <p class="auth-subtitle">Enter your SR-Code or email address to reset your password</p>

            <form action="forgot_password.php" method="POST">
                <div class="form-group">
                    <label>SR-Code or Email</label>
                    <input type="text" name="username_or_email" placeholder="2x-xxxxx or email@domain.com" value="<?= isset($_POST['username_or_email']) ? htmlspecialchars($_POST['username_or_email'], ENT_QUOTES, 'UTF-8') : '' ?>" required>
                </div>

                <button type="submit" class="submit-btn">Send Reset Link</button>
            </form>
            <p class="switch-route-text" style="margin-top: 15px;">Remembered your password? <a href="login.php">Back to Login</a></p>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Request Failed',
                text: '<?= addslashes(htmlspecialchars($error, ENT_QUOTES, 'UTF-8')) ?>',
                confirmButtonColor: '#e74c3c'
            });
        </script>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Check Your Email',
                html: '<?= addslashes($success) ?>',
                confirmButtonColor: '#098a38'
            });
        </script>
    <?php endif; ?>

</body>
</html>