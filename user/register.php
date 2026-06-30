<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';
$success = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sr_code        = trim($_POST['sr_code']);
    $full_name      = trim($_POST['name']); 
    $password       = $_POST['password'];
    $confirm_pwd    = $_POST['confirm_password'];
    $campus         = $_POST['campus'] ?? '';
    $year_level     = $_POST['year_level'] ?? '';
    $department     = $_POST['department'] ?? '';
    $role           = 'student';
    $email          = $sr_code . "@g.batstate-u.edu.ph";

    // --- GUARD CHECK: Validation rules ---
    if (empty($year_level)) {
        $error = "Please select your year level.";
    } elseif ($password !== $confirm_pwd) {
        $error = "Passwords do not match. Please verify your entries.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        $anon_key = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImN2bGlicnl6cWhvenRidXR5dmJ4Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3ODIyMDgxNTcsImV4cCI6MjA5Nzc4NDE1N30.q0vj8nBE4_SPVs8DDDeBOnzu8rpvGdfA5GXQpGp5rWs';
        
        // --- STEP 1: CREATE USER IN SUPABASE AUTH WITH REDIRECT TRACKING ---
        $auth_url = 'https://cvlibryzqhoztbutyvbx.supabase.co/auth/v1/signup';
        
        $auth_payload = [
            'email' => $email,
            'password' => $password,
            'options' => [
                'redirectTo' => 'http://localhost/user/verified.php'
            ],
            'data' => [
                'full_name' => $full_name,
                'name' => $full_name, 
                'role' => $role,
                'sr_code' => $sr_code,
                'campus' => $campus,
                'year_level' => (int)$year_level,
                'department' => $department
            ]
        ];

        $ch = curl_init($auth_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($auth_payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'apiKey: ' . $anon_key,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $auth_response = curl_exec($ch);
        $auth_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $auth_data = json_decode($auth_response, true);

        if (($auth_http_code === 200 || $auth_http_code === 201) && isset($auth_data['id'])) {
            
            // --- STEP 2: INSERT PROFILE INTO CUSTOM user_info TABLE ---
            $db_url = 'https://cvlibryzqhoztbutyvbx.supabase.co/rest/v1/user_info';
            $db_payload = [
                'sr_code'    => $sr_code,
                'full_name'  => $full_name,
                'g_suite'    => $email,
                'password'   => $password, 
                'campus'     => $campus,
                'year_level' => (int)$year_level,
                'department' => $department,
                'role'       => $role
            ];

            $ch_db = curl_init($db_url);
            curl_setopt($ch_db, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch_db, CURLOPT_POST, true);
            curl_setopt($ch_db, CURLOPT_POSTFIELDS, json_encode($db_payload));
            curl_setopt($ch_db, CURLOPT_HTTPHEADER, [
                'apiKey: ' . $anon_key,
                'Authorization: Bearer ' . $anon_key,
                'Content-Type: application/json',
                'Prefer: return=representation'
            ]);

            curl_setopt($ch_db, CURLOPT_SSL_VERIFYPEER, false);

            $db_response = curl_exec($ch_db);
            $db_http_code = curl_getinfo($ch_db, CURLINFO_HTTP_CODE);
            curl_close($ch_db);

            $_SESSION['reg_success_message'] = "Registration successful! A verification link has been dispatched to your institutional email: <strong>" . htmlspecialchars($email) . "</strong>. Please verify your account before logging in.";
            
            header('Location: login.php');
            exit;
            
        } else {
            if (isset($auth_data['error_description'])) {
                $error = $auth_data['error_description'];
            } elseif (isset($auth_data['message'])) {
                $error = $auth_data['message'];
            } else {
                $error = "Registration failed (HTTP Status Code: " . $auth_http_code . ").";
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
    <title>CarbonWise - Register</title>
    <link rel="stylesheet" href="style.css">
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
            <p class="auth-subtitle">Create an account to get started</p>

            <?php if($error): ?>
                <div class="alert alert-danger" style="background-color: #fde8e8; color: #9b1c1c; padding: 12px; border-radius: 6px; margin-bottom: 15px; font-size: 0.85rem; border: 1px solid #fbd5d5;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if($message): ?>
                <div class="alert alert-success" style="background-color: #edfdfd; color: #0369a1; padding: 15px; border-radius: 6px; margin-bottom: 20px; font-size: 0.9rem; line-height: 1.5; border: 1px solid #bae6fd; text-align: left;">
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST">
                <div class="form-group">
                    <label>SR-Code</label>
                    <input type="text" name="sr_code" placeholder="2x-xxxxx" value="<?= isset($_POST['sr_code']) ? htmlspecialchars($_POST['sr_code']) : '' ?>" required>
                </div>

                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" placeholder="Enter your name..." value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>" required>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Enter your password..." required>
                </div>

                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" placeholder="Enter your password again..." required>
                </div>
                                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Year Level</label>
                    <select name="year_level" required style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc; background-color: #fff;">
                        <option value="">Select Year Level</option>
                        <option value="1" <?= (isset($_POST['year_level']) && $_POST['year_level'] == '1') ? 'selected' : '' ?>>1st Year</option>
                        <option value="2" <?= (isset($_POST['year_level']) && $_POST['year_level'] == '2') ? 'selected' : '' ?>>2nd Year</option>
                        <option value="3" <?= (isset($_POST['year_level']) && $_POST['year_level'] == '3') ? 'selected' : '' ?>>3rd Year</option>
                        <option value="4" <?= (isset($_POST['year_level']) && $_POST['year_level'] == '4') ? 'selected' : '' ?>>4th Year</option>
                        <option value="5" <?= (isset($_POST['year_level']) && $_POST['year_level'] == '5') ? 'selected' : '' ?>>5th Year</option>
                    </select>
                </div>

                <div class="form-row" style="display: flex; gap: 15px; margin-bottom: 15px;">
                    <div class="form-group" style="flex: 1;">
                        <label>Campus</label>
                        <select name="campus" required style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc;">
                            <option value="">Select Campus</option>
                            <option value="Lipa" <?= (isset($_POST['campus']) && $_POST['campus'] === 'Lipa') ? 'selected' : '' ?>>Lipa Campus</option>
                            <option value="Pablo Borbon" <?= (isset($_POST['campus']) && $_POST['campus'] === 'Pablo Borbon') ? 'selected' : '' ?>>Pablo Borbon Campus</option>
                            <option value="Mabini" <?= (isset($_POST['campus']) && $_POST['campus'] === 'Mabini') ? 'selected' : '' ?>>Mabini Campus</option>
                            <option value="Malvar" <?= (isset($_POST['campus']) && $_POST['campus'] === 'Malvar') ? 'selected' : '' ?>>Malvar Campus</option>
                            <option value="Nasugbu" <?= (isset($_POST['campus']) && $_POST['campus'] === 'Nasugbu') ? 'selected' : '' ?>>Nasugbu Campus</option>
                            <option value="Lemery" <?= (isset($_POST['campus']) && $_POST['campus'] === 'Lemery') ? 'selected' : '' ?>>Lemery Campus</option>
                            <option value="Rosario" <?= (isset($_POST['campus']) && $_POST['campus'] === 'Rosario') ? 'selected' : '' ?>>Rosario Campus</option>
                            <option value="San Juan" <?= (isset($_POST['campus']) && $_POST['campus'] === 'San Juan') ? 'selected' : '' ?>>San Juan Campus</option>
                        </select>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Department</label>
                        <select name="department" required style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc;">
                            <option value="">Select Department</option>
                            <option value="CICS" <?= (isset($_POST['department']) && $_POST['department'] === 'CICS') ? 'selected' : '' ?>>CICS</option>
                            <option value="CTE" <?= (isset($_POST['department']) && $_POST['department'] === 'CTE') ? 'selected' : '' ?>>CTE</option>
                            <option value="CAS" <?= (isset($_POST['department']) && $_POST['department'] === 'CAS') ? 'selected' : '' ?>>CAS</option>
                            <option value="CET" <?= (isset($_POST['department']) && $_POST['department'] === 'CET') ? 'selected' : '' ?>>CET</option>
                            <option value="CABEIHM" <?= (isset($_POST['department']) && $_POST['department'] === 'CABEIHM') ? 'selected' : '' ?>>CABEIHM</option>
                        </select>
                    </div>
                </div>


                <button type="submit" class="submit-btn">Register to CarbonWise</button>
            </form>
            <p class="switch-route-text" style="margin-top: 15px;">Already have an account? <a href="login.php">Log in here.</a></p>
        </div>
    </div>

</body>
</html>