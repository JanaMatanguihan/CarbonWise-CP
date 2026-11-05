// CarbonWise - PWA install popup (v2)
// Add to index.php right before </body>, after pwa-register.js:
//   <script src="pwa_install_prompt.js"></script>
(function () {
  'use strict';

  var APP_NAME       = 'CarbonWise';
  var DISMISS_KEY    = 'cw_install_dismissed_until';
  var DISMISS_DAYS   = 7;
  var SHOW_DELAY     = 1200;  // ms before popup slides in
  var FALLBACK_DELAY = 3500;  // ms to wait for Chrome's install event before showing manual steps (Android)

  var ua = navigator.userAgent;
  var deferredPrompt = null;
  var popup = null;
  var stylesDone = false;

  // ---------- environment checks ----------
  function isStandalone() {
    return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
  }
  function isIOS() {
    return /iphone|ipad|ipod/i.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
  }
  function isAndroid() { return /android/i.test(ua); }
  function isInAppBrowser() { return /FBAN|FBAV|Instagram|Line\/|Messenger|MicroMessenger|Snapchat|TikTok/i.test(ua); }

  function isDismissed() {
    try { return Date.now() < parseInt(localStorage.getItem(DISMISS_KEY) || '0', 10); }
    catch (e) { return false; }
  }
  function rememberDismissal() {
    try { localStorage.setItem(DISMISS_KEY, String(Date.now() + DISMISS_DAYS * 86400000)); } catch (e) {}
  }

  // ---------- styles ----------
  function injectStyles() {
    if (stylesDone) return;
    stylesDone = true;
    var css =
      ':root{--cw-bg:#16241d;--cw-accent:#2D6A4F;--cw-accent-hover:#3a8a66;--cw-muted:#b9c4be;}' +
      '#cw-pwa-popup{position:fixed;left:50%;bottom:calc(20px + env(safe-area-inset-bottom,0px));transform:translate(-50%,160%);' +
        'width:calc(100% - 32px);max-width:480px;background:var(--cw-bg);color:#fff;border-radius:20px;padding:20px;' +
        'box-shadow:0 12px 40px rgba(0,0,0,.35);z-index:99999;font-family:Inter,system-ui,-apple-system,sans-serif;opacity:0;' +
        'transition:transform .45s cubic-bezier(.2,.8,.2,1),opacity .3s ease;}' +
      '#cw-pwa-popup.cw-show{transform:translate(-50%,0);opacity:1;}' +
      '#cw-pwa-popup .cw-row{display:flex;gap:16px;align-items:flex-start;}' +
      '#cw-pwa-popup .cw-icon{flex:0 0 56px;height:56px;border-radius:14px;background:var(--cw-accent);display:flex;align-items:center;justify-content:center;}' +
      '#cw-pwa-popup .cw-icon svg{width:28px;height:28px;stroke:#fff;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;}' +
      '#cw-pwa-popup .cw-body{flex:1;min-width:0;}' +
      '#cw-pwa-popup h3{margin:0 0 6px;font-size:1.15rem;font-weight:700;}' +
      '#cw-pwa-popup p{margin:0;font-size:.92rem;line-height:1.5;color:var(--cw-muted);}' +
      '#cw-pwa-popup p svg{width:16px;height:16px;vertical-align:-3px;stroke:#fff;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;}' +
      '#cw-pwa-popup p b{color:#fff;}' +
      '#cw-pwa-popup .cw-close{background:none;border:0;color:var(--cw-muted);font-size:22px;line-height:1;cursor:pointer;padding:0 0 0 4px;}' +
      '#cw-pwa-popup .cw-actions{display:flex;justify-content:flex-end;align-items:center;gap:12px;margin-top:18px;}' +
      '#cw-pwa-popup .cw-later{background:none;border:0;color:#e5e7eb;font-weight:600;font-size:.92rem;cursor:pointer;padding:10px 14px;}' +
      '#cw-pwa-popup .cw-install{background:var(--cw-accent);color:#fff;border:0;border-radius:999px;font-weight:800;font-size:.9rem;' +
        'letter-spacing:.5px;text-transform:uppercase;padding:12px 26px;cursor:pointer;transition:background .2s;}' +
      '#cw-pwa-popup .cw-install:hover{background:var(--cw-accent-hover);}' +
      '#cw-pwa-toast{position:fixed;left:50%;bottom:calc(24px + env(safe-area-inset-bottom,0px));transform:translateX(-50%);' +
        'max-width:420px;width:calc(100% - 40px);background:#16241d;color:#fff;padding:14px 18px;border-radius:14px;' +
        'font:600 .9rem/1.4 Inter,system-ui,sans-serif;text-align:center;z-index:100000;box-shadow:0 8px 28px rgba(0,0,0,.3);}';
    var s = document.createElement('style');
    s.textContent = css;
    document.head.appendChild(s);
  }

  // ---------- content per mode ----------
  var SHARE_SVG = '<svg viewBox="0 0 24 24"><path d="M12 16V3"/><path d="M8 7l4-4 4 4"/><path d="M5 12v8h14v-8"/></svg>';

  function contentFor(mode) {
    if (mode === 'ios') {
      return {
        text: '<b>1.</b> Tap the Share button ' + SHARE_SVG + ' in Safari<br><b>2.</b> Scroll and tap <b>Add to Home Screen</b><br><b>3.</b> Tap <b>Add</b>',
        button: 'Got it', native: false
      };
    }
    if (mode === 'inapp') {
      return {
        text: 'Open this page in <b>' + (isIOS() ? 'Safari' : 'Chrome') + '</b> first (tap the menu and choose "Open in browser"), then install from there.',
        button: 'Got it', native: false
      };
    }
    if (mode === 'android-manual') {
      return {
        text: 'Tap the <b>&#8942;</b> menu in Chrome, then choose <b>Install app</b> or <b>Add to Home screen</b>.',
        button: 'Got it', native: false
      };
    }
    return {
      text: 'Install the app on your device for faster access and offline capabilities.',
      button: 'Install Now', native: true
    };
  }

  // ---------- popup lifecycle ----------
  function buildPopup(mode) {
    injectStyles();
    var c = contentFor(mode);
    popup = document.createElement('div');
    popup.id = 'cw-pwa-popup';
    popup.setAttribute('role', 'dialog');
    popup.innerHTML =
      '<div class="cw-row">' +
        '<div class="cw-icon"><svg viewBox="0 0 24 24"><path d="M12 3v12"/><path d="M7 11l5 5 5-5"/><path d="M5 21h14"/></svg></div>' +
        '<div class="cw-body"><h3>Install ' + APP_NAME + '</h3><p>' + c.text + '</p></div>' +
        '<button class="cw-close" type="button" aria-label="Close">&times;</button>' +
      '</div>' +
      '<div class="cw-actions">' +
        '<button class="cw-later" type="button">Maybe Later</button>' +
        '<button class="cw-install" type="button">' + c.button + '</button>' +
      '</div>';
    document.body.appendChild(popup);
    popup.querySelector('.cw-close').addEventListener('click', dismiss);
    popup.querySelector('.cw-later').addEventListener('click', dismiss);
    popup.querySelector('.cw-install').addEventListener('click', c.native ? install : dismiss);
    setTimeout(function () { if (popup) popup.classList.add('cw-show'); }, SHOW_DELAY);
  }

  function show(mode) {
    if (popup || isStandalone() || isDismissed()) return;
    buildPopup(mode);
  }

  function hidePopup() {
    if (!popup) return;
    var el = popup;
    popup = null;
    el.classList.remove('cw-show');
    setTimeout(function () { el.remove(); }, 500);
  }

  function dismiss() { rememberDismissal(); hidePopup(); }

  function toast(msg, ms) {
    injectStyles();
    var t = document.createElement('div');
    t.id = 'cw-pwa-toast';
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(function () { t.remove(); }, ms || 6000);
  }

  // ---------- install (Chrome / Edge / Samsung on Android + desktop) ----------
  function install() {
    if (!deferredPrompt) {
      // Event is gone/used: fall back to manual steps
      hidePopup();
      setTimeout(function () { popup = null; buildPopup('android-manual'); }, 550);
      return;
    }
    var p = deferredPrompt;
    deferredPrompt = null; // prompt() can only be used once per event
    hidePopup();
    Promise.resolve(p.prompt()).catch(function () {});
    p.userChoice.then(function (choice) {
      if (choice.outcome === 'accepted') {
        toast('Installing ' + APP_NAME + '... it will appear on your home screen / app drawer in a few seconds.');
      } else {
        rememberDismissal();
      }
    });
  }

  window.addEventListener('beforeinstallprompt', function (e) {
    e.preventDefault();
    deferredPrompt = e;
    function go() { show('native'); }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', go);
    else go();
  });

  window.addEventListener('appinstalled', function () {
    deferredPrompt = null;
    hidePopup();
    try { localStorage.removeItem(DISMISS_KEY); } catch (e) {}
    toast(APP_NAME + ' installed! Open it from your home screen.');
  });

  // ---------- platforms without a native install event ----------
  document.addEventListener('DOMContentLoaded', function () {
    if (isStandalone() || isDismissed()) return;

    if (isInAppBrowser()) { show('inapp'); return; }

    if (isIOS()) { show('ios'); return; }

    if (isAndroid()) {
      // Give Chrome a moment to fire beforeinstallprompt; if it never does, show manual steps
      setTimeout(function () {
        if (!deferredPrompt && !popup) show('android-manual');
      }, FALLBACK_DELAY);
    }
  });
})();