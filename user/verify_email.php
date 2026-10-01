<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
    return ($value !== false && $value !== null) ? trim($value, '"\'') : $default;
}

$error = ''; 
$success = '';

// ==========================================
// --- CONFIGURATION FROM ENV ---
// ==========================================
$db_host     = env('DB_HOST', 'ep-red-hill-a5erg1sb-pooler.us-east-2.aws.neon.tech');
$db_port     = env('DB_PORT', '5432');
$db_name     = env('DB_DATABASE', 'neondb');
$db_user     = env('DB_USERNAME', 'neondb_owner'); 
$db_pass     = env('DB_PASSWORD', 'npg_B7h4oEQbqJdG'); 

if (empty($db_pass)) {
    die("Database Connection Error: DB_PASSWORD is missing or could not be loaded from .env.");
}

$endpoint_id = explode('.', $db_host)[0] ?? '';

$token = trim($_GET['token'] ?? '');

if (empty($token)) {
    $error = "Invalid verification token provided.";
} else {
    try {
        $dsn_options = "sslmode=require";
        if (!empty($endpoint_id)) {
            $dsn_options .= ";options='endpoint={$endpoint_id}'";
        }

        $dsn = "pgsql:host={$db_host};port={$db_port};dbname={$db_name};{$dsn_options}";
        $pdo = new PDO($dsn, $db_user, $db_pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        // Check if user exists with this token inside remember_token
        $stmt = $pdo->prepare("SELECT id, status FROM users WHERE remember_token = :token LIMIT 1");
        $stmt->execute([':token' => $token]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = "Invalid or already used verification token.";
        } else {
            // Update status to Active, fill email_verified_at timestamp, and clear remember_token
            $update = $pdo->prepare("UPDATE users SET status = 'Active', email_verified_at = NOW(), remember_token = NULL WHERE id = :id");
            $update->execute([':id' => $user['id']]);

            $success = "Your email has been successfully verified! You can now log in to CarbonWise.";
        }

    } catch (PDOException $e) {
        $error = "Database Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CarbonWise - Email Verification</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* Base Reset & Box Sizing */
        *, *::before, *::after {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            padding: 12px 24px;
            box-sizing: border-box;
        }

        .page-container {
            width: 100%;
            padding-top: 120px;
            padding-bottom: 40px;
            padding-left: 16px;
            padding-right: 16px;
            text-align: center;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-grow: 1;
        }

        .auth-card {
            width: 100%;
            max-width: 450px;
            margin: 0 auto;
        }

        /* Responsive Breakpoints */
        @media screen and (max-width: 768px) {
            .page-container {
                padding-top: 100px;
                padding-left: 12px;
                padding-right: 12px;
            }
        }

        @media screen and (max-width: 480px) {
            .navbar img {
                height: 34px !important;
            }
            .logo-text {
                font-size: 1.1rem;
            }
            .auth-card {
                padding: 20px 16px;
            }
        }
    </style>
</head>
<body>

    <div class="navbar">
        <div class="logo-container" style="display: flex; align-items: center; gap: 12px;">
            <img src="logo.png" alt="CarbonWise Logo" style="height: 42px; width: auto; object-fit: contain;">
            <span class="logo-text">CarbonWise</span>
        </div>
    </div>

    <div class="page-container">
        <div class="auth-card">
            <h2>Email Verification</h2>
            <p style="color: #666; margin: 15px 0;">Processing your account verification, please wait...</p>
            <a href="login.php" class="submit-btn" style="display: block; text-decoration: none; line-height: 40px; margin-top: 20px;">Go to Login</a>
        </div>
    </div>

    <?php if (!empty($success)): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Email Verified!',
                text: '<?= addslashes($success) ?>',
                confirmButtonColor: '#098a38',
                confirmButtonText: 'Proceed to Login',
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'login.php';
                }
            });
        </script>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Verification Failed',
                text: '<?= addslashes($error) ?>',
                confirmButtonColor: '#e74c3c',
                confirmButtonText: 'Back to Login',
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'login.php';
                }
            });
        </script>
    <?php endif; ?>

</body>
</html>