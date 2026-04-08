<?php
require_once '../config/session.php';
$user = requireRole('admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reports - JSTACK Admin</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <script src="../assets/js/chart.js"></script>
  <style>
    .grid-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:16px; margin-bottom:28px; }
    .stat-card { background:white; border-radius:8px; padding:18px; text-align:center; box-shadow:0 2px 8px rgba(0,0,0,.05); }
    .stat-card .val { font-size:2rem; font-weight:700; color:#0a66c2; }
    .stat-card .lbl { font-size:12px; color:#888; margin-top:4px; }
    .panel { background:white; border-radius:8px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,.05); margin-bottom:24px; }
    table { width:100%; border-collapse:collapse; }
    th, td { padding:12px; text-align:left; border-bottom:1px solid #eee; font-size:14px; }
    th { background:#f8f9fa; color:#555; font-size:12px; text-transform:uppercase; }
  </style>
</head>
<body>

<header class="navbar">
  <h2>JSTACK <span style="font-weight:normal;opacity:.8;">| Reports</span></h2>
  <div style="display:flex;gap:12px;align-items:center;">
    <button class="btn-sm btn-outline" onclick="window.print()" style="color:white;border-color:white;">🖨️ Print</button>
    <a href="dashboard.php" style="color:white;text-decoration:none;">← Dashboard</a>
  </div>
</header>

<div class="container" style="margin-top:30px;">
  <h2 class="title">System Performance Overview</h2>
  <p id="report-date" style="text-align:center;color:#aaa;margin-bottom:24px;"></p>

  
  <div class="grid-stats">
    <div class="stat-card"><div class="val" id="t-users">--</div><div class="lbl">Total Users</div></div>
    <div class="stat-card"><div class="val" id="t-jobs">--</div><div class="lbl">Jobs Posted</div></div>
    <div class="stat-card"><div class="val" id="t-apps">--</div><div class="lbl">Applications</div></div>
    <div class="stat-card"><div class="val" id="t-employers">--</div><div class="lbl">Employers</div></div>
    <div class="stat-card"><div class="val" id="t-seekers">--</div><div class="lbl">Seekers</div></div>
    <div class="stat-card"><div class="val" id="t-accepted" style="color:#28a745;">--</div><div class="lbl">Accepted</div></div>
  </div>

  
  <div class="panel">
    <h3 style="margin-top:0;color:#0a66c2;">Top Rated Companies</h3>
    <div style="height:300px;position:relative;">
      <canvas id="companyChart"></canvas>
    </div>
    <p id="no-chart" style="text-align:center;color:#aaa;display:none;">No review data yet.</p>
  </div>

  
  <div class="panel">
    <h3 style="margin-top:0;">Summary Report</h3>
    <table>
      <thead>
        <tr><th>Category</th><th>Count</th><th>Status</th><th>Performance</th></tr>
      </thead>
      <tbody id="report-table">
        <tr><td colspan="4" style="text-align:center;padding:20px;color:#aaa;">Loading...</td></tr>
      </tbody>
    </table>
  </div>
</div>

<script src="../assets/js/main.js?v=1.1"></script>
<script>
  document.addEventListener('DOMContentLoaded', async () => {
    document.getElementById('report-date').textContent =
      'Generated: ' + new Date().toLocaleString();

    const res = await apiGet(`${API}/admindashboard.php`);
    if (!res || !res.success) {
      document.getElementById('report-table').innerHTML =
        '<tr><td colspan="4" style="color:#c62828;text-align:center;">Failed to load report data.</td></tr>';
      return;
    }

    const s = res.stats;

    document.getElementById('t-users').textContent     = s.total_users          ?? '--';
    document.getElementById('t-jobs').textContent      = s.total_jobs           ?? '--';
    document.getElementById('t-apps').textContent      = s.total_applications   ?? '--';
    document.getElementById('t-employers').textContent = s.total_employers      ?? '--';
    document.getElementById('t-seekers').textContent   = s.total_seekers        ?? '--';
    document.getElementById('t-accepted').textContent  = s.accepted_applications ?? '--';

    const rate = s.total_applications > 0
      ? ((s.accepted_applications / s.total_applications) * 100).toFixed(1)
      : '0.0';

    document.getElementById('report-table').innerHTML = `
      <tr><td><strong>User Base</strong></td><td>${s.total_users} accounts</td><td>✅ Active</td><td>${s.total_employers} employers / ${s.total_seekers} seekers</td></tr>
      <tr><td><strong>Job Market</strong></td><td>${s.total_jobs} listings</td><td>📈 Active</td><td>${s.active_jobs ?? 0} live now</td></tr>
      <tr><td><strong>Applications</strong></td><td>${s.total_applications} total</td><td>⚡ Processing</td><td>${s.pending_applications ?? 0} pending</td></tr>
      <tr><td><strong>Success Rate</strong></td><td>${rate}% accepted</td><td>🎯 Target: 15%</td><td>${parseFloat(rate) >= 15 ? '✅ Excellent' : '📈 Growing'}</td></tr>`;

    if (res.company_stats && res.company_stats.length > 0) {
      renderChart(res.company_stats);
    } else {
      document.getElementById('no-chart').style.display = 'block';
    }
  });

  function renderChart(data) {
    const ctx = document.getElementById('companyChart').getContext('2d');
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: data.map(d => d.company),
        datasets: [{
          label: 'Avg Rating (1-5)',
          data: data.map(d => parseFloat(d.avg_rating).toFixed(1)),
          backgroundColor: '#0a66c2',
          borderRadius: 5,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          y: { beginAtZero: true, max: 5, ticks: { stepSize: 1 } }
        }
      }
    });
  }
</script>
</body>
</html>

