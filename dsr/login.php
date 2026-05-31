<?php
// dsr/login.php
require_once __DIR__ . '/../config/session.php';

// If already logged in to dsr, redirect to dashboard
if (isLoggedIn() && ($_SESSION['role'] ?? '') === 'dsr') {
    header('Location: ' . getDashboardUrl());
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>DSR Sheet App Login — Happy Bangladesh</title>
  <meta name="description" content="Secure delivery staff login for Happy Bangladesh ERP" />
  
  <!-- PWA Support -->
  <link rel="manifest" href="manifest.json" />
  <meta name="theme-color" content="#000000" />
  <link rel="apple-touch-icon" href="../assets/img/logo/pwa-icon-192.png" />
  
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  
  <style>
    :root {
      --bg: #f8fafc;
      --card-bg: #ffffff;
      --border-color: #000000;
      --border-focus: #000000;
      --accent: #000000;
      --text-main: #0f172a;
      --text-dim: #64748b;
      --input-bg: #ffffff;
      --error-bg: rgba(239, 68, 68, 0.05);
      --error-border: #f87171;
      --error-text: #dc2626;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      border-radius: 0 !important; /* STRICT REQUIREMENT: No border radius */
    }

    body {
      font-family: 'Inter', sans-serif;
      background-color: var(--bg);
      color: var(--text-main);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1.5rem;
    }

    .login-container {
      width: 100%;
      max-width: 440px;
      background-color: var(--card-bg);
      border: 3px solid var(--border-color);
      box-shadow: 10px 10px 0px rgba(0, 0, 0, 0.15); /* Premium solid flat shadow */
      padding: 3rem 2.5rem;
      position: relative;
    }

    .brand {
      text-align: center;
      margin-bottom: 2.5rem;
    }

    .brand img {
      max-height: 60px;
      width: auto;
      object-fit: contain;
      margin-bottom: 1rem;
    }

    .brand h2 {
      font-size: 1.25rem;
      font-weight: 800;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      color: var(--text-main);
      margin-top: 0.5rem;
    }
    
    .brand p {
      font-size: 0.75rem;
      color: var(--text-dim);
      text-transform: uppercase;
      letter-spacing: 0.2em;
      margin-top: 0.25rem;
      font-weight: 700;
    }

    .form-group {
      margin-bottom: 1.5rem;
    }

    .form-group label {
      display: block;
      font-size: 0.75rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: var(--text-main);
      margin-bottom: 0.5rem;
    }

    .input-wrapper {
      position: relative;
      display: flex;
      align-items: center;
    }

    .input-wrapper input {
      width: 100%;
      background-color: var(--input-bg);
      border: 2px solid #cbd5e1;
      color: var(--text-main);
      padding: 0.85rem 1rem;
      font-size: 0.95rem;
      outline: none;
      transition: all 0.15s ease;
    }

    .input-wrapper input:focus {
      border-color: var(--border-focus);
      box-shadow: 0 0 0 2px rgba(0, 0, 0, 0.1);
    }

    .input-wrapper input::placeholder {
      color: #94a3b8;
    }

    .eye-toggle {
      position: absolute;
      right: 1rem;
      background: none;
      border: none;
      color: var(--text-dim);
      cursor: pointer;
      font-size: 1rem;
      padding: 0.25rem;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .eye-toggle:hover {
      color: var(--text-main);
    }

    .btn-login {
      width: 100%;
      background-color: var(--accent);
      color: #ffffff;
      padding: 1rem;
      font-size: 0.9rem;
      font-weight: 800;
      border: none;
      cursor: pointer;
      text-transform: uppercase;
      letter-spacing: 0.15em;
      transition: all 0.15s ease;
      margin-top: 1rem;
    }

    .btn-login:hover {
      background-color: #1e293b;
    }

    .btn-login:active {
      background-color: #0f172a;
    }

    .btn-login:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }

    .error-msg {
      background-color: var(--error-bg);
      border: 2px solid var(--error-border);
      color: var(--error-text);
      padding: 0.75rem 1rem;
      font-size: 0.85rem;
      margin-bottom: 1.5rem;
      display: none;
      font-weight: 600;
    }

    .footer-note {
      margin-top: 2.5rem;
      padding-top: 1.5rem;
      border-top: 2px solid #f1f5f9;
      text-align: center;
    }

    .footer-note p {
      font-size: 0.65rem;
      color: var(--text-dim);
      text-transform: uppercase;
      letter-spacing: 0.15em;
      font-weight: 700;
    }
  </style>
</head>
<body>

  <div class="login-container">
    <div class="brand">
      <img src="../assets/img/logo/logo-black.png" alt="Happy Bangladesh Logo" />
      <h2>DSR Sheet Portal</h2>
      <p>Delivery App</p>
    </div>

    <div id="error-msg" class="error-msg"></div>

    <form id="login-form">
      <div class="form-group">
        <label for="dsr-email">Access Email</label>
        <div class="input-wrapper">
          <input id="dsr-email" type="email" name="email" placeholder="dsr@happy.com" required autocomplete="email" />
        </div>
      </div>

      <div class="form-group">
        <label for="dsr-password">Security Token</label>
        <div class="input-wrapper">
          <input id="dsr-password" type="password" name="password" placeholder="••••••••" required autocomplete="current-password" />
          <button type="button" id="toggle-pwd-btn" class="eye-toggle" title="Toggle password visibility">
            <i class="fa-solid fa-eye" id="eye-icon"></i>
          </button>
        </div>
      </div>

      <button type="submit" id="login-btn" class="btn-login">
        Sign In to App
      </button>
    </form>

    <div class="footer-note">
      <p>Secured by Happy Bangladesh Cloud</p>
    </div>
  </div>

  <script>
    async function apiCall(url, method = 'POST', body = null) {
      const opts = {
        method,
        headers: { 'Content-Type': 'application/json' }
      };
      if (body) opts.body = JSON.stringify(body);
      
      const res = await fetch(url, opts);
      return await res.json();
    }

    const pwdInput = document.getElementById('dsr-password');
    const toggleBtn = document.getElementById('toggle-pwd-btn');
    const eyeIcon = document.getElementById('eye-icon');

    toggleBtn.addEventListener('click', function() {
      if (pwdInput.type === 'password') {
        pwdInput.type = 'text';
        eyeIcon.className = 'fa-solid fa-eye-slash';
      } else {
        pwdInput.type = 'password';
        eyeIcon.className = 'fa-solid fa-eye';
      }
    });

    const form = document.getElementById('login-form');
    const btn = document.getElementById('login-btn');
    const errEl = document.getElementById('error-msg');

    form.addEventListener('submit', async function(e) {
      e.preventDefault();
      btn.disabled = true;
      btn.textContent = 'Signing in…';
      errEl.style.display = 'none';

      try {
        const data = await apiCall('../api/auth.php', 'POST', {
          email: document.getElementById('dsr-email').value,
          password: pwdInput.value
        });

        if (data.success) {
          btn.textContent = 'Success…';
          window.location.href = data.redirect;
        } else {
          errEl.textContent = data.message || 'Authentication failed.';
          errEl.style.display = 'block';
          btn.disabled = false;
          btn.textContent = 'Sign In to App';
        }
      } catch (err) {
        console.error(err);
        errEl.textContent = 'Connection error: Unable to reach auth service.';
        errEl.style.display = 'block';
        btn.disabled = false;
        btn.textContent = 'Sign In to App';
      }
    });

    // PWA Service Worker: Unregister old broken SWs then register the new one
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', async () => {
        try {
          // Step 1: Unregister ALL existing service workers (kill old broken SW)
          const registrations = await navigator.serviceWorker.getRegistrations();
          for (const reg of registrations) {
            await reg.unregister();
            console.log('Unregistered old SW:', reg.scope);
          }
          // Step 2: Clear ALL caches (remove stale cached PHP pages)
          const cacheNames = await caches.keys();
          await Promise.all(cacheNames.map(name => caches.delete(name)));
          console.log('All caches cleared.');
          // Step 3: Register the new safe SW
          const reg = await navigator.serviceWorker.register('sw.js');
          console.log('New DSR SW registered:', reg.scope);
        } catch (err) {
          console.error('DSR SW setup failed:', err);
        }
      });
    }
  </script>
</body>
</html>
