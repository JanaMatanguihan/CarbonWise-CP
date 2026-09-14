<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Wipe out all session variables
$_SESSION = [];

// 2. Clear session cookie from the browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Destroy session on the server
session_destroy();

// 4. Send them directly to the login page
header('Location: index.php');
exit;
?>
