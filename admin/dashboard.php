<?php
// ── SESSION GUARD ──
require_once '../config/session.php';
// This assumes your requireRole function returns user data or redirects on failure
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
    .grid-3 { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .stat-link { text-decoration: none; color: inherit; transition: transform 0.2s; display: block; }
    .stat-link:hover { transform: translateY(-5px); }
    .dashboard-layout { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
    .chart-container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .recent-list { list-style: none; padding: 0; }
    .recent-item { padding: 12px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
    .quick-actions button { width: 100%; margin-bottom: 10px; text-align: left; display: flex; align-items: center; gap: 10px; }
    .badge { background: #e7f3ff; color: #0a66c2; padding: 4px 8px; border-radius: 4px; font-weight: bold; }
    
    /* Stats Colors */
    .stat-value.blue { color: #0a66c2; }
    .stat-value.green { color: #28a745; }
    .stat-value.orange { color: #fd7e14; }
  </style>
</head>
<body>

<header class="navbar">
  <h2>JSTACK <span style="font-weight: normal; opacity: 0.8;">| Admin</span></h2>
  <nav id="nav-actions">
    <a href="reports.php">Reports</a>
    <a href="manage-users.php">Users</a>
    <button onclick="logout()" class="btn-sm danger">Logout</button>
  </nav>
</header>

<div class="container" style="margin-top: 30px;">
  <h1 class="title">Welcome, <?php echo htmlspecialchars($user['name']); ?></h1>

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
    
    <div class="chart-container">
      <h3 style="margin-top:0; color:#0a66c2; border-bottom: 2px solid #f4f4f4; padding-bottom: 10px;">Newest Users</h3>
      <div id="recent-users-list" class="recent-list">
         <p style="text-align:center; color:#888;">Loading users...</p>
      </div>
      <a href="manage-users.php" style="display:block; margin-top:15px; font-size:13px; font-weight: bold; color:#0a66c2; text-decoration:none;">View All Users →</a>
    </div>

    <div class="chart-container">
      <h3 style="margin-top:0;">Quick Actions</h3>
      <div class="quick-actions">
        <button class="btn-sm" onclick="location.href='manage-jobs.php'">📋 Manage Listings</button>
        <button class="btn-sm" onclick="location.href='applications.php'">📩 Review Applications</button>
        <button class="btn-sm btn-outline" onclick="location.href='reports.php'">📊 System Reports</button>
        <hr style="border: 0; border-top: 1px solid #eee; margin: 15px 0;">
        <button class="btn-sm danger" onclick="logout()">🔒 Secure Logout</button>
      </div>
    </div>

  </div>
</div>

<script src="../assets/js/main.js?v=1.2"></script>
<script>
  // Simple helper to prevent XSS in JS-rendered strings
  const esc = (str) => String(str).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[m]));

  async function initDashboard() {
    // 1. Ensure user is admin
    const user = await requireAuth('admin');
    if (!user) return;

    // 2. Fetch Stats from API (Ensure path matches your folder structure)
    // apiGet handles the BASE path automatically
    const res = await apiGet(`${API}/dashboard-stats.php`);
    
    if (!res || !res.success) {
        console.error("Dashboard data load failed:", res?.error);
        return;
    }

    // 2. Update UI Counters
    document.getElementById('total-users').textContent = res.stats?.total_users || 0;
    document.getElementById('active-jobs').textContent = res.stats?.active_jobs || 0;
    document.getElementById('total-apps').textContent  = res.stats?.total_applications || 0;

    // 3. Render Recent Users
    const userList = document.getElementById('recent-users-list');
    if(res.recent_users && res.recent_users.length > 0) {
        userList.innerHTML = res.recent_users.map(u => `
          <div class="recent-item">
            <span>
              <strong>${esc(u.name)}</strong><br>
              <small style="color: #666; text-transform: capitalize;">${esc(u.role)}</small>
            </span>
            <span class="badge" style="font-size: 10px;">${new Date(u.created_at).toLocaleDateString()}</span>
          </div>
        `).join('');
    } else {
        userList.innerHTML = "<p style='color:#888; padding: 20px; text-align:center;'>No recent users found.</p>";
    }
  }

  // Initialize on load
  document.addEventListener('DOMContentLoaded', initDashboard);
</script>
</body>
</html>