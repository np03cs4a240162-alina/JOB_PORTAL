<?php
/**
 * EMPLOYER DASHBOARD - JSTACK Job Portal
 * Handles overview of posted jobs, applicants, and recruiting metrics.
 */
require_once '../config/session.php';
$user = requireRole('employer');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Employer Dashboard - JSTACK</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    .grid-4 { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .action-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; }
    .card-action { 
        background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        text-align: center; cursor: pointer; transition: all 0.3s ease; border: 1px solid #eee;
    }
    .card-action:hover { transform: translateY(-5px); border-color: #0a66c2; box-shadow: 0 8px 24px rgba(10,102,194,0.15); }
    .card-action i { font-size: 32px; display: block; margin-bottom: 15px; }
    .card-action h3 { margin: 0; font-size: 18px; color: #333; }

    .stat-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); text-align: center; }
    .stat-num { font-size: 32px; font-weight: bold; margin-bottom: 5px; }
    .stat-num.blue { color: #0a66c2; }
    .stat-num.green { color: #28a745; }
    .stat-num.orange { color: #fd7e14; }
    .stat-label { font-size: 14px; color: #666; font-weight: 500; }
  </style>
</head>
<body>

<header class="navbar">
  <div style="display:flex; align-items:center; gap:20px;">
    <h2>JSTACK <span style="font-weight:normal; opacity:0.8;">| Employer</span></h2>
  </div>
  <nav id="nav-actions" style="display:flex; align-items:center; gap:20px;">
    <a href="../index.html" style="color:white; text-decoration:none; font-size:14px;">Marketplace</a>
    <span style="color:rgba(255,255,255,0.6);">|</span>
    <span style="color:white; font-size:14px;">👤 <?php echo htmlspecialchars($user['name']); ?></span>
    <button onclick="logout()" class="btn-sm danger">Logout</button>
  </nav>
</header>

<div class="container" style="margin-top: 40px;">
  <div style="margin-bottom:30px;">
    <h1 style="margin:0 0 10px 0;">Recruiting Overview</h1>
    <p style="color:#666; margin:0;">Track your job performance and manage applicants in one place.</p>
  </div>

  <div class="grid-4">
    <div class="stat-card">
      <div class="stat-num blue" id="s-jobs">...</div>
      <div class="stat-label">My Active Jobs</div>
    </div>
    <div class="stat-card">
      <div class="stat-num blue" id="s-apps">...</div>
      <div class="stat-label">Total Applications</div>
    </div>
    <div class="stat-card">
      <div class="stat-num green" id="s-acc">...</div>
      <div class="stat-label">Shortlisted</div>
    </div>
    <div class="stat-card">
        <div class="stat-num orange" id="s-pend">...</div>
        <div class="stat-label">Pending Review</div>
      </div>
  </div>

  <div class="action-grid">
    <div class="card-action" onclick="location.href='post-job.php'">
        <i>➕</i>
        <h3>Post a New Job</h3>
    </div>
    <div class="card-action" onclick="location.href='manage-jobs.php'">
        <i>📋</i>
        <h3>Manage My Jobs</h3>
    </div>
    <div class="card-action" onclick="location.href='applicants.php'">
        <i>👥</i>
        <h3>Review Applicants</h3>
    </div>
    <div class="card-action" onclick="location.href='profile.php'">
        <i>🏢</i>
        <h3>Company Profile</h3>
    </div>
    <div class="card-action" onclick="location.href='../messages/inbox.php'">
        <i>💬</i>
        <h3>Direct Messages</h3>
    </div>
    <div class="card-action" onclick="location.href='../settings/account.html'">
        <i>⚙️</i>
        <h3>Account Settings</h3>
    </div>
  </div>
</div>

<footer style="margin-top:60px; padding:30px; text-align:center; border-top:1px solid #eee; color:#999; font-size:14px;">
    © 2026 JSTACK Recruiting Platform
</footer>

<script src="../assets/js/main.js?v=1.2"></script>
<script>
  async function initDashboard() {
    // 1. Verify access
    const user = await requireAuth('employer');
    if (!user) return;

    // 2. Fetch Personalized Stats
    // Path: /jobportalsystem/api/dashboard-stats.php
    const res = await apiGet(`${API}/dashboard-stats.php`);
    
    if (res && res.success && res.role === 'employer') {
      const s = res.stats;
      document.getElementById('s-jobs').textContent = s.total_jobs || 0;
      document.getElementById('s-apps').textContent = s.total_applications || 0;
      document.getElementById('s-acc').textContent  = s.accepted || 0;
      document.getElementById('s-pend').textContent = s.pending || 0;
    } else {
      console.error("Failed to load employer stats:", res?.error);
    }
  }

  document.addEventListener('DOMContentLoaded', initDashboard);
</script>
</body>
</html>