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
 button{font-family:inherit}
 .btn{margin-top:16px;padding:12px 24px;background:#2D6A4F;color:#fff;border:0;border-radius:8px;font-weight:700;cursor:pointer;transition:background .2s}
 .btn:hover{background:#1B4332}
 .btn:disabled{opacity:.6;cursor:not-allowed}
 #msg{margin-top:14px;font-size:.9rem;color:#6B7280}
 #msg.err{color:#B91C1C}

 /* ---------- Permission popup ---------- */
 .perm-overlay{position:fixed;inset:0;background:rgba(17,24,39,.55);display:none;align-items:center;justify-content:center;padding:16px;z-index:9999}
 .perm-overlay.show{display:flex;animation:fadeIn .2s ease}
 .perm-card{background:#fff;width:100%;max-width:380px;border-radius:20px;padding:28px 24px 22px;text-align:center;box-shadow:0 20px 50px rgba(0,0,0,.25);animation:popIn .28s cubic-bezier(.2,.8,.2,1)}
 .perm-icon{width:68px;height:68px;border-radius:50%;background:#D8F3DC;margin:0 auto 14px;display:flex;align-items:center;justify-content:center}
 .perm-icon svg{width:34px;height:34px;stroke:#2D6A4F;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}
 .perm-icon.warn{background:#FEE2E2}
 .perm-icon.warn svg{stroke:#B91C1C}
 .perm-card h3{margin:0 0 8px;font-size:1.2rem;color:#1F2937}
 .perm-card p{margin:0;font-size:.92rem}
 .perm-card ol{text-align:left;color:#4B5563;font-size:.9rem;line-height:1.6;padding-left:20px;margin:12px 0 0}
 .perm-actions{display:flex;gap:10px;margin-top:22px}
 .perm-actions button{flex:1;padding:12px 10px;border-radius:10px;font-weight:700;font-size:.92rem;cursor:pointer;border:0}
 .perm-deny{background:#F3F4F6;color:#374151}
 .perm-deny:hover{background:#E5E7EB}
 .perm-allow{background:#2D6A4F;color:#fff}
 .perm-allow:hover{background:#1B4332}
 @keyframes fadeIn{from{opacity:0}to{opacity:1}}
 @keyframes popIn{from{transform:translateY(20px) scale(.96);opacity:0}to{transform:none;opacity:1}}
</style>
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#1B4332">
</head>
<body>
<div class="box">
  <h2>Campus Location Check</h2>
  <p>CarbonWise is exclusive to BatStateU campuses. We need to confirm you are on campus to continue.</p>
  <button class="btn" id="btn">Allow Location &amp; Continue</button>
  <p id="msg"></p>
</div>

<!-- Permission request popup -->
<div class="perm-overlay" id="permOverlay" role="dialog" aria-modal="true" aria-labelledby="permTitle">
  <div class="perm-card">
    <div class="perm-icon">
      <svg viewBox="0 0 24 24"><path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
    </div>
    <h3 id="permTitle">Allow location access?</h3>
    <p>CarbonWise needs your location to verify that you are inside a BatStateU campus. Your location is only used for this check.</p>
    <div class="perm-actions">
      <button type="button" class="perm-deny" id="permDeny">Not Now</button>
      <button type="button" class="perm-allow" id="permAllow">Allow</button>
    </div>
  </div>
</div>

<!-- Blocked / denied popup -->
<div class="perm-overlay" id="blockedOverlay" role="dialog" aria-modal="true" aria-labelledby="blockedTitle">
  <div class="perm-card">
    <div class="perm-icon warn">
      <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 8v5"/><path d="M12 16.5v.01"/></svg>
    </div>
    <h3 id="blockedTitle">Location is blocked</h3>
    <p>Your browser is blocking location access for CarbonWise. To continue:</p>
    <ol>
      <li>Tap the lock / settings icon next to the address bar</li>
      <li>Open <b>Permissions</b> (or Site settings)</li>
      <li>Set <b>Location</b> to <b>Allow</b></li>
      <li>Come back here and tap <b>Try Again</b></li>
    </ol>
    <div class="perm-actions">
      <button type="button" class="perm-deny" id="blockedClose">Close</button>
      <button type="button" class="perm-allow" id="blockedRetry">Try Again</button>
    </div>
  </div>
</div>

<script>
const NEXT_URL = <?= json_encode($next) ?>;
const msg = document.getElementById('msg'), btn = document.getElementById('btn');
const permOverlay = document.getElementById('permOverlay');
const blockedOverlay = document.getElementById('blockedOverlay');

function setMsg(text, isErr) { msg.textContent = text; msg.className = isErr ? 'err' : ''; }
function openOverlay(el)  { el.classList.add('show'); }
function closeOverlay(el) { el.classList.remove('show'); }

// Runs the real browser geolocation request + server verification.
function check() {
  closeOverlay(permOverlay); closeOverlay(blockedOverlay);

  if (!window.isSecureContext) { setMsg('Location requires a secure (HTTPS) connection.', true); return; }
  if (!navigator.geolocation)  { setMsg('Your browser does not support location services.', true); return; }
  btn.disabled = true; setMsg('Checking your location...');

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
        setMsg('Verified: ' + data.campus + '. Redirecting...');
        location.href = NEXT_URL;
      } else {
        setMsg(data.message, true); btn.disabled = false;
      }
    } catch (e) { setMsg('Network error. Please try again.', true); btn.disabled = false; }
  }, err => {
    btn.disabled = false;
    if (err.code === 1) {
      setMsg('Location permission was denied.', true);
      openOverlay(blockedOverlay);
    } else {
      setMsg('Could not get your location. Make sure GPS/location services are turned on.', true);
    }
  }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
}

// Decide whether to show our permission popup first.
async function start() {
  if (navigator.permissions && navigator.permissions.query) {
    try {
      const status = await navigator.permissions.query({ name: 'geolocation' });
      if (status.state === 'granted') { check(); return; }              // already allowed: no popup needed
      if (status.state === 'denied')  { setMsg('Location permission was denied.', true); openOverlay(blockedOverlay); return; }
    } catch (e) { /* Permissions API unsupported for geolocation: fall through to popup */ }
  }
  openOverlay(permOverlay);
}

document.getElementById('permAllow').addEventListener('click', check);
document.getElementById('permDeny').addEventListener('click', () => {
  closeOverlay(permOverlay);
  setMsg('Location access is required to use CarbonWise. Tap the button above when you are ready.', true);
});
document.getElementById('blockedClose').addEventListener('click', () => closeOverlay(blockedOverlay));
document.getElementById('blockedRetry').addEventListener('click', check);
btn.addEventListener('click', () => openOverlay(permOverlay));

start(); // on arrival: popup first (or straight to check if already granted)
</script>
    <script src="pwa-register.js"></script>
</body></html>