<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';
$success_message = '';
$trigger_signup_alert = false;   // Flag for entirely missing accounts
$trigger_password_alert = false; // Flag for wrong passwords

// Check if there's a registration success message waiting for us
if (isset($_SESSION['reg_success_message'])) {
    $success_message = $_SESSION['reg_success_message'];
    unset($_SESSION['reg_success_message']); // Clear it so it doesn't show again on refresh
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sr_code  = trim($_POST['sr_code']);
    $password = $_POST['password'];
    $email    = $sr_code . "@g.batstate-u.edu.ph"; 

    $payload = [
        'email' => $email,
        'password' => $password
    ];

    $url = 'https://cvlibryzqhoztbutyvbx.supabase.co/auth/v1/token?grant_type=password';
    $anon_key = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImN2bGlicnl6cWhvenRidXR5dmJ4Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3ODIyMDgxNTcsImV4cCI6MjA5Nzc4NDE1N30.q0vj8nBE4_SPVs8DDDeBOnzu8rpvGdfA5GXQpGp5rWs';
    
    $headers = [
        'apiKey: ' . $anon_key,
        'Authorization: Bearer ' . $anon_key,
        'Content-Type: application/json'
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); 

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        $error = "cURL Error: " . curl_error($ch);
        curl_close($ch);
    } else {
        curl_close($ch);
        $res_data = json_decode($response, true);
        
        if ($http_code === 200) {
            $_SESSION['user_token'] = $res_data['access_token'];

            $table_name = 'user_info'; 
            $fetch_url = "https://cvlibryzqhoztbutyvbx.supabase.co/rest/v1/{$table_name}?sr_code=eq." . urlencode($sr_code) . "&select=*";
            
            $ch_fetch = curl_init($fetch_url);
            curl_setopt($ch_fetch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch_fetch, CURLOPT_HTTPHEADER, [
                'apiKey: ' . $anon_key,
                'Authorization: Bearer ' . $res_data['access_token'], 
                'Content-Type: application/json'
            ]);
            curl_setopt($ch_fetch, CURLOPT_SSL_VERIFYPEER, false);
            
            $fetch_response = curl_exec($ch_fetch);
            $fetch_code = curl_getinfo($ch_fetch, CURLINFO_HTTP_CODE);
            curl_close($ch_fetch);
            
            if ($fetch_code === 200) {
                $user_records = json_decode($fetch_response, true);
                if (!empty($user_records)) {
                    $_SESSION['user_data'] = $user_records[0]; 
                } else {
                    $_SESSION['user_data'] = $res_data['user']; 
                }
            } else {
                $_SESSION['user_data'] = $res_data['user']; 
            }
            
            header('Location: dashboard.php');
            exit;
        } else {
            $error_desc = $res_data['error_description'] ?? ($res_data['msg'] ?? ($res_data['error'] ?? ''));
            
            // Checking if the error message signals email-not-found vs bad password
            if (strpos($error_desc, 'Email not found') !== false) {
                $trigger_signup_alert = true;
            } elseif (strpos($error_desc, 'Invalid login credentials') !== false) {
                // If Supabase is configured to hide whether an email exists, it returns "Invalid login credentials".
                // In modern web setups, we safely handle this via an incorrect password alert layout, or fallback to the error string.
                $trigger_password_alert = true;
            } else {
                $error = $error_desc ? $error_desc : 'Invalid login credentials.';
            }
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
    
    <style>
        /* Custom styled SweetAlert elements designed around your sage/emerald layout */
        .transparent-swal {
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            background: rgba(255, 255, 255, 0.75) !important; /* Frosted white glass layout */
            border: 1px solid rgba(255, 255, 255, 0.4);
            border-radius: 16px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.1);
        }
        .swal-title-custom {
            color: #1b4332 !important; /* Dark Forest Green font headings */
            font-family: inherit;
        }
        .swal-text-custom {
            color: #1b4332 !important; /* Muted corporate green context description */
            font-family: inherit;
        }
    </style>
</head>
<body>

    <div class="navbar">
        <div class="logo-container" style="display: flex; align-items: center; gap: 12px;">
            <img src="logo.png" alt="CarbonWise Logo" style="height: 42px; width: auto; object-fit: contain;">
            <span class="logo-text">CarbonWise</span>
        </div>
        <div class="nav-links">
            <a href="#">Features</a>
            <a href="#">About</a>
            <a href="#">Contact</a>
        </div>
    </div>

    <div class="page-container">
        <div class="auth-card">
            <h2>Welcome to CarbonWise</h2>
            <p class="auth-subtitle">Log in to your account</p>

            <?php if($success_message): ?>
                <div class="alert alert-success" style="background-color: #edfdfd; color: #0369a1; padding: 15px; border-radius: 6px; margin-bottom: 20px; font-size: 0.9rem; line-height: 1.5; border: 1px solid #bae6fd; text-align: left;">
                    <?= $success_message ?>
                </div>
            <?php endif; ?>

            <?php if($error): ?>
                <div class="alert alert-danger" style="background-color: #fde8e8; color: #9b1c1c; padding: 12px; border-radius: 6px; margin-bottom: 15px; font-size: 0.85rem; border: 1px solid #fbd5d5;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="form-group">
                    <label>SR-Code</label>
                    <input type="text" name="sr_code" placeholder="2x-xxxxx" value="<?= isset($_POST['sr_code']) ? htmlspecialchars($_POST['sr_code']) : '' ?>" required>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Enter Password" required>
                </div>

                <button type="submit" class="submit-btn">Log in</button>
            </form>
            <p class="switch-route-text" style="margin-top: 15px;">Don't have an account yet? <a href="register.php">Click here to Sign up.</a></p>
        </div>
    </div>

    <?php if ($trigger_signup_alert): ?>
    <script>
        Swal.fire({
            title: 'Account Not Found',
            text: "We couldn't find an institutional profile matching that SR-Code. Would you like to register an account on the network?",
            icon: 'warning',
            iconColor: '#dc333e',                  
            backdrop: 'rgba(45, 106, 79, 0.25)',     
            showCancelButton: true,
            confirmButtonColor: '#1e5631',           
            cancelButtonColor: '#7f8c8d',            
            confirmButtonText: 'Register Now',
            cancelButtonText: 'Try Again',
            customClass: {
                popup: 'transparent-swal',
                title: 'swal-title-custom',
                htmlContainer: 'swal-text-custom'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'register.php';
            }
        });
    </script>
    <?php endif; ?>

    <?php if ($trigger_password_alert): ?>
    <script>
        Swal.fire({
            title: 'Authentication Failed',
            text: "The password you entered is incorrect, or the account credentials do not match our database records. Please try again.",
            icon: 'error',
            iconColor: '#e74c3c',                  
            backdrop: 'rgba(192, 57, 43, 0.15)',     
            showCancelButton: false,
            confirmButtonColor: '#1e5631',           
            confirmButtonText: 'Dismiss and Re-try',
            customClass: {
                popup: 'transparent-swal',
                title: 'swal-title-custom',
                htmlContainer: 'swal-text-custom'
            }
        });
    </script>
    <?php endif; ?>

</body>
</html>