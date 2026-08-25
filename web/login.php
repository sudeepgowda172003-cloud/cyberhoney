<?php
require_once __DIR__ . '/auth.php';
if (Auth::check()) { header('Location: dashboard.php'); exit; }

$error = ''; $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::csrfValidate($_POST['csrf'] ?? '')) { $error = 'Invalid form submission.'; }
    else {
        $result = Auth::login($_POST['email'] ?? '', $_POST['password'] ?? '', isset($_POST['remember']));
        if ($result['success']) { header('Location: dashboard.php'); exit; }
        else { $error = $result['message']; }
    }
}
$csrf = Auth::csrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — HoneyGuard</title>
  <meta name="description" content="HoneyGuard Security Dashboard — Deception-based threat intelligence platform">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🛡️</text></svg>">
</head>
<body>
  <div class="bg-animated"></div>
  <div class="honeycomb-bg"></div>

  <!-- Floating Particles -->
  <div class="particle" style="top:15%;left:10%;animation-delay:0s;animation-duration:7s"></div>
  <div class="particle" style="top:70%;left:85%;animation-delay:1s;animation-duration:5s"></div>
  <div class="particle" style="top:40%;left:60%;animation-delay:2s;animation-duration:8s"></div>
  <div class="particle" style="top:80%;left:25%;animation-delay:3s;animation-duration:6s"></div>
  <div class="particle" style="top:20%;left:75%;animation-delay:1.5s;animation-duration:9s"></div>

  <div class="auth-wrapper">
    <div class="auth-card glass fade-in">
      <div class="auth-logo">
        <span class="logo-icon">🛡️</span>
        <h1>HoneyGuard</h1>
        <p>Deception-Based Security Intelligence</p>
      </div>

      <?php if ($error): ?>
        <div style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:#fca5a5;padding:12px 16px;border-radius:8px;margin-bottom:20px;font-size:.85rem">
          ⚠️ <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div style="background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.3);color:#86efac;padding:12px 16px;border-radius:8px;margin-bottom:20px;font-size:.85rem">
          ✅ <?= htmlspecialchars($success) ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="login.php">
        <input type="hidden" name="csrf" value="<?= $csrf ?>">

        <div class="form-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" class="form-input" placeholder="you@domain.com" required autofocus>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" class="form-input" placeholder="••••••••" required>
        </div>

        <div class="checkbox-wrap" style="margin-bottom:20px">
          <input type="checkbox" id="remember" name="remember">
          <label for="remember">Remember me for 30 days</label>
        </div>

        <button type="submit" class="btn btn-primary btn-block">
          🔐 Sign In
        </button>
      </form>

      <div style="text-align:center;margin-top:24px">
        <p style="color:var(--text-muted);font-size:.85rem">
          Don't have an account? <a href="register.php" style="font-weight:600">Create one</a>
        </p>
      </div>

      <div style="text-align:center;margin-top:20px;font-size:.7rem;color:var(--text-muted)">
        <span class="status-dot active"></span> Monitoring Agent: Active
        <br><code style="color:var(--accent);font-size:.65rem">v<?= APP_VERSION ?></code>
      </div>
    </div>
  </div>
</body>
</html>
