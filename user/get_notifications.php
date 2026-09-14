<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// 1. Guard check: Ensure user token is present
if (!isset($_SESSION['user_token'])) {
    echo json_encode(['error' => 'Unauthorized. Please log in.']);
    exit;
}

// 2. Resolve user email dynamically using nested fallbacks
$user_data = $_SESSION['user_profile'] ?? ($_SESSION['user_data'] ?? []);
$userEmail = $_SESSION['user_email'] ?? ($_SESSION['email'] ?? ($_SESSION['g_suite'] ?? ($user_data['g_suite'] ?? ($user_data['email'] ?? null))));

if (!empty($userEmail)) {
    $userEmail = strtolower(trim($userEmail));
} else {
    echo json_encode(['error' => 'Active email identifier not found.']);
    exit;
}

// 3. Supabase connection keys matching live workspace details
$supabaseUrl = 'https://cvlibryzqhoztbutyvbx.supabase.co';
$supabaseKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImN2bGlicnl6cWhvenRidXR5dmJ4Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3ODIyMDgxNTcsImV4cCI6MjA5Nzc4NDE1N30.q0vj8nBE4_SPVs8DDDeBOnzu8rpvGdfA5GXQpGp5rWs'; 

// 4. Fetch notifications linked to the user's g_suite email, ordered by recent creation date
$url = $supabaseUrl . '/rest/v1/notifications?g_suite=eq.' . urlencode($userEmail) . '&order=created_at.desc';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'apikey: ' . $supabaseKey,
    'Authorization: Bearer ' . $supabaseKey
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo $response;
} else {
    echo json_encode(['error' => 'Failed to fetch data from database', 'status' => $httpCode]);
}
?>