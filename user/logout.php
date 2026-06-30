<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. If the user confirmed the logout via SweetAlert, destroy the session backend data
if (isset($_GET['action']) && $_GET['action'] === 'logout' && isset($_GET['confirmed']) && $_GET['confirmed'] === 'true') {
    $_SESSION = [];
    session_destroy();
    header('Location: login.php');
    exit;
}

// 2. Guard Check: If no active token exists, redirect straight back to login panel
if (!isset($_SESSION['user_token'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CarbonWise - Signing Out...</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        html, body {
            /* Makes the body container completely invisible so the underlying dashboard shows through */
            background-color: transparent !important;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
    </style>
</head>
<body>

    <script>
        // Trigger the SweetAlert dialog automatically as soon as the DOM loads
        window.addEventListener('DOMContentLoaded', () => {
            Swal.fire({
                title: 'Are you sure?',
                text: "You want to log out of your CarbonWise session?",
                icon: 'warning',
                iconColor: '#F2A654',           // Soft orange alert outline icon color
                showCancelButton: true,
                confirmButtonColor: '#2D6A4F',   // CarbonWise brand green accent
                cancelButtonColor: '#BA181B',    // Contrast notification red 
                confirmButtonText: 'Yes, log me out',
                cancelButtonText: 'Cancel',
                allowOutsideClick: false,         // Prevent accidental backdrop closing
                allowEscapeKey: false,
                /* Customized semi-transparent dimmed overlay over your dashboard view */
                backdrop: `rgba(0, 0, 0, 0.4)`
            }).then((result) => {
                if (result.isConfirmed) {
                    // Send query string flag to execute session clear logic above
                    window.location.href = 'logout.php?action=logout&confirmed=true';
                } else {
                    // Go back to the previous dashboard view instead of hardcoded route paths
                    if (document.referrer !== "") {
                        window.location.href = document.referrer;
                    } else {
                        window.history.back();
                    }
                }
            });
        });
    </script>
</body>
</html>