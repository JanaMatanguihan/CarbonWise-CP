<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect immediately if the user already holds an active session token
if (isset($_SESSION['user_token'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

if (isset($_SESSION['reg_success_message'])) {
    $success = $_SESSION['reg_success_message'];
    unset($_SESSION['reg_success_message']);
}

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
    $identifier    = trim($_POST['username_or_email'] ?? ''); 
    $user_password = $_POST['password'] ?? '';

    if (empty($identifier) || empty($user_password)) {
        $error = "Please fill in all fields.";
    } else {
        // Format raw SR-Codes (e.g., 21-12345) to institutional emails
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

            // FLEXIBLE LOOKUP: Searches email, sr_code, or name columns
            $stmt = $pdo->prepare("
                SELECT * FROM users 
                WHERE LOWER(email) = LOWER(:identifier) 
                   OR LOWER(email) = LOWER(:formatted_email)
                   OR LOWER(sr_code) = LOWER(:identifier)
                   OR LOWER(name) = LOWER(:identifier)
                LIMIT 1
            ");
            
            $stmt->execute([
                ':identifier'      => $identifier,
                ':formatted_email' => $formatted_email
            ]);

            $user = $stmt->fetch();

            if ($user) {
                if (password_verify($user_password, $user['password'])) {
                    // REGENERATE SESSION TO PREVENT SESSION FIXATION
                    session_regenerate_id(true);

                    // Populate keys required by dashboard.php
                    $_SESSION['user_token']   = bin2hex(random_bytes(32));
                    $_SESSION['user_id']      = $user['id'];
                    $_SESSION['user_email']   = $user['email'];
                    $_SESSION['g_suite']      = $user['email'];
                    $_SESSION['user_name']    = $user['name'] ?? $identifier;
                    
                    unset($user['password']);
                    $_SESSION['user_profile'] = $user;

                    // DIRECT REDIRECT TO DASHBOARD
                    header('Location: dashboard.php');
                    exit; 
                } else {
                    $error = "Incorrect password. Please double-check your password and try again.";
                }
            } else {
                $error = "We couldn't find an account matching that SR-Code or email. Please check for typos or sign up first.";
            }

        } catch (PDOException $e) {
            $error = "Unable to connect right now. Please try again in a few moments.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CarbonWise - Login</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        .password-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        .password-wrapper input {
            width: 100%;
            padding-right: 40px;
        }
        .toggle-password-btn {
            position: absolute;
            right: 12px;
            cursor: pointer;
            color: #666;
            font-size: 16px;
            user-select: none;
            display: none;
            transition: color 0.2s;
        }
        .toggle-password-btn:hover {
            color: #098a38;
        }
    </style>
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
            <h2>Welcome Back to CarbonWise</h2>
            <p class="auth-subtitle">Sign in to access your sustainability portal</p>

            <form action="login.php" method="POST">
                <div class="form-group">
                    <label>SR-Code or Email</label>
                    <input type="text" name="username_or_email" placeholder="2x-xxxxx or email@domain.com" value="<?= isset($_POST['username_or_email']) ? htmlspecialchars($_POST['username_or_email'], ENT_QUOTES, 'UTF-8') : '' ?>" required>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="password" id="passwordInput" placeholder="Enter your password..." oninput="checkInputLength(this, 'togglePwdIcon')" required>
                        <i id="togglePwdIcon" class="fa-solid fa-eye-slash toggle-password-btn" onclick="toggleVisibility('passwordInput', this)"></i>
                    </div>
                </div>

                <button type="submit" class="submit-btn">Login</button>
            </form>
            <p class="switch-route-text" style="margin-top: 15px;">Don't have an account? <a href="register.php">Create one here.</a></p>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Login Unsuccessful',
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
                html: '<?= addslashes(htmlspecialchars($success, ENT_QUOTES, 'UTF-8')) ?>',
                confirmButtonColor: '#098a38'
            });
        </script>
    <?php endif; ?>

    <script>
        function checkInputLength(inputElement, iconId) {
            const icon = document.getElementById(iconId);
            if (inputElement.value.trim().length > 0) {
                icon.style.display = 'block';
            } else {
                icon.style.display = 'none';
            }
        }

        function toggleVisibility(fieldId, iconElement) {
            const inputField = document.getElementById(fieldId);
            if (inputField.type === "password") {
                inputField.type = "text";
                iconElement.classList.remove("fa-eye-slash");
                iconElement.classList.add("fa-eye");
            } else {
                inputField.type = "password";
                iconElement.classList.remove("fa-eye");
                iconElement.classList.add("fa-eye-slash");
            }
        }
    </script>

</body>
</html>