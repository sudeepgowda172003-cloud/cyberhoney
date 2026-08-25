<?php
require_once __DIR__ . '/auth.php';
Auth::requireAuth();
$user = Auth::user();
$msg = ''; $msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!Auth::csrfValidate($_POST['csrf'] ?? '')) { $msg = 'Invalid form.'; $msgType = 'error'; }
    elseif ($_POST['action'] === 'change_password') {
        $r = Auth::changePassword($user['id'], $_POST['current_password'], $_POST['new_password']);
        $msg = $r['message']; $msgType = $r['success'] ? 'success' : 'error';
    }
    elseif ($_POST['action'] === 'generate_key') {
        $key = Auth::generateApiKey($user['id'], $_POST['key_name'] ?? 'Agent');
        $msg = "API Key created: $key — Copy it now, it won't be shown again!"; $msgType = 'success';
    }
}
$csrf = Auth::csrfToken();
$apiKeys = Database::query('SELECT id, key_prefix, name, is_active, last_used, created_at FROM api_keys WHERE user_id = ? ORDER BY created_at DESC', [$user['id']]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Settings — HoneyGuard</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="bg-animated"></div>

  <nav class="sidebar" id="sidebar">
    <div class="sidebar-brand"><h2>🛡️ HoneyGuard</h2><span>SOC</span></div>
    <div class="sidebar-nav">
      <a href="dashboard.php" class="nav-item"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6z"/></svg> Dashboard</a>
      <a href="alerts.php" class="nav-item"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg> Alerts</a>
      <a href="settings.php" class="nav-item active"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35"/></svg> Settings</a>
      <a href="logout.php" class="nav-item" style="color:#ef4444"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7"/></svg> Logout</a>
    </div>
  </nav>

  <div class="main-content">
    <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
    <div class="header"><h1>⚙️ Settings</h1></div>

    <?php if ($msg): ?>
    <div style="background:rgba(<?= $msgType==='success'?'34,197,94':'239,68,68' ?>,.1);border:1px solid rgba(<?= $msgType==='success'?'34,197,94':'239,68,68' ?>,.3);color:<?= $msgType==='success'?'#86efac':'#fca5a5' ?>;padding:14px 20px;border-radius:8px;margin-bottom:20px;font-size:.85rem;word-break:break-all">
      <?= $msgType==='success'?'✅':'⚠️' ?> <?= htmlspecialchars($msg) ?>
    </div>
    <?php endif; ?>

    <div class="settings-grid">
      <!-- Profile -->
      <div class="glass settings-section fade-in">
        <h3>👤 Profile</h3>
        <div style="margin-bottom:16px">
          <div style="font-size:.8rem;color:var(--text-muted);margin-bottom:4px">Username</div>
          <div style="font-weight:600"><?= htmlspecialchars($user['username']) ?></div>
        </div>
        <div style="margin-bottom:16px">
          <div style="font-size:.8rem;color:var(--text-muted);margin-bottom:4px">Email</div>
          <div><?= htmlspecialchars($user['email']) ?></div>
        </div>
        <div>
          <div style="font-size:.8rem;color:var(--text-muted);margin-bottom:4px">Role</div>
          <span class="badge badge-<?= $user['role']==='admin'?'warning':'info' ?>"><?= $user['role'] ?></span>
        </div>
      </div>

      <!-- Change Password -->
      <div class="glass settings-section fade-in" style="animation-delay:.1s">
        <h3>🔑 Change Password</h3>
        <form method="POST">
          <input type="hidden" name="csrf" value="<?= $csrf ?>">
          <input type="hidden" name="action" value="change_password">
          <div class="form-group">
            <label>Current Password</label>
            <input type="password" name="current_password" class="form-input" required>
          </div>
          <div class="form-group">
            <label>New Password</label>
            <input type="password" name="new_password" class="form-input" required minlength="8">
          </div>
          <button type="submit" class="btn btn-primary btn-sm">Update Password</button>
        </form>
      </div>

      <!-- API Keys -->
      <div class="glass settings-section fade-in" style="animation-delay:.2s;grid-column:1/-1">
        <h3>🔗 API Keys (for Python Agent)</h3>
        <p style="font-size:.8rem;color:var(--text-muted);margin-bottom:16px">
          Generate an API key to authenticate your local Python monitoring agent with this dashboard.
        </p>
        <form method="POST" style="display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap">
          <input type="hidden" name="csrf" value="<?= $csrf ?>">
          <input type="hidden" name="action" value="generate_key">
          <input type="text" name="key_name" class="form-input" placeholder="Key name (e.g. My Agent)" style="flex:1;min-width:200px">
          <button type="submit" class="btn btn-primary btn-sm">Generate Key</button>
        </form>

        <?php if ($apiKeys): ?>
        <table>
          <thead><tr><th>Name</th><th>Prefix</th><th>Status</th><th>Last Used</th><th>Created</th></tr></thead>
          <tbody>
            <?php foreach ($apiKeys as $k): ?>
            <tr>
              <td><?= htmlspecialchars($k['name']) ?></td>
              <td><code><?= htmlspecialchars($k['key_prefix']) ?>...</code></td>
              <td><span class="badge badge-<?= $k['is_active']?'success':'alert' ?>"><?= $k['is_active']?'Active':'Revoked' ?></span></td>
              <td><?= $k['last_used'] ? date('M j, H:i', strtotime($k['last_used'])) : 'Never' ?></td>
              <td><?= date('M j, Y', strtotime($k['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state"><div class="icon">🔑</div><p>No API keys yet. Generate one to connect your agent.</p></div>
        <?php endif; ?>

        <div style="margin-top:20px;padding:16px;background:rgba(0,0,0,.2);border-radius:8px;font-size:.8rem;color:var(--text-secondary)">
          <strong>Agent Configuration:</strong><br>
          <code style="color:var(--accent)">python main.py --monitor --push-url https://soctestone.free.je/api/ingest.php --api-key YOUR_KEY</code>
        </div>
      </div>
    </div>
  </div>

  <script src="assets/js/app.js"></script>
</body>
</html>
