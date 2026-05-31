<?php
// sw-reset.php — Emergency Service Worker & Cache Reset Tool
// Place this at root level (outside /dsr/) so old SW can't intercept it
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Fixing App... — Happy Bangladesh</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: 'Segoe UI', sans-serif;
      background: #0f172a;
      color: #f1f5f9;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-direction: column;
      gap: 1.5rem;
      padding: 2rem;
      text-align: center;
    }
    .spinner {
      width: 48px; height: 48px;
      border: 4px solid #334155;
      border-top-color: #3b82f6;
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
    h1 { font-size: 1.25rem; font-weight: 700; color: #e2e8f0; }
    p  { font-size: 0.85rem; color: #94a3b8; max-width: 320px; line-height: 1.6; }
    .status { font-size: 0.8rem; color: #60a5fa; font-family: monospace; min-height: 1.2rem; }
    .done-box {
      display: none;
      background: #1e3a2f;
      border: 2px solid #22c55e;
      padding: 1.25rem 2rem;
      color: #86efac;
      font-weight: 700;
      font-size: 0.95rem;
    }
  </style>
</head>
<body>
  <div class="spinner" id="spinner"></div>
  <h1>Fixing DSR App…</h1>
  <p>Clearing old cached data and resetting the app. This only takes a moment.</p>
  <div class="status" id="status">Initializing…</div>
  <div class="done-box" id="done-box">✓ All done! Redirecting to DSR login…</div>

  <script>
    const statusEl = document.getElementById('status');
    const spinner  = document.getElementById('spinner');
    const doneBox  = document.getElementById('done-box');

    async function resetAndRedirect() {
      try {
        // Step 1: Unregister all service workers
        statusEl.textContent = 'Removing old service workers…';
        if ('serviceWorker' in navigator) {
          const regs = await navigator.serviceWorker.getRegistrations();
          for (const reg of regs) {
            await reg.unregister();
          }
        }

        // Step 2: Delete all browser caches
        statusEl.textContent = 'Clearing browser cache…';
        if ('caches' in window) {
          const keys = await caches.keys();
          await Promise.all(keys.map(k => caches.delete(k)));
        }

        // Step 3: Done — redirect
        statusEl.textContent = 'Done! ✓';
        spinner.style.display  = 'none';
        doneBox.style.display  = 'block';

        setTimeout(() => {
          window.location.href = '/dsr/login';
        }, 1500);

      } catch (err) {
        statusEl.textContent = 'Error: ' + err.message;
        setTimeout(() => {
          window.location.href = '/dsr/login';
        }, 2000);
      }
    }

    resetAndRedirect();
  </script>
</body>
</html>
