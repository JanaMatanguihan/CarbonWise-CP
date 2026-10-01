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
            $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $_ENV[$name] = $value;
            putenv("{$name}={$value}");
        }
    }
}

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
    return html_entity_decode(trim($value, " \t\n\r\0\x0B\"'"), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

$error = '';
$success = '';
$token = trim($_GET['token'] ?? '');

// Verify token presence
if (empty($token)) {
    $error = "Invalid or missing password reset token.";
}

$db_host     = env('DB_HOST', 'ep-red-hill-a5erg1sb-pooler.us-east-2.aws.neon.tech');
$db_port     = env('DB_PORT', '5432');
$db_name     = env('DB_DATABASE', 'neondb');
$db_user     = env('DB_USERNAME', 'neondb_owner'); 
$db_pass     = env('DB_PASSWORD', 'npg_B7h4oEQbqJdG'); 

$endpoint_id = explode('.', $db_host)[0] ?? '';

// Handle Password Reset Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($token)) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
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

            // 1. Safe Token Match: check remember_token first
            $user = null;
            $tokenColumnUsed = 'remember_token';

            try {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token = :token LIMIT 1");
                $stmt->execute([':token' => $token]);
                $user = $stmt->fetch();
            } catch (PDOException $e) {
                // Ignore if remember_token column missing
            }

            // Fallback: check reset_token column
            if (!$user) {
                try {
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = :token LIMIT 1");
                    $stmt->execute([':token' => $token]);
                    $user = $stmt->fetch();
                    if ($user) {
                        $tokenColumnUsed = 'reset_token';
                    }
                } catch (PDOException $e) {
                    // Ignore if reset_token column missing
                }
            }

            if ($user) {
                // Hash new password using bcrypt
                $hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

                // Construct UPDATE query dynamically based on existing schema
                $updateFields = ["password = :password", "{$tokenColumnUsed} = NULL"];
                if (array_key_exists('updated_at', $user)) {
                    $updateFields[] = "updated_at = NOW()";
                }

                $updateSql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = :id";
                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->execute([
                    ':password' => $hashed_password,
                    ':id'       => $user['id']
                ]);

                $success = "Your password has been successfully reset! You can now log in with your new password.";
            } else {
                $error = "This password reset link is invalid or has expired.";
            }

        } catch (PDOException $e) {
            error_log("Reset Password PDO Error: " . $e->getMessage());
            // Displays detailed error message for troubleshooting
            $error = "Database Error: " . $e->getMessage();
        } catch (Exception $e) {
            error_log("Reset Password System Error: " . $e->getMessage());
            $error = "System Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CarbonWise - Set New Password</title>
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
            <h2>Set New Password</h2>
            <p class="auth-subtitle">Please enter and confirm your new password</p>

            <form action="reset_password.php?token=<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>" method="POST">
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="password" placeholder="At least 8 characters" required>
                </div>

                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" placeholder="Re-enter password" required>
                </div>

                <button type="submit" class="submit-btn">Reset Password</button>
            </form>
            <p class="switch-route-text"><a href="login.php">Back to Login</a></p>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Reset Failed',
                text: '<?= addslashes(htmlspecialchars($error, ENT_QUOTES, 'UTF-8')) ?>',
                confirmButtonColor: '#e74c3c'
            });
        </script>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Password Updated',
                text: '<?= addslashes($success) ?>',
                confirmButtonColor: '#098a38'
            }).then(() => {
                window.location.href = 'login.php';
            });
        </script>
    <?php endif; ?>

</body>
</html>