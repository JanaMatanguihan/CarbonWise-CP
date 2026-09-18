<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_token'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

// ==========================================
// --- NEON POSTGRESQL CONFIGURATION ---
// ==========================================
$db_host     = 'ep-red-hill-a5erg1sb-pooler.us-east-2.aws.neon.tech';
$endpoint_id = 'ep-red-hill-a5erg1sb-pooler'; 
$db_port     = '5432';
$db_name     = 'neondb';
$db_user     = 'neondb_owner'; 
$db_pass     = 'npg_B7h4oEQbqJdG'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['username_or_email'] ?? '');

    if (empty($identifier)) {
        $error = "Please enter your SR-Code or email address.";
    } else {
        if (preg_match('/^\d{2}-\d{5}$/', $identifier)) {
            $formatted_email = $identifier . "@g.batstate-u.edu.ph";
        } else {
            $formatted_email = $identifier;
        }

        try {
            $dsn = "pgsql:host={$db_host};port={$db_port};dbname={$db_name};sslmode=require;options='endpoint={$endpoint_id}'";
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false
            ];

            $pdo = new PDO($dsn, $db_user, $db_pass, $options);

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
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

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

                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                $host = $_SERVER['HTTP_HOST'];
                $reset_link = "{$protocol}://{$host}/reset_password.php?token={$token}";

                $success = "A password reset link has been generated.<br><br><strong>Reset Link:</strong><br><a href='{$reset_link}' style='color: #098a38; word-break: break-all;'>{$reset_link}</a>";
            } else {
                $error = "We couldn't find an account matching that SR-Code or email. Please check for typos.";
            }

        } catch (PDOException $e) {
            // Show explicit error message for debugging
            $error = "Database Error: " . $e->getMessage();
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
                title: 'Success!',
                html: '<?= addslashes($success) ?>',
                confirmButtonColor: '#098a38'
            });
        </script>
    <?php endif; ?>

</body>
</html>