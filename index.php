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
    body { font-family: 'Inter', sans-serif; }
    .login-bg {
      background: linear-gradient(135deg, #0F172A 0%, #1E1B4B 50%, #0F172A 100%);
      min-height: 100vh;
    }
    .login-card {
      background: rgba(255,255,255,0.04);
      backdrop-filter: blur(16px);
      border: 1px solid rgba(255,255,255,0.1);
      border-radius: 1.5rem;
    }
    .grid-pattern {
      background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,0.08) 1px, transparent 0);
      background-size: 32px 32px;
    }
    .glow { box-shadow: 0 0 80px rgba(79,70,229,0.35); }
    .input-dark {
      background: rgba(255,255,255,0.07);
      border: 1px solid rgba(255,255,255,0.12);
      color: #fff;
      border-radius: 0.625rem;
      padding: 0.75rem 1rem;
      width: 100%;
      font-size: 0.875rem;
      transition: border-color 0.2s, box-shadow 0.2s;
      outline: none;
    }
    .input-dark::placeholder { color: rgba(255,255,255,0.35); }
    .input-dark:focus {
      border-color: #6366F1;
      box-shadow: 0 0 0 3px rgba(99,102,241,0.25);
    }
    .btn-login {
      width: 100%;
      background: linear-gradient(135deg, #4F46E5, #7C3AED);
      color: #fff;
      padding: 0.75rem;
      border-radius: 0.625rem;
      font-weight: 600;
      font-size: 0.9rem;
      border: none;
      cursor: pointer;
      transition: opacity 0.2s, transform 0.1s;
    }
    .btn-login:hover { opacity: 0.92; transform: translateY(-1px); }
    .btn-login:active { transform: translateY(0); }
    .btn-login:disabled { opacity: 0.5; cursor: not-allowed; }
    .error-msg {
      background: rgba(239,68,68,0.15);
      border: 1px solid rgba(239,68,68,0.3);
      color: #FCA5A5;
      border-radius: 0.5rem;
      padding: 0.625rem 1rem;
      font-size: 0.8rem;
      display: none;
    }
    .floating-orb {
      position: absolute;
      border-radius: 50%;
      filter: blur(80px);
      opacity: 0.15;
      pointer-events: none;
    }
  </style>
</head>
<body class="login-bg grid-pattern flex items-center justify-center relative overflow-hidden">

  <!-- Floating orbs -->
  <div class="floating-orb w-96 h-96 bg-indigo-500 -top-20 -left-20"></div>
  <div class="floating-orb w-72 h-72 bg-purple-600 bottom-20 right-10"></div>

  <div class="w-full max-w-sm mx-4 relative z-10">

    <!-- Logo -->
    <div class="text-center mb-8">
      <div class="inline-flex items-center justify-center w-16 h-16 bg-indigo-600 rounded-2xl shadow-lg mb-4 glow">
        <span class="text-white font-black text-2xl">HB</span>
      </div>
      <h1 class="text-white text-2xl font-bold">Happy Bangladesh</h1>
      <p class="text-slate-400 text-sm mt-1">ERP Management System</p>
    </div>

    <!-- Card -->
    <div class="login-card p-8 glow">
      <h2 class="text-white font-semibold text-lg mb-6">Sign in to your account</h2>

      <div id="error-msg" class="error-msg mb-4">Invalid credentials. Please try again.</div>

      <form id="login-form" class="space-y-4">
        <div>
          <label class="block text-slate-300 text-xs font-medium mb-1.5">Email Address</label>
          <input id="email" type="email" name="email" class="input-dark" placeholder="admin@happy.com" required autocomplete="email" />
        </div>
        <div>
          <label class="block text-slate-300 text-xs font-medium mb-1.5">Password</label>
          <div class="relative">
            <input id="password" type="password" name="password" class="input-dark pr-10" placeholder="••••••••" required autocomplete="current-password" />
            <button type="button" onclick="togglePwd()" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white transition">
              <svg id="eye-icon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            </button>
          </div>
        </div>
        <button type="submit" id="login-btn" class="btn-login mt-2">
          Sign In
        </button>
      </form>

      <p class="text-slate-500 text-xs text-center mt-6">
        Default: admin@happy.com / password
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
      btn.textContent = 'Signing in…';
      errEl.style.display = 'none';

      const data = await api('api/auth.php', 'POST', {
        email: document.getElementById('email').value,
        password: document.getElementById('password').value
      });

      if (data.success) {
        btn.textContent = 'Redirecting…';
        window.location.href = data.redirect;
      } else {
        errEl.textContent = data.message || 'Invalid credentials.';
        errEl.style.display = 'block';
        btn.disabled = false;
        btn.textContent = 'Sign In';
      }
    });
  </script>
</body>
</html>
