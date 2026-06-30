<?php
// Start session for login state tracking
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define credentials clearly
define('SUPABASE_URL', 'https://cvlibryzqhoztbutyvbx.supabase.co'); 
define('SUPABASE_ANON_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImN2bGlicnl6cWhvenRidXR5dmJ4Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3ODIyMDgxNTcsImV4cCI6MjA5Nzc4NDE1N30.q0vj8nBE4_SPVs8DDDeBOnzu8rpvGdfA5GXQpGp5rWs');

/**
 * Helper function to send requests to Supabase Auth API
 */
function supabase_auth_request($endpoint, $payload) {
    // Explicit concatenation eliminates any dynamic string issues
    $url = 'https://cvlibryzqhoztbutyvbx.supabase.co/auth/v1/' . $endpoint;
    
    // FIX: Both apiKey and Authorization header use your true Anon Key token
    $headers = [
        'apiKey: ' . SUPABASE_ANON_KEY,
        'Authorization: Bearer ' . SUPABASE_ANON_KEY,
        'Content-Type: application/json'
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    // Bypass SSL restrictions on local development environments
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); 

    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        $curl_error = curl_error($ch);
        curl_close($ch);
        return ['status' => 500, 'data' => ['msg' => "cURL Error: " . $curl_error]];
    }

    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'status' => $http_code,
        'data' => json_decode($response, true)
    ];
}