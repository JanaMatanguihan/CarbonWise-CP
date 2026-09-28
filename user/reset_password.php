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

            // Locate user by remember_token or reset_token
            $stmt = $pdo->prepare("
                SELECT * FROM users 
                WHERE remember_token = :token 
                   OR reset_token = :token
                LIMIT 1
            ");
            $stmt->execute([':token' => $token]);
            $user = $stmt->fetch();

            if ($user) {
                // Hash new password using standard bcrypt
                $hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

                // Clear token and update password
                $hasRememberToken = array_key_exists('remember_token', $user);
                $tokenColumn = $hasRememberToken ? 'remember_token' : 'reset_token';

                $updateSql = "UPDATE users SET password = :password, {$tokenColumn} = NULL";
                if (array_key_exists('updated_at', $user)) {
                    $updateSql .= ", updated_at = NOW()";
                }
                $updateSql .= " WHERE id = :id";

                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->execute([
                    ':password' => $hashed_password,
                    ':id'       => $user['id']
                ]);

                $success = "Your password has been successfully reset! You can now log in with your new password.";
            } else {
                $error = "This password reset token is invalid or has expired.";
            }

        } catch (PDOException $e) {
            error_log("Reset Password PDO Error: " . $e->getMessage());
            $error = "Unable to reset password. Please try again later.";
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
            <p class="switch-route-text" style="margin-top: 15px;"><a href="login.php">Back to Login</a></p>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Error',
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