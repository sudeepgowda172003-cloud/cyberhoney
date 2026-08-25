/**
 * HoneyGuard v2.0 — Core Frontend Logic
 */

// ── Toast Notifications ──
function showToast(message, type = 'success', duration = 4000) {
  let container = document.querySelector('.toast-container');
  if (!container) {
    container = document.createElement('div');
    container.className = 'toast-container';
    document.body.appendChild(container);
  }
  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;
  toast.textContent = message;
  container.appendChild(toast);
  requestAnimationFrame(() => toast.classList.add('show'));
  setTimeout(() => {
    toast.classList.remove('show');
    setTimeout(() => toast.remove(), 300);
  }, duration);
}

// ── Animated Counters ──
function animateCounter(el, target, duration = 1200) {
  let start = 0;
  const step = (ts) => {
    if (!start) start = ts;
    const progress = Math.min((ts - start) / duration, 1);
    el.textContent = Math.floor(progress * target);
    if (progress < 1) requestAnimationFrame(step);
    else el.textContent = target;
  };
  requestAnimationFrame(step);
}

// ── API Helper ──
async function api(url, options = {}) {
  try {
    const res = await fetch(url, { headers: { 'Content-Type': 'application/json' }, ...options });
    return await res.json();
  } catch (e) {
    console.error('API Error:', e);
    showToast('Network error', 'error');
    return null;
  }
}

// ── Sidebar Toggle ──
function toggleSidebar() {
  document.querySelector('.sidebar')?.classList.toggle('open');
}

// ── Modal ──
function openModal(id) {
  document.getElementById(id)?.classList.add('active');
}
function closeModal(id) {
  document.getElementById(id)?.classList.remove('active');
}

// ── Dashboard Init ──
async function initDashboard() {
  const dateInput = document.getElementById('date-filter');
  const dateVal = dateInput ? dateInput.value : '';
  
  let url = 'api/stats.php?type=overview';
  if (dateVal) url += '&date=' + encodeURIComponent(dateVal);

  const data = await api(url);
  if (!data) return;

  // Animate stat counters
  const mapping = {
    'stat-total': data.total_alerts,
    'stat-critical': data.critical,
    'stat-files': data.unique_files,
    'stat-score': data.security_score,
    'stat-unread': data.unread,
    'stat-today': data.today,
  };
  Object.entries(mapping).forEach(([id, val]) => {
    const el = document.getElementById(id);
    if (el) animateCounter(el, val);
  });
  
  const todayLabel = document.getElementById('stat-today-label');
  if (todayLabel) {
      todayLabel.textContent = dateVal ? "Selected Day" : "Today";
  }

  // Trend
  const trendEl = document.getElementById('stat-trend');
  if (trendEl) {
    const t = data.trend_24h;
    trendEl.textContent = (t >= 0 ? '↑' : '↓') + ' ' + Math.abs(t) + '% vs 24h ago';
    trendEl.className = 'stat-trend ' + (t >= 0 ? 'up' : 'down');
  }

  // Threat level
  const threatEl = document.getElementById('threat-level');
  if (threatEl) {
    const total = data.total_alerts, crit = data.critical;
    if (crit > 5) { threatEl.className = 'threat-badge threat-critical'; threatEl.innerHTML = '🔴 Critical'; }
    else if (total > 30) { threatEl.className = 'threat-badge threat-high'; threatEl.innerHTML = '🟠 High'; }
    else if (total > 10) { threatEl.className = 'threat-badge threat-medium'; threatEl.innerHTML = '🟡 Medium'; }
    else { threatEl.className = 'threat-badge threat-low'; threatEl.innerHTML = '🟢 Low'; }
  }

  // Load charts
  if (typeof loadCharts === 'function') loadCharts(dateVal);
  
  // Load recent alerts table with current filters
  const searchInput = document.getElementById('search-input');
  const levelFilter = document.getElementById('level-filter');
  loadRecentAlerts(1, searchInput?.value || '', levelFilter?.value || '', dateVal);
}

// ── Recent Alerts Table ──
async function loadRecentAlerts(page = 1, search = '', level = '', date = '') {
  let url = `api/alerts.php?page=${page}&limit=15`;
  if (search) url += `&search=${encodeURIComponent(search)}`;
  if (level) url += `&level=${encodeURIComponent(level)}`;
  if (date) {
      url += `&from=${encodeURIComponent(date)}&to=${encodeURIComponent(date)}`;
  }

  const data = await api(url);
  if (!data) return;

  const tbody = document.getElementById('alerts-tbody');
  if (!tbody) return;
  tbody.innerHTML = '';

  if (!data.alerts || data.alerts.length === 0) {
    tbody.innerHTML = '<tr><td colspan="7" class="empty-state"><p>No alerts found</p></td></tr>';
    return;
  }

  data.alerts.forEach(a => {
    const levelClass = { CRITICAL: 'badge-critical', ALERT: 'badge-alert', WARNING: 'badge-warning', INFO: 'badge-info' }[a.level] || 'badge-info';
    const time = a.created_at ? new Date(a.created_at).toLocaleString() : '-';
    const tr = document.createElement('tr');
    tr.className = 'clickable';
    tr.onclick = () => showAlertDetail(a);
    tr.innerHTML = `
      <td>${time}</td>
      <td><span class="badge ${levelClass}">${a.level}</span></td>
      <td title="${a.file_path || ''}">${a.file_name || 'N/A'}</td>
      <td>${a.action || 'N/A'}</td>
      <td><code>${a.ip_address || 'N/A'}</code></td>
      <td>${a.username || 'System'}</td>
      <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="${(a.message||'').replace(/"/g,'&quot;')}">${a.message || '-'}</td>`;
    tbody.appendChild(tr);
  });

  // Pagination
  renderPagination(data.pagination, (p) => loadRecentAlerts(p, search, level, date));
}

function renderPagination(pag, callback) {
  const container = document.getElementById('pagination');
  if (!container) return;
  container.innerHTML = '';
  if (pag.pages <= 1) return;

  const prev = document.createElement('button');
  prev.textContent = '← Prev'; prev.disabled = pag.page <= 1;
  prev.onclick = () => callback(pag.page - 1);
  container.appendChild(prev);

  const info = document.createElement('span');
  info.style.cssText = 'color:var(--text-muted);font-size:.85rem';
  info.textContent = `Page ${pag.page} of ${pag.pages}`;
  container.appendChild(info);

  const next = document.createElement('button');
  next.textContent = 'Next →'; next.disabled = pag.page >= pag.pages;
  next.onclick = () => callback(pag.page + 1);
  container.appendChild(next);
}

// ── Alert Detail Modal ──
function showAlertDetail(a) {
  const mc = document.getElementById('modal-content');
  if (!mc) return;
  const time = a.created_at ? new Date(a.created_at).toLocaleString() : '-';
  mc.innerHTML = `
    <div style="display:grid;gap:12px">
      <div><strong style="color:var(--text-muted)">Time</strong><br>${time}</div>
      <div><strong style="color:var(--text-muted)">Level</strong><br><span class="badge badge-${a.level?.toLowerCase() || 'info'}">${a.level}</span></div>
      <div><strong style="color:var(--text-muted)">File</strong><br>${a.file_path || a.file_name || 'N/A'}</div>
      <div><strong style="color:var(--text-muted)">Action</strong><br>${a.action || 'N/A'}</div>
      <div><strong style="color:var(--text-muted)">IP Address</strong><br><code>${a.ip_address || 'N/A'}</code></div>
      <div><strong style="color:var(--text-muted)">Hostname</strong><br>${a.hostname || 'N/A'}</div>
      <div><strong style="color:var(--text-muted)">User</strong><br>${a.username || 'System'}</div>
      <div><strong style="color:var(--text-muted)">Process</strong><br>${a.process_name ? a.process_name + ' (PID: ' + a.pid + ')' : 'N/A'}</div>
      <div><strong style="color:var(--text-muted)">Message</strong><br>${a.message || '-'}</div>
    </div>`;
  openModal('alert-modal');
}

// ── Search & Filter Handlers ──
function setupFilters() {
  const searchInput = document.getElementById('search-input');
  const levelFilter = document.getElementById('level-filter');
  const dateFilter = document.getElementById('date-filter');
  let debounce;

  if (searchInput) {
    searchInput.addEventListener('input', () => {
      clearTimeout(debounce);
      debounce = setTimeout(() => {
        loadRecentAlerts(1, searchInput.value, levelFilter?.value || '', dateFilter?.value || '');
      }, 400);
    });
  }
  if (levelFilter) {
    levelFilter.addEventListener('change', () => {
      loadRecentAlerts(1, searchInput?.value || '', levelFilter.value, dateFilter?.value || '');
    });
  }
  if (dateFilter) {
    dateFilter.addEventListener('change', () => {
      // Re-initialize dashboard to update stats and table based on new date
      initDashboard();
    });
  }
}

// ── Auto Refresh ──
let refreshInterval;
function startAutoRefresh(seconds = 15) {
  refreshInterval = setInterval(() => {
    initDashboard();
  }, seconds * 1000);
}

// ── Init ──
document.addEventListener('DOMContentLoaded', () => {
  if (document.getElementById('dashboard-page')) {
    initDashboard();
    setupFilters();
    startAutoRefresh();
  }
});
