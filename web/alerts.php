<?php
require_once __DIR__ . '/auth.php';
Auth::requireAuth();
$user = Auth::user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Alerts — HoneyGuard</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="bg-animated"></div>

  <nav class="sidebar" id="sidebar">
    <div class="sidebar-brand"><h2>🛡️ HoneyGuard</h2><span>SOC</span></div>
    <div class="sidebar-nav">
      <a href="dashboard.php" class="nav-item"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6z"/></svg> Dashboard</a>
      <a href="alerts.php" class="nav-item active"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg> Alerts</a>
      <a href="settings.php" class="nav-item"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35"/></svg> Settings</a>
      <a href="logout.php" class="nav-item" style="color:#ef4444"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7"/></svg> Logout</a>
    </div>
  </nav>

  <div class="main-content">
    <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
    <div class="header">
      <h1>🔔 Alert Management</h1>
      <div class="header-actions">
        <button class="btn btn-secondary btn-sm" onclick="markAllRead()">✓ Mark All Read</button>
        <?php if ($user['role'] === 'admin'): ?>
        <button class="btn btn-danger btn-sm" onclick="if(confirm('Delete alerts older than 30 days?'))purgeOld()">🗑 Purge Old</button>
        <?php endif; ?>
      </div>
    </div>

    <div class="filter-bar">
      <input type="text" id="search-input" class="search-input" placeholder="🔍 Search alerts...">
      <select id="level-filter" class="filter-select">
        <option value="">All Levels</option>
        <option value="CRITICAL">Critical</option>
        <option value="ALERT">Alert</option>
        <option value="WARNING">Warning</option>
        <option value="INFO">Info</option>
      </select>
      <select id="read-filter" class="filter-select">
        <option value="">All</option>
        <option value="0">Unread</option>
        <option value="1">Read</option>
      </select>
    </div>

    <div class="glass" style="padding:24px">
      <div class="table-wrapper">
        <table>
          <thead><tr><th>Time</th><th>Level</th><th>File</th><th>Action</th><th>IP</th><th>User</th><th>Message</th></tr></thead>
          <tbody id="alerts-tbody"><tr><td colspan="7" class="empty-state"><p>Loading...</p></td></tr></tbody>
        </table>
      </div>
      <div id="pagination" class="pagination"></div>
    </div>
  </div>

  <div class="modal-overlay" id="alert-modal">
    <div class="glass modal">
      <h2>🔍 Alert Details</h2>
      <div id="modal-content"></div>
      <button class="btn btn-danger btn-block" style="margin-top:20px" onclick="closeModal('alert-modal')">Close</button>
    </div>
  </div>

  <script src="assets/js/app.js"></script>
  <script>
    // Load alerts on page load
    document.addEventListener('DOMContentLoaded', () => { loadPage(); setupPageFilters(); });

    function loadPage(page = 1) {
      const s = document.getElementById('search-input')?.value || '';
      const l = document.getElementById('level-filter')?.value || '';
      const r = document.getElementById('read-filter')?.value ?? '';
      let url = `api/alerts.php?page=${page}&limit=25&search=${encodeURIComponent(s)}&level=${l}`;
      if (r !== '') url += `&is_read=${r}`;
      loadRecentAlerts(page, s, l);
    }

    function setupPageFilters() {
      let debounce;
      document.getElementById('search-input')?.addEventListener('input', () => { clearTimeout(debounce); debounce = setTimeout(() => loadPage(), 400); });
      document.getElementById('level-filter')?.addEventListener('change', () => loadPage());
      document.getElementById('read-filter')?.addEventListener('change', () => loadPage());
    }

    async function markAllRead() {
      await api('api/alerts.php', { method: 'PUT', body: '{}' });
      showToast('All alerts marked as read', 'success');
      loadPage();
    }

    async function purgeOld() {
      await api('api/alerts.php?older_than=30', { method: 'DELETE' });
      showToast('Old alerts purged', 'success');
      loadPage();
    }
  </script>
</body>
</html>
