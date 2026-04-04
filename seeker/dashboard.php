<?php
/**
 * SEEKER DASHBOARD - JSTACK Job Portal
 * Handles overview of applied jobs, saved jobs, and profile status.
 */
require_once '../config/session.php';
$user = requireRole('seeker'); // Ensures only seekers can access this page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seeker Dashboard - JSTACK</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root {
            --primary: #0a66c2;
            --bg-light: #f3f2ef;
            --text-main: #333;
            --text-muted: #666;
            --white: #ffffff;
            --shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        body {
            background-color: var(--bg-light);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            color: var(--text-main);
        }

        .dashboard-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 25px;
        }

        /* Sidebar Profile Card */
        .sidebar-card {
            background: var(--white);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow);
            position: sticky;
            top: 20px;
        }

        .profile-header-bg {
            height: 60px;
            background: linear-gradient(135deg, #0a66c2 0%, #004182 100%);
        }

        .profile-content {
            padding: 20px;
            margin-top: -30px;
            text-align: center;
        }

        .p-avatar {
            width: 70px;
            height: 70px;
            background: #eee;
            border-radius: 50%;
            margin: 0 auto 10px;
            border: 4px solid var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: var(--primary);
            font-weight: bold;
        }

        .p-name { font-size: 18px; font-weight: 700; margin-bottom: 4px; }
        .p-role { font-size: 13px; color: var(--text-muted); margin-bottom: 15px; }

        .stat-list {
            border-top: 1px solid #f0f0f0;
            padding: 15px 20px;
            text-align: left;
        }

        .stat-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 13px;
            color: var(--text-muted);
        }

        .stat-count { color: var(--primary); font-weight: bold; }

        /* Main Feed */
        .main-feed { display: flex; flex-direction: column; gap: 20px; }

        .quick-nav {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }

        .nav-card {
            background: var(--white);
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            text-decoration: none;
            color: var(--text-main);
            box-shadow: var(--shadow);
            transition: transform 0.2s;
        }

        .nav-card:hover { transform: translateY(-3px); border-bottom: 2px solid var(--primary); }
        .nav-card i { font-size: 24px; display: block; margin-bottom: 8px; color: var(--primary); }

        .feed-section {
            background: var(--white);
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--shadow);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 12px;
        }

        .section-title { font-size: 18px; font-weight: 700; color: var(--primary); }

        .app-list { display: flex; flex-direction: column; gap: 15px; }

        .app-item {
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background 0.2s;
        }

        .app-item:hover { background-color: #f9f9f9; }

        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-accepted { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }

        .empty-state { text-align: center; padding: 40px 0; color: var(--text-muted); }
    </style>
</head>
<body>

<header class="navbar">
    <div class="nav-container" style="max-width:1200px; margin:0 auto; display:flex; justify-content:space-between; align-items:center; width:100%;">
        <div style="display:flex; align-items:center; gap:20px;">
            <h2 style="margin:0;">JSTACK</h2>
            <nav style="display:flex; gap:15px;">
                <a href="../index.html" style="color:white; text-decoration:none; font-size:14px;">Find Jobs</a>
                <a href="applied-jobs.php" style="color:white; text-decoration:none; font-size:14px;">My Applications</a>
                <a href="saved-jobs.php" style="color:white; text-decoration:none; font-size:14px;">Saved</a>
            </nav>
        </div>
        <div style="display:flex; align-items:center; gap:15px;">
            <a href="profile.php" style="color:white; text-decoration:none; font-size:14px;">Profile</a>
            <button onclick="logout()" class="btn-sm danger">Logout</button>
        </div>
    </div>
</header>

<div class="dashboard-container">
    <!-- Sidebar -->
    <aside class="sidebar-card">
        <div class="profile-header-bg"></div>
        <div class="profile-content">
            <div class="p-avatar" id="avatar-initials">?</div>
            <div class="p-name" id="user-name">Loading...</div>
            <div class="p-role" id="user-email">...</div>
            
            <a href="profile.php" class="btn-outline" style="width:100%; display:block; padding:8px 0; font-size:13px; text-decoration:none;">Update Profile</a>
        </div>
        
        <div class="stat-list">
            <div class="stat-item">
                <span>Applied Jobs</span>
                <span class="stat-count" id="count-applied">0</span>
            </div>
            <div class="stat-item">
                <span>Saved Jobs</span>
                <span class="stat-count" id="count-saved">0</span>
            </div>
        </div>
    </aside>

    <!-- Main Feed -->
    <main class="main-feed">
        <div class="quick-nav">
            <a href="../index.html" class="nav-card">
                <i>🔍</i>
                <strong>Search Jobs</strong>
            </a>
            <a href="resume-manager.php" class="nav-card">
                <i>📄</i>
                <strong>My Resumes</strong>
            </a>
            <a href="profile.php" class="nav-card">
                <i>⚙️</i>
                <strong>Settings</strong>
            </a>
        </div>

        <section class="feed-section">
            <div class="section-header">
                <span class="section-title">Recent Applications</span>
                <a href="applied-jobs.php" style="font-size:13px; color:var(--primary); text-decoration:none; font-weight:bold;">View All</a>
            </div>
            
            <div id="application-list" class="app-list">
                <div class="empty-state">Loading your applications...</div>
            </div>
        </section>

        <section class="feed-section">
            <div class="section-header">
                <span class="section-title">Recommended for You</span>
            </div>
            <div id="job-recommendations" class="app-list">
                <div class="empty-state">Loading job suggestions...</div>
            </div>
        </section>
    </main>
</div>

<script src="../assets/js/main.js?v=1.2"></script>
<script>
    async function initDashboard() {
        const user = await requireAuth('seeker');
        if (!user) return;

        // Set User Info
        document.getElementById('user-name').textContent = user.name;
        document.getElementById('user-email').textContent = user.email;
        document.getElementById('avatar-initials').textContent = user.name.charAt(0).toUpperCase();

        // Fetch Applications
        const appRes = await apiGet(`${API}/applications.php`);
        console.log("Apps:", appRes);
        if (appRes && appRes.success) {
            renderApplications(appRes.data);
            document.getElementById('count-applied').textContent = appRes.data.length;
        }

        // Fetch Saved Jobs (for count)
        const savedRes = await apiGet(`${API}/saved.php`);
        const savedData = (savedRes && Array.isArray(savedRes.data)) ? savedRes.data : (Array.isArray(savedRes) ? savedRes : []);
        document.getElementById('count-saved').textContent = savedData.length;

        // Fetch Recommended (Just active jobs for now)
        const jobsRes = await apiGet(`${API}/jobs.php`);
        if (jobsRes && jobsRes.success) {
            renderRecommendations(jobsRes.data);
        }
    }

    function renderApplications(apps) {
        const list = document.getElementById('application-list');
        if (!apps || apps.length === 0) {
            list.innerHTML = `<div class="empty-state">You haven't applied for any jobs yet. <br><br> <a href="../index.html" class="btn-primary" style="display:inline-block; text-decoration:none;">Find Jobs</a></div>`;
            return;
        }

        list.innerHTML = apps.slice(0, 3).map(app => `
            <div class="app-item">
                <div>
                    <strong style="font-size:15px; color:var(--primary);">${app.job_title}</strong><br>
                    <small style="color:#666;">${app.company} • Applied on ${new Date(app.applied_at).toLocaleDateString()}</small>
                </div>
                <span class="status-badge status-${app.status}">${app.status}</span>
            </div>
        `).join('');
    }

    function renderRecommendations(jobs) {
        const list = document.getElementById('job-recommendations');
        if (!jobs || jobs.length === 0) {
            list.innerHTML = `<div class="empty-state">No jobs found.</div>`;
            return;
        }

        list.innerHTML = jobs.slice(0, 3).map(job => `
            <div class="app-item" onclick="location.href='../jobs/view.html?id=${job.id}'" style="cursor:pointer;">
                <div>
                    <strong>${job.title}</strong><br>
                    <small style="color:#666;">${job.company} • ${job.location} • ${job.salary || 'Salary N/A'}</small>
                </div>
                <span style="color:var(--primary); font-size:20px;">→</span>
            </div>
        `).join('');
    }

    document.addEventListener('DOMContentLoaded', initDashboard);
</script>

</body>
</html>
