// Registers the CarbonWise service worker. Include this on every page,
// right before </body>, e.g.:
//   <script src="pwa-register.js"></script>
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker
      .register('service-worker.js', { scope: './' })
      .catch(err => console.warn('CarbonWise SW registration failed:', err));
  });
}