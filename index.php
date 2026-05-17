<?php
// index.php — Login Page (Last sync: 2026-05-13 20:46)
require_once __DIR__ . '/config/session.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    header('Location: ' . getDashboardUrl());
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login — Happy Bangladesh ERP</title>
  <meta name="description" content="Login to Happy Bangladesh ERP system" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="assets/css/app.css" />
  <style>
    :root {
      --bg: #e0e5ec;
      --card-bg: #e0e5ec;
      --shadow-light: #ffffff;
      --shadow-dark: #a3b1c6;
      --accent: #4f46e5;
      --text-main: #2d3748;
      --text-dim: #718096;
    }

    body { 
      font-family: 'Inter', sans-serif;
      background-color: var(--bg);
      color: var(--text-main);
    }

    .login-bg {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      overflow: hidden;
    }

    /* Neumorphic Card */
    .login-card {
      background: var(--card-bg);
      border-radius: 2rem;
      padding: 3.5rem 2.5rem;
      width: 100%;
      max-width: 420px;
      box-shadow: 
        12px 12px 24px var(--shadow-dark),
        -12px -12px 24px var(--shadow-light);
      position: relative;
      z-index: 10;
      border: 1px solid rgba(255, 255, 255, 0.5);
    }

    .logo-container {
      margin-bottom: 2.5rem;
      text-align: center;
    }

    /* Neumorphic Inset Input */
    .input-group {
      margin-bottom: 1.75rem;
    }

    .input-group label {
      display: block;
      color: var(--text-dim);
      font-size: 0.75rem;
      font-weight: 700;
      margin-bottom: 0.75rem;
      margin-left: 0.5rem;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    .input-wrapper {
      position: relative;
      background: var(--bg);
      border-radius: 1rem;
      box-shadow: 
        inset 4px 4px 8px var(--shadow-dark),
        inset -4px -4px 8px var(--shadow-light);
      transition: all 0.3s ease;
    }

    .input-wrapper input {
      width: 100%;
      background: transparent;
      border: none;
      padding: 1rem 1.25rem;
      color: var(--text-main);
      font-size: 0.95rem;
      outline: none;
      border-radius: 1rem;
    }

    .input-wrapper:focus-within {
      box-shadow: 
        inset 2px 2px 4px var(--shadow-dark),
        inset -2px -2px 4px var(--shadow-light);
      border: 1px solid rgba(79, 70, 229, 0.2);
    }

    .input-wrapper input::placeholder {
      color: rgba(0, 0, 0, 0.35);
    }

    /* Neumorphic Button */
    .btn-login {
      width: 100%;
      background: var(--bg);
      color: var(--accent);
      padding: 1.1rem;
      border-radius: 1rem;
      font-weight: 700;
      font-size: 1rem;
      border: none;
      cursor: pointer;
      box-shadow: 
        6px 6px 12px var(--shadow-dark),
        -6px -6px 12px var(--shadow-light);
      transition: all 0.2s ease;
      margin-top: 1.5rem;
      text-transform: uppercase;
      letter-spacing: 0.1em;
    }

    .btn-login:hover {
      box-shadow: 
        3px 3px 6px var(--shadow-dark),
        -3px -3px 6px var(--shadow-light);
      color: #312e81;
    }

    .btn-login:active {
      box-shadow: 
        inset 3px 3px 6px var(--shadow-dark),
        inset -3px -3px 6px var(--shadow-light);
      color: var(--accent);
    }

    .btn-login:disabled {
      opacity: 0.5;
      cursor: not-allowed;
      box-shadow: none;
    }

    .error-msg {
      background: rgba(239, 68, 68, 0.05);
      border: 1px solid rgba(239, 68, 68, 0.2);
      color: #ef4444;
      border-radius: 0.75rem;
      padding: 0.75rem 1rem;
      font-size: 0.85rem;
      margin-bottom: 1.5rem;
      display: none;
      text-align: center;
      box-shadow: inset 2px 2px 4px rgba(0,0,0,0.05);
    }

    .eye-toggle {
      position: absolute;
      right: 1rem;
      top: 50%;
      transform: translateY(-50%);
      color: var(--text-dim);
      background: none;
      border: none;
      cursor: pointer;
      padding: 0.25rem;
    }

    .eye-toggle:hover { color: var(--accent); }

    /* Hide Decorative Orbs for Light Neumorphism */
    .orb {
      display: none;
    }
  </style>
</head>
<body class="login-bg">

  <div class="login-card">
    <div class="logo-container flex flex-col items-center">
      <img src="assets/img/logo/logo-black.png" alt="Happy Bangladesh" class="h-14 w-auto object-contain mb-3 mx-auto" />
    </div>

    <div id="error-msg" class="error-msg"></div>

    <form id="login-form">
      <div class="input-group">
        <label for="email">Access Email</label>
        <div class="input-wrapper">
          <input id="email" type="email" name="email" placeholder="nexus@happy.com" required autocomplete="email" />
        </div>
      </div>

      <div class="input-group">
        <label for="password">Security Token</label>
        <div class="input-wrapper">
          <input id="password" type="password" name="password" placeholder="••••••••" required autocomplete="current-password" />
          <button type="button" onclick="togglePwd()" class="eye-toggle">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
          </button>
        </div>
      </div>

      <button type="submit" id="login-btn" class="btn-login">
        Authenticate
      </button>
    </form>

    <div class="mt-8 pt-6 border-t border-white/5 text-center">
      <p class="text-[10px] text-slate-600 font-bold uppercase tracking-widest">
        Secured by Happy Bangladesh Cloud
      </p>
    </div>
  </div>

  <script src="assets/js/app.js"></script>
  <script>
    function togglePwd() {
      const pwd = document.getElementById('password');
      pwd.type = pwd.type === 'password' ? 'text' : 'password';
    }

    document.getElementById('login-form').addEventListener('submit', async function(e) {
      e.preventDefault();
      const btn = document.getElementById('login-btn');
      const errEl = document.getElementById('error-msg');
      btn.disabled = true;
      btn.textContent = 'Authenticating…';
      errEl.style.display = 'none';

      try {
        const data = await api('api/auth.php', 'POST', {
          email: document.getElementById('email').value,
          password: document.getElementById('password').value
        });

        if (data.success) {
          btn.textContent = 'Access Granted…';
          btn.style.color = 'var(--neon-cyan)';
          btn.style.boxShadow = '0 0 20px var(--neon-cyan)';
          window.location.href = data.redirect;
        } else {
          errEl.textContent = data.message || 'Authentication failed.';
          errEl.style.display = 'block';
          btn.disabled = false;
          btn.textContent = 'Authenticate';
        }
      } catch (err) {
        errEl.textContent = 'System connection error.';
        errEl.style.display = 'block';
        btn.disabled = false;
        btn.textContent = 'Authenticate';
      }
    });
  </script>
</body>
</html>
</body>
</html>
