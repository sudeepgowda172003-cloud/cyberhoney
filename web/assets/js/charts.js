/**
 * HoneyGuard v2.0 — Chart Configurations
 */

const chartColors = {
  amber: '#f59e0b', orange: '#f97316', red: '#ef4444', rose: '#f43f5e',
  blue: '#3b82f6', cyan: '#06b6d4', green: '#22c55e', purple: '#a855f7',
  teal: '#14b8a6', indigo: '#6366f1',
  palette: ['#f59e0b','#ef4444','#3b82f6','#22c55e','#a855f7','#06b6d4','#f97316','#6366f1','#14b8a6','#f43f5e']
};

const chartDefaults = {
  responsive: true, maintainAspectRatio: false,
  plugins: { legend: { labels: { color: '#94a3b8', font: { family: "'Inter',sans-serif", size: 11 } } } },
  scales: {
    x: { ticks: { color: '#64748b', font: { size: 10 } }, grid: { color: 'rgba(255,255,255,.04)' } },
    y: { ticks: { color: '#64748b', font: { size: 10 } }, grid: { color: 'rgba(255,255,255,.04)' } }
  }
};

let charts = {};

async function loadCharts(dateVal = '') {
  let dateQuery = dateVal ? `&date=${encodeURIComponent(dateVal)}` : '';

  // Timeline
  const timeline = await api(`api/stats.php?type=timeline&period=24h${dateQuery}`);
  if (timeline?.timeline) renderTimelineChart(timeline.timeline);

  // Top files
  const files = await api(`api/stats.php?type=top_files&limit=5${dateQuery}`);
  if (files?.top_files) renderTopFilesChart(files.top_files);

  // Actions breakdown
  const actions = await api(`api/stats.php?type=actions${dateQuery}`);
  if (actions?.actions) renderActionsChart(actions.actions);

  // Levels breakdown
  const levels = await api(`api/stats.php?type=levels${dateQuery}`);
  if (levels?.levels) renderLevelsChart(levels.levels);

  // Security score
  const overview = await api(`api/stats.php?type=overview${dateQuery}`);
  if (overview) renderScoreGauge(overview.security_score);
}

function renderTimelineChart(data) {
  const ctx = document.getElementById('chart-timeline');
  if (!ctx) return;
  if (charts.timeline) charts.timeline.destroy();
  charts.timeline = new Chart(ctx, {
    type: 'line',
    data: {
      labels: data.map(d => d.label),
      datasets: [{
        label: 'Alerts', data: data.map(d => d.count),
        borderColor: chartColors.cyan, backgroundColor: 'rgba(6,182,212,.1)',
        fill: true, tension: .4, pointRadius: 3, pointBackgroundColor: chartColors.cyan,
        borderWidth: 2
      }]
    },
    options: { ...chartDefaults, plugins: { ...chartDefaults.plugins, legend: { display: false } } }
  });
}

function renderTopFilesChart(data) {
  const ctx = document.getElementById('chart-files');
  if (!ctx) return;
  if (charts.files) charts.files.destroy();
  charts.files = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: data.map(d => d.label?.split('/').pop() || d.label),
      datasets: [{
        label: 'Hits', data: data.map(d => d.count),
        backgroundColor: chartColors.palette.slice(0, data.length),
        borderRadius: 6, borderSkipped: false
      }]
    },
    options: { ...chartDefaults, indexAxis: 'y', plugins: { ...chartDefaults.plugins, legend: { display: false } } }
  });
}

function renderActionsChart(data) {
  const ctx = document.getElementById('chart-actions');
  if (!ctx) return;
  if (charts.actions) charts.actions.destroy();
  charts.actions = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: data.map(d => d.label),
      datasets: [{ data: data.map(d => d.count), backgroundColor: chartColors.palette.slice(0, data.length), borderWidth: 0 }]
    },
    options: { responsive: true, maintainAspectRatio: false, cutout: '70%',
      plugins: { legend: { position: 'bottom', labels: { color: '#94a3b8', padding: 12, font: { size: 11 } } } } }
  });
}

function renderLevelsChart(data) {
  const ctx = document.getElementById('chart-levels');
  if (!ctx) return;
  if (charts.levels) charts.levels.destroy();
  const colorMap = { CRITICAL: chartColors.red, ALERT: chartColors.rose, WARNING: chartColors.amber, INFO: chartColors.blue };
  charts.levels = new Chart(ctx, {
    type: 'polarArea',
    data: {
      labels: data.map(d => d.label),
      datasets: [{ data: data.map(d => d.count), backgroundColor: data.map(d => colorMap[d.label] || chartColors.purple + '80'), borderWidth: 0 }]
    },
    options: { responsive: true, maintainAspectRatio: false,
      scales: { r: { ticks: { display: false }, grid: { color: 'rgba(255,255,255,.05)' } } },
      plugins: { legend: { position: 'bottom', labels: { color: '#94a3b8', padding: 12, font: { size: 11 } } } } }
  });
}

function renderScoreGauge(score) {
  const ctx = document.getElementById('chart-score');
  if (!ctx) return;
  if (charts.score) charts.score.destroy();
  const color = score >= 70 ? chartColors.green : score >= 40 ? chartColors.amber : chartColors.red;
  charts.score = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: ['Score', ''],
      datasets: [{ data: [score, 100 - score], backgroundColor: [color, 'rgba(255,255,255,.04)'], borderWidth: 0 }]
    },
    options: { responsive: true, maintainAspectRatio: false, cutout: '82%', rotation: -90, circumference: 180,
      plugins: { legend: { display: false }, tooltip: { enabled: false } } }
  });
  // Update score text
  const scoreEl = document.getElementById('score-number');
  if (scoreEl) animateCounter(scoreEl, score);
}
