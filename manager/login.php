<?php
// manager/login.php
require_once __DIR__ . '/../config/session.php';

// If already logged in to manager, redirect to dashboard
if (isLoggedIn() && ($_SESSION['role'] ?? '') === 'manager') {
    header('Location: ' . getDashboardUrl());
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Manager Portal Login — Happy Bangladesh ERP</title>
  <meta name="description" content="Secure authentication for Happy Bangladesh ERP Manager Portal" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  
  <style>
    :root {
      --bg: #18181b;
      --card-bg: #27272a;
      --border-color: #52525b;
      --border-focus: #e4e4e7;
      --accent: #a1a1aa;
      --text-main: #f4f4f5;
      --text-dim: #a1a1aa;
      --input-bg: #18181b;
      --error-bg: rgba(239, 68, 68, 0.15);
      --error-border: rgba(239, 68, 68, 0.5);
      --error-text: #f87171;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      border-radius: 0 !important; /* STRICT REQUIREMENT: No border radius */
    }

    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, #09090b 0%, #18181b 50%, #27272a 100%);
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
      border: 2px solid var(--border-color);
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.6);
      padding: 3rem 2.5rem;
      position: relative;
    }

    .login-container::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 4px;
      background-color: var(--accent);
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
      filter: grayscale(100%) brightness(1.2);
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
      color: var(--accent);
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
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: var(--text-dim);
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
      border: 1px solid var(--border-color);
      color: var(--text-main);
      padding: 0.85rem 1rem;
      font-size: 0.95rem;
      outline: none;
      transition: all 0.2s ease;
    }

    .input-wrapper input:focus {
      border-color: var(--border-focus);
      background-color: #09090b;
      box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.1);
    }

    .input-wrapper input::placeholder {
      color: #52525b;
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
      background-color: #e4e4e7;
      color: #09090b;
      padding: 1rem;
      font-size: 0.9rem;
      font-weight: 700;
      border: none;
      cursor: pointer;
      text-transform: uppercase;
      letter-spacing: 0.1em;
      transition: all 0.2s ease;
      margin-top: 1rem;
    }

    .btn-login:hover {
      background-color: #ffffff;
      box-shadow: 0 0 12px rgba(255, 255, 255, 0.2);
    }

    .btn-login:active {
      background-color: #d4d4d8;
    }

    .btn-login:disabled {
      opacity: 0.6;
      cursor: not-allowed;
      box-shadow: none;
    }

    .error-msg {
      background-color: var(--error-bg);
      border: 1px solid var(--error-border);
      color: var(--error-text);
      padding: 0.75rem 1rem;
      font-size: 0.85rem;
      margin-bottom: 1.5rem;
      display: none;
      font-weight: 500;
    }

    .footer-note {
      margin-top: 2.5rem;
      padding-top: 1.5rem;
      border-top: 1px solid rgba(255, 255, 255, 0.05);
      text-align: center;
    }

    .footer-note p {
      font-size: 0.65rem;
      color: var(--text-dim);
      text-transform: uppercase;
      letter-spacing: 0.15em;
      font-weight: 600;
    }
  </style>
</head>
<body>

  <div class="login-container">
    <div class="brand">
      <img src="../assets/img/logo/logo.png" alt="Happy Bangladesh Logo" />
      <h2>Manager Portal</h2>
      <p>Operation Login</p>
    </div>

    <div id="error-msg" class="error-msg"></div>

    <form id="login-form">
      <div class="form-group">
        <label for="manager-email">Access Email</label>
        <div class="input-wrapper">
          <input id="manager-email" type="email" name="email" placeholder="manager@happy.com" required autocomplete="email" />
        </div>
      </div>

      <div class="form-group">
        <label for="manager-password">Security Token</label>
        <div class="input-wrapper">
          <input id="manager-password" type="password" name="password" placeholder="••••••••" required autocomplete="current-password" />
          <button type="button" id="toggle-pwd-btn" class="eye-toggle" title="Toggle password visibility">
            <i class="fa-solid fa-eye" id="eye-icon"></i>
          </button>
        </div>
      </div>

      <button type="submit" id="login-btn" class="btn-login">
        Authenticate Access
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

    const pwdInput = document.getElementById('manager-password');
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
      btn.textContent = 'Verifying Credentials…';
      errEl.style.display = 'none';

      try {
        const data = await apiCall('../api/auth.php', 'POST', {
          email: document.getElementById('manager-email').value,
          password: pwdInput.value
        });

        if (data.success) {
          btn.textContent = 'Access Granted…';
          window.location.href = data.redirect;
        } else {
          errEl.textContent = data.message || 'Authentication failed.';
          errEl.style.display = 'block';
          btn.disabled = false;
          btn.textContent = 'Authenticate Access';
        }
      } catch (err) {
        console.error(err);
        errEl.textContent = 'Connection error: Unable to reach auth service.';
        errEl.style.display = 'block';
        btn.disabled = false;
        btn.textContent = 'Authenticate Access';
      }
    });
  </script>
</body>
</html>
