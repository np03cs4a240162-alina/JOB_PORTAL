<?php
require_once '../config/session.php';
require_once '../config/db.php';
$user = requireRole('admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - JSTACK</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    .grid-3 { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .stat-link { text-decoration: none; color: inherit; display: block; transition: transform 0.2s; }
    .stat-link:hover { transform: translateY(-4px); }
    .dashboard-layout { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
    .panel { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.06); }
    .recent-item { padding: 12px 0; border-bottom: 1px solid #f0f0f0; display: flex; justify-content: space-between; align-items: center; }
    .recent-item:last-child { border-bottom: none; }
    .badge { background: #e7f3ff; color: #0a66c2; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; }
    .stat-value.blue  { color: #0a66c2; font-size: 2rem; font-weight: 700; }
    .stat-value.green { color: #28a745; font-size: 2rem; font-weight: 700; }
    .stat-value.orange{ color: #fd7e14; font-size: 2rem; font-weight: 700; }
    .stat-label { color: #888; font-size: 13px; margin-top: 4px; }
    .quick-btn { width: 100%; margin-bottom: 10px; text-align: left; padding: 10px 14px; border-radius: 6px; border: 1px solid #e0e0e0; background: #fff; cursor: pointer; font-size: 14px; }
    .quick-btn:hover { background: #f5f9ff; border-color: #0a66c2; }
    .quick-btn.danger { border-color: #ffcdd2; color: #c62828; }
    .quick-btn.danger:hover { background: #fff5f5; }
    @media(max-width:768px){ .dashboard-layout{ grid-template-columns:1fr; } }
  </style>
</head>
<body>

<header class="navbar">
  <h2>JSTACK <span style="font-weight:normal;opacity:.8;">| Admin</span></h2>
  <nav>
    <a href="reports.php" style="color:white;margin-right:16px;text-decoration:none;">Reports</a>
    <a href="manage-users.php" style="color:white;margin-right:16px;text-decoration:none;">Users</a>
    <button onclick="handleLogout()" class="btn-sm" style="background:#c62828;color:white;border:none;padding:6px 14px;border-radius:5px;cursor:pointer;">Logout</button>
  </nav>
</header>

<div class="container" style="margin-top:30px;">
  <h1 class="title">Welcome, <?php echo htmlspecialchars($user['name']); ?> 👋</h1>

  
  <div class="grid-3">
    <a href="manage-users.php" class="stat-link">
      <div class="card">
        <p class="stat-value blue" id="total-users">...</p>
        <p class="stat-label">Total Users</p>
      </div>
    </a>
    <a href="manage-jobs.php" class="stat-link">
      <div class="card">
        <p class="stat-value green" id="active-jobs">...</p>
        <p class="stat-label">Active Jobs</p>
      </div>
    </a>
    <a href="applications.php" class="stat-link">
      <div class="card">
        <p class="stat-value orange" id="total-apps">...</p>
        <p class="stat-label">Applications</p>
      </div>
    </a>
  </div>

  <div class="dashboard-layout">

    
    <div class="panel">
      <h3 style="margin-top:0;color:#0a66c2;border-bottom:2px solid #f4f4f4;padding-bottom:10px;">
        Recent Users
      </h3>
      <div id="recent-users-list">
        <p style="text-align:center;color:#aaa;">Loading...</p>
      </div>
      <a href="manage-users.php" style="display:block;margin-top:15px;font-size:13px;font-weight:bold;color:#0a66c2;text-decoration:none;">
        View All Users →
      </a>
    </div>

    
    <div class="panel">
      <h3 style="margin-top:0;">Quick Actions</h3>
      <button class="quick-btn" onclick="location.href='manage-jobs.php'">📋 Manage Job Listings</button>
      <button class="quick-btn" onclick="location.href='applications.php'">📩 Review Applications</button>
      <button class="quick-btn" onclick="location.href='manage-users.php'">👥 Manage Users</button>
      <button class="quick-btn" onclick="location.href='reports.php'">📊 System Reports</button>
      <hr style="border:0;border-top:1px solid #eee;margin:12px 0;">
      <button class="quick-btn danger" onclick="handleLogout()">🔒 Secure Logout</button>
    </div>

  </div>
</div>

<script src="../assets/js/main.js?v=1.2"></script>
<script>
  const esc = s => String(s).replace(/[&<>"']/g, m =>
    ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));

  async function initDashboard() {

    const res = await apiGet(`${API}/dashboard-stats.php`);

    if (!res || !res.success) {
      console.error('Dashboard load failed:', res?.error);
      return;
    }

    const s = res.stats;
    document.getElementById('total-users').textContent = s.total_users  ?? 0;
    document.getElementById('active-jobs').textContent = s.active_jobs  ?? 0;
    document.getElementById('total-apps').textContent  = s.total_applications ?? 0;

    const list = document.getElementById('recent-users-list');
    if (res.recent_users && res.recent_users.length > 0) {
      list.innerHTML = res.recent_users.map(u => `
        <div class="recent-item">
          <span>
            <strong>${esc(u.name)}</strong><br>
            <small style="color:#888;text-transform:capitalize;">${esc(u.role)}</small>
          </span>
          <span class="badge">${new Date(u.created_at).toLocaleDateString()}</span>
        </div>`).join('');
    } else {
      list.innerHTML = "<p style='color:#aaa;text-align:center;padding:20px;'>No users yet.</p>";
    }
  }

  document.addEventListener('DOMContentLoaded', initDashboard);
</script>
</body>
</html>

