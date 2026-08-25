<?php
require_once __DIR__ . '/auth.php';
Auth::requireAuth();
$user = Auth::user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard — HoneyGuard SOC</title>
  <meta name="description" content="HoneyGuard Security Operations Center — Real-time threat monitoring dashboard">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🛡️</text></svg>">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
  <div class="bg-animated"></div>
  <div id="dashboard-page"></div>

  <!-- Sidebar -->
  <nav class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <h2>🛡️ HoneyGuard</h2>
      <span>Security Operations Center</span>
    </div>
    <div class="sidebar-nav">
      <a href="dashboard.php" class="nav-item active">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
        Dashboard
      </a>
      <a href="alerts.php" class="nav-item">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
        Alerts
      </a>
      <a href="settings.php" class="nav-item">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        Settings
      </a>
      <a href="logout.php" class="nav-item" style="margin-top:auto;color:#ef4444">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
        Logout
      </a>
    </div>
    <div class="sidebar-footer">
      <div style="display:flex;align-items:center;gap:10px">
        <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--accent),#f97316);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;color:#0a0e17">
          <?= strtoupper(substr($user['username'] ?? 'U', 0, 1)) ?>
        </div>
        <div>
          <div style="font-size:.85rem;font-weight:600"><?= htmlspecialchars($user['username'] ?? 'User') ?></div>
          <div style="font-size:.7rem;color:var(--text-muted)"><?= htmlspecialchars($user['role'] ?? 'viewer') ?></div>
        </div>
      </div>
    </div>
  </nav>

  <!-- Main Content -->
  <div class="main-content">
    <!-- Mobile Menu Toggle -->
    <button class="menu-toggle" onclick="toggleSidebar()">☰</button>

    <!-- Header -->
    <div class="header">
      <h1>📊 Security Dashboard</h1>
      <div class="header-actions">
        <span id="threat-level" class="threat-badge threat-low">🟢 Low</span>
        <span class="threat-badge threat-low"><span class="status-dot active"></span> Monitoring Active</span>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
      <div class="glass stat-card fade-in">
        <div class="stat-value" id="stat-total">0</div>
        <div class="stat-label">Total Alerts</div>
        <div id="stat-trend" class="stat-trend"></div>
      </div>
      <div class="glass stat-card fade-in" style="animation-delay:.1s">
        <div class="stat-value" id="stat-critical" style="color:#ef4444">0</div>
        <div class="stat-label">Critical</div>
      </div>
      <div class="glass stat-card fade-in" style="animation-delay:.2s">
        <div class="stat-value" id="stat-today">0</div>
        <div class="stat-label" id="stat-today-label">Today</div>
      </div>
      <div class="glass stat-card fade-in" style="animation-delay:.3s">
        <div class="stat-value" id="stat-files">0</div>
        <div class="stat-label">Files Targeted</div>
      </div>
    </div>

    <!-- Charts -->
    <div class="charts-grid">
      <div class="glass chart-card fade-in" style="animation-delay:.15s">
        <h3>📈 Threats Over Time</h3>
        <div style="height:220px"><canvas id="chart-timeline"></canvas></div>
      </div>
      <div class="glass chart-card fade-in" style="animation-delay:.25s">
        <h3>🔐 Security Score</h3>
        <div class="score-gauge" style="height:180px;position:relative">
          <canvas id="chart-score"></canvas>
          <div class="score-value" style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center">
            <span class="number" id="score-number" style="font-size:2.2rem;font-weight:800;font-family:var(--mono)">0</span>
            <span class="label" style="font-size:.7rem;color:var(--text-muted)">/ 100</span>
          </div>
        </div>
      </div>
      <div class="glass chart-card fade-in" style="animation-delay:.35s">
        <h3>🔥 Top Targeted Files</h3>
        <div style="height:220px"><canvas id="chart-files"></canvas></div>
      </div>
    </div>

    <div class="charts-grid" style="grid-template-columns:1fr 1fr">
      <div class="glass chart-card fade-in" style="animation-delay:.4s">
        <h3>⚔️ Attack Types</h3>
        <div style="height:250px"><canvas id="chart-actions"></canvas></div>
      </div>
      <div class="glass chart-card fade-in" style="animation-delay:.45s">
        <h3>🎯 Alert Levels</h3>
        <div style="height:250px"><canvas id="chart-levels"></canvas></div>
      </div>
    </div>

    <!-- Recent Alerts Table -->
    <div class="glass fade-in" style="padding:24px;animation-delay:.5s">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:12px">
        <h3 style="font-size:1.1rem">📋 Recent Alerts</h3>
        <div class="filter-bar" style="margin-bottom:0">
          <input type="text" id="search-input" class="search-input" placeholder="🔍 Search alerts...">
          <input type="date" id="date-filter" class="filter-select" title="Filter by Day">
          <select id="level-filter" class="filter-select">
            <option value="">All Levels</option>
            <option value="CRITICAL">Critical</option>
            <option value="ALERT">Alert</option>
            <option value="WARNING">Warning</option>
            <option value="INFO">Info</option>
          </select>
        </div>
      </div>
      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Time</th><th>Level</th><th>File</th><th>Action</th><th>IP</th><th>User</th><th>Message</th>
            </tr>
          </thead>
          <tbody id="alerts-tbody">
            <tr><td colspan="7" class="empty-state"><div class="icon">🔍</div><p>Loading alerts...</p></td></tr>
          </tbody>
        </table>
      </div>
      <div id="pagination" class="pagination"></div>
    </div>
  </div>

  <!-- Alert Detail Modal -->
  <div class="modal-overlay" id="alert-modal">
    <div class="glass modal">
      <h2>🔍 Alert Details</h2>
      <div id="modal-content"></div>
      <button class="btn btn-danger btn-block" style="margin-top:20px" onclick="closeModal('alert-modal')">Close</button>
    </div>
  </div>

  <script src="assets/js/app.js"></script>
  <script src="assets/js/charts.js"></script>
</body>
</html>
