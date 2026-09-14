<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
    $role          = $_POST['role'] ?? ''; 
    $full_name     = trim($_POST['name'] ?? ''); 
    $password      = $_POST['password'] ?? '';
    $confirm_pwd   = $_POST['confirm_password'] ?? '';
    $campus        = $_POST['campus'] ?? '';
    $terms         = $_POST['terms'] ?? '';
    
    // Role-dependent assignments
    $sr_code       = ($role === 'student') ? trim($_POST['sr_code'] ?? '') : null;
    $year_level    = ($role === 'student') ? ($_POST['year_level'] ?? null) : null;
    $department    = ($role === 'student') ? ($_POST['department'] ?? null) : null;

    // Handle dynamic institutional routing schemas based on selected role
    if ($role === 'student') {
        $email = $sr_code . "@g.batstate-u.edu.ph";
    } else {
        $email = trim($_POST['email'] ?? '');
    }

    // --- GUARD CHECK: Validation rules ---
    if (empty($role)) {
        $error = "Please choose a role.";
    } elseif ($role === 'student' && empty($year_level)) {
        $error = "Please select your year level.";
    } elseif ($password !== $confirm_pwd) {
        $error = "Passwords do not match. Please verify your entries.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif (empty($terms)) {
        $error = "You must agree to the Terms and Conditions to register.";
    } else {
        try {
            $dsn = "pgsql:host={$db_host};port={$db_port};dbname={$db_name};sslmode=require;options='endpoint={$endpoint_id}'";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false
            ];

            $pdo = new PDO($dsn, $db_user, $db_pass, $options);

            // --- STEP 1: CHECK IF EMAIL ALREADY EXISTS ---
            $check_stmt = $pdo->prepare("SELECT id FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1");
            $check_stmt->execute([':email' => $email]);
            
            if ($check_stmt->fetch()) {
                $error = "You already have an account registered with this email address. Please log in instead.";
            } else {
                // --- STEP 2: SECURE BCRYPT PASSWORD HASHING ---
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $current_time    = date('Y-m-d H:i:s');

                // --- STEP 3: INSERT INTO NEON POSTGRESQL TABLE MATCHING EXACT SCHEMA ---
                $insert_sql = "INSERT INTO users 
                    (name, email, password, role, sr_code, campus, year_level, department, status, created_at, \"updated_at\") 
                    VALUES 
                    (:name, :email, :password, :role, :sr_code, :campus, :year_level, :department, 'Active', :created_at, :updated_at)";

                $insert_stmt = $pdo->prepare($insert_sql);
                $insert_stmt->execute([
                    ':name'       => $full_name,
                    ':email'      => $email,
                    ':password'   => $hashed_password,
                    ':role'       => $role,
                    ':sr_code'    => $sr_code,
                    ':campus'     => $campus,
                    ':year_level' => $year_level ? (int)$year_level : null,
                    ':department' => $department,
                    ':created_at' => $current_time,
                    ':updated_at' => $current_time
                ]);

                $_SESSION['reg_success_message'] = "Registration successful! Welcome to CarbonWise. You may log in directly now.";
                
                // Clear state variables before redirection
                unset($_POST);
                header('Location: login.php');
                exit;
            }

        } catch (PDOException $e) {
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
    <title>CarbonWise - Register</title>
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
        .terms-container {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 20px 0;
            font-size: 0.9rem;
            color: #4b5563;
        }
        .terms-container input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #2D6A4F;
            cursor: pointer;
        }
        .terms-container a {
            color: #2D6A4F;
            text-decoration: underline;
        }
        .terms-container a:hover {
            color: #1B4332;
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

    <div class="page-container" style="padding-top: 100px;">
        <div class="auth-card">
            <h2>Get Started with CarbonWise</h2>
            <p class="auth-subtitle">Create your account and start your journey today!</p>

            <form action="register.php" method="POST" id="registrationForm">
                <div class="form-group">
                    <label>Role</label>
                    <select name="role" id="roleSelector" required style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc; background-color: #fff;">
                        <option value="" disabled <?= !isset($_POST['role']) || $_POST['role'] === '' ? 'selected' : '' ?>>Click to choose role</option>
                        <option value="student" <?= (isset($_POST['role']) && $_POST['role'] === 'student') ? 'selected' : '' ?>>Student</option>
                        <option value="faculty" <?= (isset($_POST['role']) && $_POST['role'] === 'faculty') ? 'selected' : '' ?>>Faculty</option>
                        <option value="staff" <?= (isset($_POST['role']) && $_POST['role'] === 'staff') ? 'selected' : '' ?>>Non-Teaching Staff</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" placeholder="Enter your name..." value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>" required>
                </div>

                <div id="studentContainer" style="display: none;">
                    <div class="form-group">
                        <label>SR-Code</label>
                        <input type="text" name="sr_code" id="srCodeField" placeholder="2x-xxxxx" value="<?= isset($_POST['sr_code']) ? htmlspecialchars($_POST['sr_code']) : '' ?>">
                    </div>
                </div>

                <div id="staffContainer" style="display: none;">
                    <div class="form-group">
                        <label>G-Suite Email</label>
                        <input type="email" name="email" id="emailField" placeholder="example@g.batstate-u.edu.ph" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="password" id="passwordInput" placeholder="Enter your password..." oninput="checkInputLength(this, 'togglePwdIcon')" required>
                        <i id="togglePwdIcon" class="fa-solid fa-eye-slash toggle-password-btn" onclick="toggleVisibility('passwordInput', this)"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label>Confirm Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="confirm_password" id="confirmPasswordInput" placeholder="Enter your password again..." oninput="checkInputLength(this, 'toggleConfirmPwdIcon')" required>
                        <i id="toggleConfirmPwdIcon" class="fa-solid fa-eye-slash toggle-password-btn" onclick="toggleVisibility('confirmPasswordInput', this)"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label>Campus</label>
                    <select name="campus" required style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc;">
                        <option value="">Choose Campus</option>
                        <option value="Lipa Campus" <?= (isset($_POST['campus']) && $_POST['campus'] === 'Lipa Campus') ? 'selected' : '' ?>>Lipa Campus</option>
                        <option value="Pablo Borbon Campus" <?= (isset($_POST['campus']) && $_POST['campus'] === 'Pablo Borbon Campus') ? 'selected' : '' ?>>Pablo Borbon Campus</option>
                        <option value="Alangilan Campus" <?= (isset($_POST['campus']) && $_POST['campus'] === 'Alangilan Campus') ? 'selected' : '' ?>>Alangilan Campus</option>
                        <option value="LIMA Campus" <?= (isset($_POST['campus']) && $_POST['campus'] === 'LIMA Campus') ? 'selected' : '' ?>>LIMA Campus</option>
                        <option value="ARASOF Nasugbu Campus" <?= (isset($_POST['campus']) && $_POST['campus'] === 'ARASOF Nasugbu Campus') ? 'selected' : '' ?>>ARASOF Nasugbu Campus</option>
                        <option value="JPLPC Malvar Campus" <?= (isset($_POST['campus']) && $_POST['campus'] === 'JPLPC Malvar Campus') ? 'selected' : '' ?>>JPLPC Malvar Campus</option>
                        <option value="Lemery Campus" <?= (isset($_POST['campus']) && $_POST['campus'] === 'Lemery Campus') ? 'selected' : '' ?>>Lemery Campus</option>
                        <option value="Rosario Campus" <?= (isset($_POST['campus']) && $_POST['campus'] === 'Rosario Campus') ? 'selected' : '' ?>>Rosario Campus</option>
                        <option value="San Juan Campus" <?= (isset($_POST['campus']) && $_POST['campus'] === 'San Juan Campus') ? 'selected' : '' ?>>San Juan Campus</option>
                         <option value="BalayanCampus" <?= (isset($_POST['campus']) && $_POST['campus'] === 'BalayanCampus') ? 'selected' : '' ?>>Balayan Campus</option>
                        <option value="Lobo Campus" <?= (isset($_POST['campus']) && $_POST['campus'] === 'Lobo Campus') ? 'selected' : '' ?>>Lobo Campus</option>
                        <option value="Mabini Campus" <?= (isset($_POST['campus']) && $_POST['campus'] === 'Mabini Campus') ? 'selected' : '' ?>>Mabini Campus</option>
                    </select>
                </div>
                
                <div id="studentAcademicRow" class="form-row" style="display: none; gap: 15px; margin-bottom: 15px;">
                    <div class="form-group" style="flex: 1;">
                        <label>Year Level</label>
                        <select name="year_level" id="yearLevelField" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc; background-color: #fff;">
                            <option value="">Choose Year Level</option>
                            <option value="1st Year" <?= (isset($_POST['year_level']) && $_POST['year_level'] == '1st Year') ? 'selected' : '' ?>>1st Year</option>
                            <option value="2nd Year" <?= (isset($_POST['year_level']) && $_POST['year_level'] == '2nd Year') ? 'selected' : '' ?>>2nd Year</option>
                            <option value="3rd Year" <?= (isset($_POST['year_level']) && $_POST['year_level'] == '3rd Year') ? 'selected' : '' ?>>3rd Year</option>
                            <option value="4th Year" <?= (isset($_POST['year_level']) && $_POST['year_level'] == '4th Year') ? 'selected' : '' ?>>4th Year</option>
                            <option value="5th Year" <?= (isset($_POST['year_level']) && $_POST['year_level'] == '5th Year') ? 'selected' : '' ?>>5th Year</option>
                        </select>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Department</label>
                        <select name="department" id="departmentField" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc;">
                            <option value="">Choose Department</option>
                            <option value="College of Informatics and Computing Sciences" <?= (isset($_POST['department']) && $_POST['department'] === 'College of Informatics and Computing Sciences') ? 'selected' : '' ?>>College of Informatics and Computing Sciences</option>
                            <option value="College of Teacher Education" <?= (isset($_POST['department']) && $_POST['department'] === 'College of Teacher Education') ? 'selected' : '' ?>>College of Teacher Education</option>
                            <option value="College of Arts and Sciences" <?= (isset($_POST['department']) && $_POST['department'] === 'College of Arts and Sciences') ? 'selected' : '' ?>>College of Arts and Sciences</option>
                            <option value="College of Engineering and Technology" <?= (isset($_POST['department']) && $_POST['department'] === 'College of Engineering and Technology') ? 'selected' : '' ?>>College of Engineering and Technology</option>
                            <option value="College of Business, Accountancy, and Economics" <?= (isset($_POST['department']) && $_POST['department'] === 'College of Business, Accountancy, and Economics') ? 'selected' : '' ?>>College of Business, Accountancy, and Economics</option>
                        </select>
                    </div>
                </div>

                <div class="terms-container">
                    <input type="checkbox" name="terms" id="termsCheckbox" required <?= isset($_POST['terms']) ? 'checked' : '' ?>>
                    <label for="termsCheckbox" style="margin-bottom: 0; cursor: pointer;">
                        I agree to the <a href="#" onclick="showTermsModal(event)">Terms and Conditions</a> & Privacy Policy.
                    </label>
                </div>

                <button type="submit" class="submit-btn">Sign Up</button>
            </form>
            <p class="switch-route-text" style="margin-top: 15px;">Already have an account? <a href="login.php">Log in here.</a></p>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <script>
            Swal.fire({
                icon: 'warning',
                title: 'Registration Alert',
                text: '<?= addslashes(htmlspecialchars($error)) ?>',
                confirmButtonColor: '#098a38',
                confirmButtonText: 'Review Fields'
            });
        </script>
    <?php endif; ?>

    <script>
        function showTermsModal(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Terms and Conditions',
                html: '<div style="text-align: left; max-height: 250px; overflow-y: auto; font-size: 0.85rem; color: #555; padding: 0 10px;">' +
                      '<p><strong>1. Acceptance of Terms:</strong> By registering for CarbonWise, you agree to comply with and be bound by these terms.</p><br>' +
                      '<p><strong>2. Account Security:</strong> You are responsible for maintaining the confidentiality of your institutional password and account details.</p><br>' +
                      '<p><strong>3. Data Usage:</strong> Carbon data, transport footprints, and resource consumption logged into this platform are utilized for institutional sustainability evaluations and carbon accounting metrics.</p><br>' +
                      '<p><strong>4. Code of Conduct:</strong> Users must provide accurate records regarding their campus routines and resource logs.</p>' +
                      '</div>',
                confirmButtonColor: '#098a38',
                confirmButtonText: 'Close'
            });
        }

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

        const roleSelector = document.getElementById('roleSelector');
        const studentContainer = document.getElementById('studentContainer');
        const studentAcademicRow = document.getElementById('studentAcademicRow');
        const staffContainer = document.getElementById('staffContainer');

        const srCodeField = document.getElementById('srCodeField');
        const emailField = document.getElementById('emailField');
        const yearLevelField = document.getElementById('yearLevelField');
        const departmentField = document.getElementById('departmentField');

        function updateFormUI() {
            const role = roleSelector.value;
            if (role === 'student') {
                studentContainer.style.display = 'block';
                studentAcademicRow.style.display = 'flex';
                staffContainer.style.display = 'none';

                srCodeField.setAttribute('required', 'required');
                yearLevelField.setAttribute('required', 'required');
                departmentField.setAttribute('required', 'required');
                emailField.removeAttribute('required');
            } else if (role === 'faculty' || role === 'staff') {
                studentContainer.style.display = 'none';
                studentAcademicRow.style.display = 'none';
                staffContainer.style.display = 'block';

                srCodeField.removeAttribute('required');
                yearLevelField.removeAttribute('required');
                departmentField.removeAttribute('required');
                emailField.setAttribute('required', 'required');
            } else {
                studentContainer.style.display = 'none';
                studentAcademicRow.style.display = 'none';
                staffContainer.style.display = 'none';

                srCodeField.removeAttribute('required');
                yearLevelField.removeAttribute('required');
                departmentField.removeAttribute('required');
                emailField.removeAttribute('required');
            }
        }

        roleSelector.addEventListener('change', updateFormUI);
        window.addEventListener('DOMContentLoaded', updateFormUI);
    </script>

</body>
</html>