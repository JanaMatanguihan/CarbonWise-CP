<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';
$success = '';

// ==========================================
// --- NEON POSTGRESQL CONFIGURATION ---
// ==========================================
$db_host     = getenv('DB_HOST') ?: 'localhost';
$endpoint_id = getenv('ENDPOINT_ID') ?: '';
$db_port     = getenv('DB_PORT') ?: '5432';
$db_name     = getenv('DB_NAME') ?: 'neondb';
$db_user     = getenv('DB_USER') ?: 'neondb_owner';
$db_pass     = getenv('DB_PASS') ?: '';

$dsn = "pgsql:host=$db_host;port=$db_port;dbname=$db_name;sslmode=require";

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role          = $_POST['role'] ?? ''; 
    $full_name     = trim($_POST['name'] ?? ''); 
    $password      = $_POST['password'] ?? '';
    $confirm_pwd   = $_POST['confirm_password'] ?? '';
    $campus        = $_POST['campus'] ?? '';
    $terms         = $_POST['terms'] ?? '';
    
    // Dynamic Role-dependent assignments based on screenshots
    $sr_code       = ($role === 'student') ? trim($_POST['sr_code'] ?? '') : null;
    $year_level    = ($role === 'student') ? ($_POST['year_level'] ?? null) : null;
    $department    = ($role === 'student') ? ($_POST['department'] ?? null) : null;
    $faculty_type  = ($role === 'faculty') ? ($_POST['faculty_type'] ?? null) : null;
    $office        = ($role === 'staff') ? ($_POST['office'] ?? null) : null;

    if ($role === 'student') {
        $email = $sr_code ? $sr_code . "@g.batstate-u.edu.ph" : '';
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
                $hashed_confirm  = password_hash($confirm_pwd, PASSWORD_BCRYPT);
                $current_time    = date('Y-m-d H:i:s');

                // --- STEP 3: INSERT INTO NEON POSTGRESQL TABLE MATCHING EXACT SCHEMA ---
                $insert_sql = "INSERT INTO users 
                    (name, email, password, confirm_password, role, sr_code, campus, year_level, department, faculty_type, office, status, created_at, \"updated_at\") 
                    VALUES 
                    (:name, :email, :password, :confirm_password, :role, :sr_code, :campus, :year_level, :department, :faculty_type, :office, 'Active', :created_at, :updated_at)";

                $insert_stmt = $pdo->prepare($insert_sql);
                $insert_stmt->execute([
                    ':name'             => $full_name,
                    ':email'            => $email,
                    ':password'         => $hashed_password,
                    ':confirm_password' => $hashed_confirm,
                    ':role'             => $role,
                    ':sr_code'          => $sr_code,
                    ':campus'           => $campus,
                    ':year_level'       => $year_level,
                    ':department'       => $department,
                    ':faculty_type'     => $faculty_type,
                    ':office'           => $office,
                    ':created_at'       => $current_time,
                    ':updated_at'       => $current_time
                ]);

                $_SESSION['reg_success_message'] = "Registration successful! Welcome to CarbonWise. You may log in directly now.";
                
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
                <!-- 1. Role Selection -->
                <div class="form-group">
                    <label>Role</label>
                    <select name="role" id="roleSelector" required style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc; background-color: #fff;">
                        <option value="" disabled <?= !isset($_POST['role']) || $_POST['role'] === '' ? 'selected' : '' ?>>Click to choose role</option>
                        <option value="student" <?= (isset($_POST['role']) && $_POST['role'] === 'student') ? 'selected' : '' ?>>Student</option>
                        <option value="faculty" <?= (isset($_POST['role']) && $_POST['role'] === 'faculty') ? 'selected' : '' ?>>Faculty</option>
                        <option value="staff" <?= (isset($_POST['role']) && $_POST['role'] === 'staff') ? 'selected' : '' ?>>Non-Teaching Staff</option>
                    </select>
                </div>

                <!-- 2. Dynamic SR-Code Field (Students only) -->
                <div id="studentSrContainer" style="display: none;">
                    <div class="form-group">
                        <label>SR-Code</label>
                        <input type="text" name="sr_code" id="srCodeField" placeholder="xx-xxxxx" value="<?= isset($_POST['sr_code']) ? htmlspecialchars($_POST['sr_code']) : '' ?>">
                    </div>
                </div>

                <!-- 3. Name Field -->
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" id="nameField" placeholder="Enter your full name" value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>" required>
                </div>

                <!-- 4. Dynamic Email Field -->
                <div class="form-group">
                    <label id="emailLabel">Email</label>
                    <input type="email" name="email" id="emailField" placeholder="Enter your email" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                </div>

                <!-- 5. Password -->
                <div class="form-group">
                    <label>Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="password" id="passwordInput" placeholder="Password" oninput="checkInputLength(this, 'togglePwdIcon')" required>
                        <i id="togglePwdIcon" class="fa-solid fa-eye-slash toggle-password-btn" onclick="toggleVisibility('passwordInput', this)"></i>
                    </div>
                </div>

                <!-- 6. Confirm Password -->
                <div class="form-group">
                    <label>Confirm Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="confirm_password" id="confirmPasswordInput" placeholder="Confirm password" oninput="checkInputLength(this, 'toggleConfirmPwdIcon')" required>
                        <i id="toggleConfirmPwdIcon" class="fa-solid fa-eye-slash toggle-password-btn" onclick="toggleVisibility('confirmPasswordInput', this)"></i>
                    </div>
                </div>

                <!-- 7. Campus -->
                <div class="form-group">
                    <label>Campus</label>
                    <select name="campus" required style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc; background-color: #fff;">
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
                        <option value="Balayan Campus" <?= (isset($_POST['campus']) && $_POST['campus'] === 'Balayan Campus') ? 'selected' : '' ?>>Balayan Campus</option>
                        <option value="Lobo Campus" <?= (isset($_POST['campus']) && $_POST['campus'] === 'Lobo Campus') ? 'selected' : '' ?>>Lobo Campus</option>
                        <option value="Mabini Campus" <?= (isset($_POST['campus']) && $_POST['campus'] === 'Mabini Campus') ? 'selected' : '' ?>>Mabini Campus</option>
                    </select>
                </div>
                
                <!-- Dynamic Bottom Fields for Students -->
                <div id="studentBottomContainer" style="display: none;">
                    <div class="form-group">
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
                    <div class="form-group">
                        <label>Department</label>
                        <select name="department" id="departmentField" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc; background-color: #fff;">
                            <option value="">Choose Department</option>
                            <option value="College of Informatics and Computing Sciences" <?= (isset($_POST['department']) && $_POST['department'] === 'College of Informatics and Computing Sciences') ? 'selected' : '' ?>>College of Informatics and Computing Sciences</option>
                            <option value="College of Teacher Education" <?= (isset($_POST['department']) && $_POST['department'] === 'College of Teacher Education') ? 'selected' : '' ?>>College of Teacher Education</option>
                            <option value="College of Arts and Sciences" <?= (isset($_POST['department']) && $_POST['department'] === 'College of Arts and Sciences') ? 'selected' : '' ?>>College of Arts and Sciences</option>
                            <option value="College of Engineering and Technology" <?= (isset($_POST['department']) && $_POST['department'] === 'College of Engineering and Technology') ? 'selected' : '' ?>>College of Engineering and Technology</option>
                            <option value="College of Business, Accountancy, and Economics" <?= (isset($_POST['department']) && $_POST['department'] === 'College of Business, Accountancy, and Economics') ? 'selected' : '' ?>>College of Business, Accountancy, and Economics</option>
                        </select>
                    </div>
                </div>

                <!-- Dynamic Bottom Field for Faculty -->
                <div id="facultyContainer" style="display: none;">
                    <div class="form-group">
                        <label>Faculty Type</label>
                        <select name="faculty_type" id="facultyTypeField" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc; background-color: #fff;">
                            <option value="">Choose Faculty Type</option>
                            <option value="Teaching Faculty" <?= (isset($_POST['faculty_type']) && $_POST['faculty_type'] === 'Teaching Faculty') ? 'selected' : '' ?>>Teaching Faculty</option>
                            <option value="Administrative Faculty" <?= (isset($_POST['faculty_type']) && $_POST['faculty_type'] === 'Administrative Faculty') ? 'selected' : '' ?>>Administrative Faculty</option>
                        </select>
                    </div>
                </div>

                <!-- Dynamic Bottom Field for Staff -->
                <div id="staffContainer" style="display: none;">
                    <div class="form-group">
                        <label>Office</label>
                        <select name="office" id="officeField" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc; background-color: #fff;">
                            <option value="">Choose Office</option>
                            
                            <!-- Office of the Chancellor & Direct Reporting Units -->
                            <option value="Office of the Chancellor" <?= (isset($_POST['office']) && $_POST['office'] === 'Office of the Chancellor') ? 'selected' : '' ?>>Office of the Chancellor</option>
                            <option value="Internal Audit" <?= (isset($_POST['office']) && $_POST['office'] === 'Internal Audit') ? 'selected' : '' ?>>Internal Audit</option>
                            <option value="Quality Assurance Management" <?= (isset($_POST['office']) && $_POST['office'] === 'Quality Assurance Management') ? 'selected' : '' ?>>Quality Assurance Management</option>
                            <option value="Sustainable Development" <?= (isset($_POST['office']) && $_POST['office'] === 'Sustainable Development') ? 'selected' : '' ?>>Sustainable Development</option>
                            
                            <!-- Development and External Affairs -->
                            <option value="Vice Chancellor for Development and External Affairs" <?= (isset($_POST['office']) && $_POST['office'] === 'Vice Chancellor for Development and External Affairs') ? 'selected' : '' ?>>Vice Chancellor for Development and External Affairs</option>
                            <option value="Planning and Development" <?= (isset($_POST['office']) && $_POST['office'] === 'Planning and Development') ? 'selected' : '' ?>>Planning and Development</option>
                            <option value="External Affairs" <?= (isset($_POST['office']) && $_POST['office'] === 'External Affairs') ? 'selected' : '' ?>>External Affairs</option>
                            <option value="Resource Generation" <?= (isset($_POST['office']) && $_POST['office'] === 'Resource Generation') ? 'selected' : '' ?>>Resource Generation</option>
                            <option value="ICT Services" <?= (isset($_POST['office']) && $_POST['office'] === 'ICT Services') ? 'selected' : '' ?>>ICT Services</option>
                            
                            <!-- Academic Affairs & Academic Colleges -->
                            <option value="Vice Chancellor for Academic Affairs" <?= (isset($_POST['office']) && $_POST['office'] === 'Vice Chancellor for Academic Affairs') ? 'selected' : '' ?>>Vice Chancellor for Academic Affairs</option>
                            <option value="College of Arts and Sciences" <?= (isset($_POST['office']) && $_POST['office'] === 'College of Arts and Sciences') ? 'selected' : '' ?>>College of Arts and Sciences</option>
                            <option value="College of Accountancy, Business and Economics" <?= (isset($_POST['office']) && $_POST['office'] === 'College of Accountancy, Business and Economics') ? 'selected' : '' ?>>College of Accountancy, Business and Economics</option>
                            <option value="College of Informatics and Computing Sciences" <?= (isset($_POST['office']) && $_POST['office'] === 'College of Informatics and Computing Sciences') ? 'selected' : '' ?>>College of Informatics and Computing Sciences</option>
                            <option value="College of Engineering Technology" <?= (isset($_POST['office']) && $_POST['office'] === 'College of Engineering Technology') ? 'selected' : '' ?>>College of Engineering Technology</option>
                            <option value="College of Teacher Education" <?= (isset($_POST['office']) && $_POST['office'] === 'College of Teacher Education') ? 'selected' : '' ?>>College of Teacher Education</option>
                            <option value="College of Engineering" <?= (isset($_POST['office']) && $_POST['office'] === 'College of Engineering') ? 'selected' : '' ?>>College of Engineering</option>
                            
                            <!-- Student & Support Services -->
                            <option value="Culture and Arts" <?= (isset($_POST['office']) && $_POST['office'] === 'Culture and Arts') ? 'selected' : '' ?>>Culture and Arts</option>
                            <option value="Testing and Admission" <?= (isset($_POST['office']) && $_POST['office'] === 'Testing and Admission') ? 'selected' : '' ?>>Testing and Admission</option>
                            <option value="Registration Services" <?= (isset($_POST['office']) && $_POST['office'] === 'Registration Services') ? 'selected' : '' ?>>Registration Services</option>
                            <option value="Scholarship and Financial Assistance" <?= (isset($_POST['office']) && $_POST['office'] === 'Scholarship and Financial Assistance') ? 'selected' : '' ?>>Scholarship and Financial Assistance</option>
                            <option value="Guidance and Counseling" <?= (isset($_POST['office']) && $_POST['office'] === 'Guidance and Counseling') ? 'selected' : '' ?>>Guidance and Counseling</option>
                            <option value="Library Services" <?= (isset($_POST['office']) && $_POST['office'] === 'Library Services') ? 'selected' : '' ?>>Library Services</option>
                            <option value="Student Organization and Activities" <?= (isset($_POST['office']) && $_POST['office'] === 'Student Organization and Activities') ? 'selected' : '' ?>>Student Organization and Activities</option>
                            <option value="Student Discipline" <?= (isset($_POST['office']) && $_POST['office'] === 'Student Discipline') ? 'selected' : '' ?>>Student Discipline</option>
                            <option value="Sports and Development" <?= (isset($_POST['office']) && $_POST['office'] === 'Sports and Development') ? 'selected' : '' ?>>Sports and Development</option>
                            <option value="OJT" <?= (isset($_POST['office']) && $_POST['office'] === 'OJT') ? 'selected' : '' ?>>OJT</option>
                            <option value="National Service Training Program" <?= (isset($_POST['office']) && $_POST['office'] === 'National Service Training Program') ? 'selected' : '' ?>>National Service Training Program</option>
                            
                            <!-- Administration and Finance -->
                            <option value="Vice Chancellor for Administration and Finance" <?= (isset($_POST['office']) && $_POST['office'] === 'Vice Chancellor for Administration and Finance') ? 'selected' : '' ?>>Vice Chancellor for Administration and Finance</option>
                            <option value="Human Resource Management" <?= (isset($_POST['office']) && $_POST['office'] === 'Human Resource Management') ? 'selected' : '' ?>>Human Resource Management</option>
                            <option value="Records Management" <?= (isset($_POST['office']) && $_POST['office'] === 'Records Management') ? 'selected' : '' ?>>Records Management</option>
                            <option value="Procurement" <?= (isset($_POST['office']) && $_POST['office'] === 'Procurement') ? 'selected' : '' ?>>Procurement</option>
                            <option value="Budget" <?= (isset($_POST['office']) && $_POST['office'] === 'Budget') ? 'selected' : '' ?>>Budget</option>
                            <option value="Cashiering/Disbursing" <?= (isset($_POST['office']) && $_POST['office'] === 'Cashiering/Disbursing') ? 'selected' : '' ?>>Cashiering/Disbursing</option>
                            <option value="Accounting" <?= (isset($_POST['office']) && $_POST['office'] === 'Accounting') ? 'selected' : '' ?>>Accounting</option>
                            <option value="Project Facilities and Management" <?= (isset($_POST['office']) && $_POST['office'] === 'Project Facilities and Management') ? 'selected' : '' ?>>Project Facilities and Management</option>
                            <option value="Environment Management Unit" <?= (isset($_POST['office']) && $_POST['office'] === 'Environment Management Unit') ? 'selected' : '' ?>>Environment Management Unit</option>
                            <option value="Property and Supply Management" <?= (isset($_POST['office']) && $_POST['office'] === 'Property and Supply Management') ? 'selected' : '' ?>>Property and Supply Management</option>
                            <option value="General Services" <?= (isset($_POST['office']) && $_POST['office'] === 'General Services') ? 'selected' : '' ?>>General Services</option>
                            
                            <!-- Research, Development and Extension Services -->
                            <option value="Vice Chancellor for Research, Development and Extension Services" <?= (isset($_POST['office']) && $_POST['office'] === 'Vice Chancellor for Research, Development and Extension Services') ? 'selected' : '' ?>>Vice Chancellor for Research, Development and Extension Services</option>
                            <option value="Extension" <?= (isset($_POST['office']) && $_POST['office'] === 'Extension') ? 'selected' : '' ?>>Extension</option>
                            <option value="Research" <?= (isset($_POST['office']) && $_POST['office'] === 'Research') ? 'selected' : '' ?>>Research</option>
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
            <p class="switch-route-text" style="margin-top: 15px;">Already have an account? <a href="login.php">Log In</a>.</p>
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
        const studentSrContainer = document.getElementById('studentSrContainer');
        const studentBottomContainer = document.getElementById('studentBottomContainer');
        const facultyContainer = document.getElementById('facultyContainer');
        const staffContainer = document.getElementById('staffContainer');

        const srCodeField = document.getElementById('srCodeField');
        const nameField = document.getElementById('nameField');
        const emailLabel = document.getElementById('emailLabel');
        const emailField = document.getElementById('emailField');
        const yearLevelField = document.getElementById('yearLevelField');
        const departmentField = document.getElementById('departmentField');
        const facultyTypeField = document.getElementById('facultyTypeField');
        const officeField = document.getElementById('officeField');

        // Helper to dynamic email value update from SR-Code input
        function updateStudentEmail() {
            if (roleSelector.value === 'student') {
                const srValue = srCodeField.value.trim();
                emailField.value = srValue ? srValue + "@g.batstate-u.edu.ph" : "00-00000@g.batstate-u.edu.ph";
            }
        }

        srCodeField.addEventListener('input', updateStudentEmail);

        function updateFormUI() {
            const role = roleSelector.value;

            // Reset dynamic fields visibility and required attributes
            studentSrContainer.style.display = 'none';
            studentBottomContainer.style.display = 'none';
            facultyContainer.style.display = 'none';
            staffContainer.style.display = 'none';

            srCodeField.removeAttribute('required');
            yearLevelField.removeAttribute('required');
            departmentField.removeAttribute('required');
            facultyTypeField.removeAttribute('required');
            officeField.removeAttribute('required');

            if (role === 'student') {
                studentSrContainer.style.display = 'block';
                studentBottomContainer.style.display = 'block';

                nameField.placeholder = "Enter your name";
                emailLabel.textContent = "G-Suite Email";
                emailField.placeholder = "00-00000@g.batstate-u.edu.ph";
                updateStudentEmail();
                emailField.readOnly = true;

                srCodeField.setAttribute('required', 'required');
                yearLevelField.setAttribute('required', 'required');
                departmentField.setAttribute('required', 'required');
            } else if (role === 'faculty') {
                facultyContainer.style.display = 'block';

                nameField.placeholder = "Enter your full name";
                emailLabel.textContent = "G-Suite Email";
                emailField.placeholder = "example@g.batstate-u.edu.ph";
                emailField.value = "";
                emailField.readOnly = false;
                emailField.setAttribute('required', 'required');

                facultyTypeField.setAttribute('required', 'required');
            } else if (role === 'staff') {
                staffContainer.style.display = 'block';

                nameField.placeholder = "Enter your full name";
                emailLabel.textContent = "Email";
                emailField.placeholder = "Enter your email";
                emailField.value = "";
                emailField.readOnly = false;
                emailField.setAttribute('required', 'required');

                officeField.setAttribute('required', 'required');
            }
        }

        roleSelector.addEventListener('change', updateFormUI);
        window.addEventListener('DOMContentLoaded', updateFormUI);
    </script>

</body>
</html>