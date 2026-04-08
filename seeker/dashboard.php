<?php
require_once '../config/session.php';
$user = requireRole('seeker'); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seeker Dashboard - JSTACK</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .dashboard-layout {
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 30px;
            margin-top: 30px;
        }

        .profile-side {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-light);
            height: fit-content;
            position: sticky;
            top: 110px;
            animation: slideUp 0.6s var(--ease-out);
        }

        .side-header {
            height: 100px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            position: relative;
        }

        .side-info {
            padding: 0 24px 30px;
            text-align: center;
            margin-top: -45px;
        }

        .side-avatar {
            width: 90px;
            height: 90px;
            background: #fff;
            border-radius: 50%;
            margin: 0 auto 15px;
            border: 5px solid #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: 800;
            color: var(--primary);
            box-shadow: var(--shadow-md);
        }

        .side-name { font-size: 20px; font-weight: 800; margin-bottom: 5px; color: var(--text-dark); }
        .side-email { font-size: 13px; color: var(--text-light); margin-bottom: 20px; word-break: break-all; }

        .side-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            border-top: 1px solid var(--border-light);
            background: var(--bg-soft);
        }

        .stat-box {
            padding: 20px 10px;
            text-align: center;
            border-right: 1px solid var(--border-light);
        }
        .stat-box:last-child { border-right: none; }
        .stat-box .count { font-size: 22px; font-weight: 900; color: var(--primary); display: block; }
        .stat-box .label { font-size: 11px; font-weight: 700; color: var(--text-light); text-transform: uppercase; }

        .dashboard-content { display: flex; flex-direction: column; gap: 30px; }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            animation: slideUp 0.6s var(--ease-out) 0.1s both;
        }

        .action-card {
            background: var(--bg-card);
            padding: 24px;
            border-radius: var(--radius-lg);
            text-align: center;
            border: 1px solid var(--border-light);
            transition: var(--transition);
        }
        .action-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-lg); border-color: var(--primary); }
        .action-card i { font-size: 28px; color: var(--primary); margin-bottom: 12px; display: block; }
        .action-card strong { font-size: 15px; color: var(--text-dark); display: block; margin-bottom: 4px; }
        .action-card span { font-size: 12px; color: var(--text-light); }

        .feed-card {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-light);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            animation: slideUp 0.6s var(--ease-out) 0.2s both;
        }

        .feed-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .feed-header h3 { font-size: 16px; font-weight: 700; color: var(--text-dark); margin: 0; }
        .feed-header a { font-size: 13px; font-weight: 600; color: var(--primary); }

        .feed-list { padding: 0; margin: 0; list-style: none; }
        .feed-item {
            padding: 18px 24px;
            border-bottom: 1px solid var(--border-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background 0.2s;
        }
        .feed-item:last-child { border-bottom: none; }
        .feed-item:hover { background: var(--bg-soft); }

        .item-info h4 { font-size: 15px; font-weight: 700; color: var(--primary); margin: 0 0 4px 0; }
        .item-info p { font-size: 13px; color: var(--text-mid); margin: 0; }
        
        .status-pill {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .status-rejected { background: #f3f4f6; color: #4b5563; }

        @media (max-width: 992px) {
            .dashboard-layout { grid-template-columns: 1fr; }
            .profile-side { position: static; }
        }
    </style>
</head>
<body>

<header class="navbar">
    <div class="nav-container">
        <h2>JSTACK</h2>
        <nav>
            <a href="../index.html"><i class="fas fa-search"></i> Jobs</a>
            <a href="applied-jobs.php"><i class="fas fa-briefcase"></i> My Apps</a>
            <a href="saved-jobs.php"><i class="fas fa-bookmark"></i> Saved</a>
            <a href="../messages/inbox.php"><i class="fas fa-envelope"></i> Messages</a>
        </nav>
        <div id="nav-actions">
            <a href="profile.php"><i class="fas fa-user-circle"></i> Profile</a>
            <button onclick="logout()" class="btn-sm danger"><i class="fas fa-sign-out-alt"></i></button>
        </div>
    </div>
</header>

<div class="container">
    <div class="dashboard-layout">
        <aside class="profile-side">
            <div class="side-header"></div>
            <div class="side-info">
                <div class="side-avatar" id="avatar-initials">?</div>
                <h3 class="side-name" id="user-name">Loading...</h3>
                <p class="side-email" id="user-email">...</p>
                <a href="profile.php" class="btn-outline full-width">Update Profile</a>
            </div>
            <div class="side-stats">
                <div class="stat-box">
                    <span class="count" id="count-applied">0</span>
                    <span class="label">Applied</span>
                </div>
                <div class="stat-box">
                    <span class="count" id="count-saved">0</span>
                    <span class="label">Saved</span>
                </div>
            </div>
        </aside>

        <main class="dashboard-content">
            <div class="quick-actions">
                <a href="../index.html" class="action-card">
                    <i class="fas fa-magnifying-glass"></i>
                    <strong>Search Jobs</strong>
                    <span>Find your dream role</span>
                </a>
                <a href="resume-manager.php" class="action-card">
                    <i class="fas fa-file-invoice"></i>
                    <strong>My Resumes</strong>
                    <span>Manage documents</span>
                </a>
                <a href="profile.php" class="action-card">
                    <i class="fas fa-cog"></i>
                    <strong>Settings</strong>
                    <span>Account & security</span>
                </a>
            </div>

            <section class="feed-card">
                <div class="feed-header">
                    <h3><i class="fas fa-history"></i> Recent Applications</h3>
                    <a href="applied-jobs.php">View All</a>
                </div>
                <div id="application-list" class="feed-list">
                    <div style="padding:40px; text-align:center; color:var(--text-light);">Loading...</div>
                </div>
            </section>

            <section class="feed-card">
                <div class="feed-header">
                    <h3><i class="fas fa-star text-warning"></i> Recommended Jobs</h3>
                    <a href="../index.html">Explore More</a>
                </div>
                <div id="job-recommendations" class="feed-list">
                    <div style="padding:40px; text-align:center; color:var(--text-light);">Loading...</div>
                </div>
            </section>
        </main>
    </div>
</div>

<script src="../assets/js/main.js?v=1.2"></script>
<script>
    async function initDashboard() {
        const user = await requireAuth('seeker');
        if (!user) return;


        const appRes = await apiGet(`${API}/applications.php`);
        if (appRes && appRes.success) {
            renderApplications(appRes.data);
            document.getElementById('count-applied').textContent = appRes.data.length;
        }


        const jobsRes = await apiGet(`${API}/jobs.php`);
        if (jobsRes && jobsRes.success) {
            renderRecommendations(jobsRes.data);
        }
    }

    function renderApplications(apps) {
        const list = document.getElementById('application-list');
        if (!apps || apps.length === 0) {
            list.innerHTML = `<div style="padding:40px; text-align:center;">
                <p style="color:var(--text-light); margin-bottom:15px;">No applications yet.</p>
                <a href="../index.html" class="btn-primary">Find Jobs</a>
            </div>`;
            return;
        }

        list.innerHTML = apps.slice(0, 3).map(app => `
            <div class="feed-item">
                <div class="item-info">
                    <h4>${escHtml(app.job_title)}</h4>
                    <p>${escHtml(app.company)} • Applied on ${new Date(app.applied_at).toLocaleDateString()}</p>
                </div>
                <span class="status-pill status-${app.status}">${app.status}</span>
            </div>
        `).join('');
    }

    function renderRecommendations(jobs) {
        const list = document.getElementById('job-recommendations');
        if (!jobs || jobs.length === 0) {
            list.innerHTML = `<div style="padding:40px; text-align:center; color:var(--text-light);">No jobs available right now.</div>`;
            return;
        }

        list.innerHTML = jobs.slice(0, 3).map(job => `
            <div class="feed-item" onclick="location.href='../jobs/detail.html?id=${job.id}'" style="cursor:pointer;">
                <div class="item-info">
                    <h4>${escHtml(job.title)}</h4>
                    <p><i class="fas fa-building"></i> ${escHtml(job.employer_name || 'JSTACK')} • <i class="fas fa-map-marker-alt"></i> ${escHtml(job.location)}</p>
                </div>
                <span style="color:var(--primary); font-size:18px;"><i class="fas fa-chevron-right"></i></span>
            </div>
        `).join('');
    }

    document.addEventListener('DOMContentLoaded', initDashboard);
</script>

</body>
</html>>

</body>
</html>



