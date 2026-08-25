<?php
require_once __DIR__ . '/auth.php';
if (Auth::check()) { header('Location: dashboard.php'); exit; }

$error = ''; $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::csrfValidate($_POST['csrf'] ?? '')) { $error = 'Invalid form submission.'; }
    else {
        $result = Auth::register($_POST['username'] ?? '', $_POST['email'] ?? '', $_POST['password'] ?? '');
        if ($result['success']) { $success = 'Account created! You can now sign in.'; }
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
  <title>Register — HoneyGuard</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🛡️</text></svg>">
</head>
<body>
  <div class="bg-animated"></div>
  <div class="honeycomb-bg"></div>
  <div class="particle" style="top:10%;left:20%;animation-delay:0s"></div>
  <div class="particle" style="top:60%;left:80%;animation-delay:1.5s"></div>
  <div class="particle" style="top:30%;left:50%;animation-delay:2.5s"></div>

  <div class="auth-wrapper">
    <div class="auth-card glass fade-in">
      <div class="auth-logo">
        <span class="logo-icon">🛡️</span>
        <h1>HoneyGuard</h1>
        <p>Create Your Account</p>
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

      <form method="POST" action="register.php">
        <input type="hidden" name="csrf" value="<?= $csrf ?>">

        <div class="form-group">
          <label for="username">Username</label>
          <input type="text" id="username" name="username" class="form-input" placeholder="e.g. admin_user" required minlength="3" maxlength="50">
        </div>

        <div class="form-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" class="form-input" placeholder="you@domain.com" required>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" class="form-input" placeholder="Min 8 characters" required minlength="8">
          <div id="pw-strength" style="height:3px;margin-top:6px;border-radius:2px;transition:all .3s"></div>
        </div>

        <button type="submit" class="btn btn-primary btn-block">
          🚀 Create Account
        </button>
      </form>

      <div style="text-align:center;margin-top:24px">
        <p style="color:var(--text-muted);font-size:.85rem">
          Already have an account? <a href="login.php" style="font-weight:600">Sign in</a>
        </p>
      </div>
    </div>
  </div>

  <script>
    // Password strength indicator
    document.getElementById('password')?.addEventListener('input', function() {
      const bar = document.getElementById('pw-strength');
      const len = this.value.length;
      if (len === 0) { bar.style.width = '0'; return; }
      const score = Math.min(100, (len / 16) * 100);
      bar.style.width = score + '%';
      bar.style.background = score < 40 ? '#ef4444' : score < 70 ? '#f59e0b' : '#22c55e';
    });
  </script>
</body>
</html>
