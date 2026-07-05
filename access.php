<?php
session_start();
$status = $_SESSION['status'] ?? null;
$is_success = $status !== null && stripos($status, 'success') !== false;
unset($_SESSION['status']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Firewall Login</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg-0: #070b16;
      --bg-1: #0d1628;
      --card: rgba(20, 28, 46, 0.65);
      --card-border: rgba(255, 255, 255, 0.08);
      --text: #eaf0fb;
      --muted: #8a97b1;
      --field: rgba(9, 14, 26, 0.7);
      --field-border: rgba(255, 255, 255, 0.10);
      --accent: #4f8cff;
      --accent-2: #7c5cff;
      --accent-glow: rgba(79, 140, 255, 0.45);
      --ok: #34d399;
      --err: #fb7185;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      font-family: 'Inter', system-ui, sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px;
      color: var(--text);
      background: radial-gradient(1200px 800px at 15% -10%, #16233f 0%, transparent 55%),
                  radial-gradient(1000px 700px at 110% 110%, #241a45 0%, transparent 50%),
                  linear-gradient(160deg, var(--bg-0), var(--bg-1));
      overflow: hidden;
      position: relative;
    }

    /* Soft animated aurora blobs */
    .aurora {
      position: fixed;
      inset: -20% -20% auto auto;
      width: 60vmax;
      height: 60vmax;
      border-radius: 50%;
      filter: blur(90px);
      opacity: 0.35;
      pointer-events: none;
      background: radial-gradient(circle at 30% 30%, var(--accent), transparent 60%);
      animation: drift 18s ease-in-out infinite alternate;
    }
    .aurora.two {
      inset: auto auto -25% -20%;
      background: radial-gradient(circle at 60% 60%, var(--accent-2), transparent 60%);
      animation-duration: 22s;
      animation-direction: alternate-reverse;
    }
    @keyframes drift {
      from { transform: translate(0, 0) scale(1); }
      to   { transform: translate(-6%, 8%) scale(1.15); }
    }

    .card {
      position: relative;
      width: 100%;
      max-width: 400px;
      background: var(--card);
      border: 1px solid var(--card-border);
      border-radius: 22px;
      padding: 40px 34px 30px;
      backdrop-filter: blur(22px);
      -webkit-backdrop-filter: blur(22px);
      box-shadow: 0 30px 80px -20px rgba(0, 0, 0, 0.7),
                  inset 0 1px 0 rgba(255, 255, 255, 0.06);
      animation: rise 0.6s cubic-bezier(0.16, 1, 0.3, 1);
    }
    @keyframes rise {
      from { opacity: 0; transform: translateY(24px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    .brand { text-align: center; margin-bottom: 28px; }

    .shield {
      width: 62px;
      height: 62px;
      margin: 0 auto 16px;
      display: grid;
      place-items: center;
      border-radius: 18px;
      background: linear-gradient(145deg, var(--accent), var(--accent-2));
      box-shadow: 0 12px 30px -8px var(--accent-glow),
                  inset 0 1px 0 rgba(255, 255, 255, 0.3);
      animation: floaty 4s ease-in-out infinite;
    }
    .shield svg { width: 30px; height: 30px; color: #fff; }
    @keyframes floaty {
      0%, 100% { transform: translateY(0); }
      50%      { transform: translateY(-5px); }
    }

    .brand h1 { font-size: 22px; font-weight: 800; letter-spacing: -0.02em; }
    .brand p { color: var(--muted); font-size: 13px; margin-top: 5px; }

    .field { position: relative; margin-bottom: 18px; }

    .field .icon {
      position: absolute;
      left: 15px;
      top: 50%;
      transform: translateY(-50%);
      width: 18px;
      height: 18px;
      color: var(--muted);
      pointer-events: none;
      transition: color 0.2s ease;
    }

    .field input {
      width: 100%;
      height: 52px;
      padding: 0 46px;
      font-size: 15px;
      color: var(--text);
      background: var(--field);
      border: 1px solid var(--field-border);
      border-radius: 13px;
      outline: none;
      transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
    }
    .field input::placeholder { color: var(--muted); }
    .field input:focus {
      border-color: var(--accent);
      background: rgba(9, 14, 26, 0.9);
      box-shadow: 0 0 0 4px rgba(79, 140, 255, 0.15);
    }
    .field input:focus ~ .icon { color: var(--accent); }

    .toggle {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      width: 34px;
      height: 34px;
      display: grid;
      place-items: center;
      border: none;
      background: transparent;
      color: var(--muted);
      cursor: pointer;
      border-radius: 9px;
      transition: color 0.2s ease, background 0.2s ease;
    }
    .toggle:hover { color: var(--text); background: rgba(255, 255, 255, 0.05); }
    .toggle svg { width: 19px; height: 19px; }

    .btn {
      position: relative;
      width: 100%;
      height: 52px;
      margin-top: 6px;
      border: none;
      border-radius: 13px;
      font-family: inherit;
      font-size: 15px;
      font-weight: 700;
      color: #fff;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 9px;
      background: linear-gradient(135deg, var(--accent), var(--accent-2));
      box-shadow: 0 14px 30px -10px var(--accent-glow);
      transition: transform 0.15s ease, box-shadow 0.2s ease, filter 0.2s ease;
    }
    .btn:hover { transform: translateY(-2px); filter: brightness(1.07); box-shadow: 0 18px 38px -10px var(--accent-glow); }
    .btn:active { transform: translateY(0); }
    .btn .btn-label { display: flex; align-items: center; gap: 9px; }
    .btn svg { width: 18px; height: 18px; }
    .btn.loading { pointer-events: none; }
    .btn.loading .btn-label { visibility: hidden; }
    .btn .spinner {
      display: none;
      position: absolute;
      width: 20px; height: 20px;
      border: 2.5px solid rgba(255, 255, 255, 0.35);
      border-top-color: #fff;
      border-radius: 50%;
      animation: spin 0.7s linear infinite;
    }
    .btn.loading .spinner { display: block; }
    @keyframes spin { to { transform: rotate(360deg); } }

    .alert {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 20px;
      padding: 12px 14px;
      border-radius: 12px;
      font-size: 13.5px;
      font-weight: 500;
      border: 1px solid transparent;
      animation: rise 0.35s ease;
    }
    .alert svg { width: 18px; height: 18px; flex-shrink: 0; }
    .alert.error   { color: var(--err); background: rgba(251, 113, 133, 0.10); border-color: rgba(251, 113, 133, 0.25); }
    .alert.success { color: var(--ok);  background: rgba(52, 211, 153, 0.10); border-color: rgba(52, 211, 153, 0.25); }

    .foot {
      margin-top: 24px;
      text-align: center;
      font-size: 12px;
      color: var(--muted);
    }

    @media (max-width: 440px) {
      .card { padding: 32px 22px 24px; border-radius: 18px; }
    }
  </style>
</head>
<body>
  <div class="aurora"></div>
  <div class="aurora two"></div>

  <main class="card">
    <div class="brand">
      <div class="shield">
        <!-- shield-lock icon -->
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 3l7 3v5c0 4.5-3 7.9-7 9-4-1.1-7-4.5-7-9V6l7-3z"/>
          <path d="M9.5 12h5v3.5h-5z"/>
          <path d="M10.5 12v-1.2a1.5 1.5 0 0 1 3 0V12"/>
        </svg>
      </div>
      <h1>Firewall Login</h1>
      <p>Sign in to authorize your access</p>
    </div>

    <?php if ($status !== null): ?>
      <div class="alert <?php echo $is_success ? 'success' : 'error'; ?>">
        <?php if ($is_success): ?>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
        <?php else: ?>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>
        <?php endif; ?>
        <span><?php echo htmlspecialchars($status, ENT_QUOTES); ?></span>
      </div>
    <?php endif; ?>

    <form action="inc/code.php" method="post" id="loginForm" novalidate>
      <div class="field">
        <input type="text" id="user" name="user" placeholder="Username" autocomplete="username" required>
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      </div>

      <div class="field">
        <input type="password" id="password" name="password" placeholder="Password" autocomplete="current-password" required>
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
        <button type="button" class="toggle" id="togglePassword" aria-label="Show password">
          <svg id="eyeIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
        </button>
      </div>

      <button type="submit" name="submit" class="btn" id="submitBtn">
        <span class="btn-label">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="M10 17l5-5-5-5M15 12H3"/></svg>
          Sign In
        </span>
        <span class="spinner"></span>
      </button>
    </form>

    <div class="foot">Protected access &middot; Authorized users only</div>
  </main>

  <script>
    // Password visibility toggle
    const eyeOpen = '<path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/>';
    const eyeOff  = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19M1 1l22 22"/>';
    const pwd = document.getElementById('password');
    const eye = document.getElementById('eyeIcon');
    document.getElementById('togglePassword').addEventListener('click', function () {
      const show = pwd.type === 'password';
      pwd.type = show ? 'text' : 'password';
      eye.innerHTML = show ? eyeOff : eyeOpen;
      this.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    });

    // Lightweight client-side guard + loading state
    document.getElementById('loginForm').addEventListener('submit', function (e) {
      const u = document.getElementById('user');
      const p = document.getElementById('password');
      if (!u.value.trim() || !p.value.trim()) {
        e.preventDefault();
        (!u.value.trim() ? u : p).focus();
        return;
      }
      document.getElementById('submitBtn').classList.add('loading');
    });

    window.addEventListener('load', () => document.getElementById('user').focus());
  </script>
</body>
</html>
