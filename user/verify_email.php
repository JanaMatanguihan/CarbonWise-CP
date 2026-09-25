<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Force consistent timezone handling between PHP and Database
date_default_timezone_set('UTC');

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

$token = trim($_GET['token'] ?? '');

if (empty($token)) {
    $error = "Invalid verification token provided.";
} else {
    try {
        $dsn = "pgsql:host={$db_host};port={$db_port};dbname={$db_name};sslmode=require;options='endpoint={$endpoint_id}'";
        $pdo = new PDO($dsn, $db_user, $db_pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        // Check if token exists regardless of expiration first
        $stmt = $pdo->prepare("SELECT id, status, verification_token_expires FROM users WHERE verification_token = :token LIMIT 1");
        $stmt->execute([':token' => $token]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = "Token not found in database. Check if the token column was saved properly during registration.";
        } else {
            // Compare using database time comparison via SQL instead of PHP strtotime to avoid timezone bugs
            $checkTime = $pdo->query("SELECT (verification_token_expires > NOW()) AS is_valid FROM users WHERE verification_token = " . $pdo->quote($token))->fetch();
            
            if (!$checkTime || !$checkTime['is_valid']) {
                $error = "This verification link has expired.";
            } else {
                // Update status to Active, set email_verified_at timestamp, and clear token
                $update = $pdo->prepare("UPDATE users SET status = 'Active', email_verified_at = NOW(), verification_token = NULL, verification_token_expires = NULL WHERE id = :id");
                $update->execute([':id' => $user['id']]);

                $success = "Your email has been successfully verified! You can now log in to CarbonWise.";
            }
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
</head>
<body>

    <div class="navbar" style="position: fixed; top: 0; left: 0; width: 100%; z-index: 1000; box-sizing: border-box;">
        <div class="logo-container" style="display: flex; align-items: center; gap: 12px;">
            <img src="logo.png" alt="CarbonWise Logo" style="height: 42px; width: auto; object-fit: contain;">
            <span class="logo-text">CarbonWise</span>
        </div>
    </div>

    <div class="page-container" style="padding-top: 120px; text-align: center;">
        <div class="auth-card" style="max-width: 450px; margin: 0 auto;">
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