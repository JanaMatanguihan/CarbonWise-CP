<?php
session_start();
$allowed = ['index.php','login.php','register.php','dashboard.php','profile.php',
            'reports.php','activity_input.php','mitigation_strategies.php'];
$next = $_GET['next'] ?? 'login.php';
if (!in_array($next, $allowed, true)) $next = 'login.php';
?>
<!DOCTYPE html>
<html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>CarbonWise - Location Check</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>
 *{box-sizing:border-box}
 body{display:flex;min-height:100vh;align-items:center;justify-content:center;background:#F3F4F6;font-family:Inter,sans-serif;margin:0}
 .box{background:#fff;padding:32px;border-radius:16px;max-width:420px;width:90%;text-align:center;box-shadow:0 8px 24px rgba(0,0,0,.08)}
 h2{margin-top:0;color:#1F2937}
 p{color:#4B5563;line-height:1.5}
 button{margin-top:16px;padding:12px 24px;background:#2D6A4F;color:#fff;border:0;border-radius:8px;font-weight:700;cursor:pointer}
 button:disabled{opacity:.6;cursor:not-allowed}
 #msg{margin-top:14px;font-size:.9rem;color:#6B7280}
</style></head>
<body>
<div class="box">
  <h2>Campus Location Check</h2>
  <p>CarbonWise is exclusive to BatStateU campuses. Please allow location access so we can confirm you are on campus.</p>
  <button id="btn">Allow Location &amp; Continue</button>
  <p id="msg"></p>
</div>
<script>
const msg = document.getElementById('msg'), btn = document.getElementById('btn');

function check() {
  if (!window.isSecureContext) { msg.textContent = 'Location requires a secure (HTTPS) connection.'; return; }
  if (!navigator.geolocation) { msg.textContent = 'Your browser does not support location services.'; return; }
  btn.disabled = true; msg.textContent = 'Checking your location...';

  navigator.geolocation.getCurrentPosition(async pos => {
    try {
      const res = await fetch('verify_location.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
          lat: pos.coords.latitude, lng: pos.coords.longitude, accuracy: pos.coords.accuracy
        })
      });
      const data = await res.json();
      if (data.ok) {
        msg.textContent = 'Verified: ' + data.campus + '. Redirecting...';
        location.href = <?= json_encode($next) ?>;
      } else {
        msg.textContent = data.message; btn.disabled = false;
      }
    } catch (e) { msg.textContent = 'Network error. Please try again.'; btn.disabled = false; }
  }, err => {
    btn.disabled = false;
    msg.textContent = err.code === 1
      ? 'Location permission was denied. Enable it in your browser or site settings, then try again.'
      : 'Could not get your location. Make sure GPS/location services are turned on.';
  }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
}
btn.addEventListener('click', check);
check(); // prompt immediately on arrival
</script>
</body></html>