<?php

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .employer-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #fff;
            padding: 60px 0;
            margin-bottom: 40px;
            border-radius: 0 0 var(--radius-xl) var(--radius-xl);
            animation: fadeIn 0.8s var(--ease-out);
        }

        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 40px;
            animation: slideUp 0.8s var(--ease-out) 0.2s both;
        }

        .employer-action-card {
            background: var(--bg-card);
            padding: 35px 25px;
            border-radius: var(--radius-lg);
            text-align: center;
            border: 1px solid var(--border-light);
            transition: var(--transition);
            cursor: pointer;
            box-shadow: var(--shadow-sm);
        }

        .employer-action-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-premium);
            border-color: var(--primary);
        }

        .employer-action-card i {
            font-size: 40px;
            color: var(--primary);
            margin-bottom: 20px;
            display: block;
        }

        .employer-action-card h3 {
            font-size: 18px;
            font-weight: 800;
            color: var(--text-dark);
            margin: 0;
        }

        .employer-action-card p {
            font-size: 13px;
            color: var(--text-light);
            margin-top: 8px;
        }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: -60px;
            padding: 0 24px;
            animation: slideUp 0.8s var(--ease-out) 0.1s both;
        }

        .header-content h1 { font-size: 32px; font-weight: 900; margin-bottom: 10px; }
        .header-content p { font-size: 16px; opacity: 0.9; }
    </style>
</head>
<body>

<header class="navbar">
    <div class="nav-container">
        <h2>JSTACK <span style="font-weight:normal; opacity:0.6; font-size: 16px;">Recruiter</span></h2>
        <nav>
            <a href="../index.html"><i class="fas fa-globe"></i> Marketplace</a>
            <a href="manage-jobs.php"><i class="fas fa-list-check"></i> Manage Jobs</a>
            <a href="applicants.php"><i class="fas fa-users"></i> Applicants</a>
        </nav>
        <div id="nav-actions">
            <span style="font-weight: 600; font-size: 14px; color: var(--text-mid);"><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($user['name']); ?></span>
            <button onclick="logout()" class="btn-sm danger"><i class="fas fa-sign-out-alt"></i></button>
        </div>
    </div>
</header>

<div class="employer-header">
    <div class="container header-content">
        <h1>Recruiting Overview</h1>
        <p>Manage your talent pipeline and track performance efficiently.</p>
    </div>
</div>

<div class="container">
    <div class="stat-grid">
        <div class="stat-card">
            <span class="stat-value" id="s-jobs">...</span>
            <span class="stat-label">Active Job Posts</span>
        </div>
        <div class="stat-card">
            <span class="stat-value" id="s-apps">...</span>
            <span class="stat-label">Total Applicants</span>
        </div>
        <div class="stat-card">
            <span class="stat-value" id="s-acc" style="background: linear-gradient(135deg, var(--secondary) 0%, var(--secondary-dark) 100%); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;">...</span>
            <span class="stat-label">Shortlisted</span>
        </div>
        <div class="stat-card">
            <span class="stat-value" id="s-pend" style="background: linear-gradient(135deg, var(--warning) 0%, #d97706 100%); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;">...</span>
            <span class="stat-label">Pending Review</span>
        </div>
    </div>

    <div class="action-grid">
        <div class="employer-action-card" onclick="location.href='post-job.php'">
            <i class="fas fa-plus-circle"></i>
            <h3>Post a New Job</h3>
            <p>Reach top talent across the platform</p>
        </div>
        <div class="employer-action-card" onclick="location.href='manage-jobs.php'">
            <i class="fas fa-tasks"></i>
            <h3>Manage Jobs</h3>
            <p>Update, close or feature listings</p>
        </div>
        <div class="employer-action-card" onclick="location.href='applicants.php'">
            <i class="fas fa-user-tie"></i>
            <h3>Review Applicants</h3>
            <p>Screen resumes and message candidates</p>
        </div>
        <div class="employer-action-card" onclick="location.href='profile.php'">
            <i class="fas fa-building"></i>
            <h3>Company Profile</h3>
            <p>Enhance your employer branding</p>
        </div>
        <div class="employer-action-card" onclick="location.href='../messages/inbox.php'">
            <i class="fas fa-comments"></i>
            <h3>Direct Messages</h3>
            <p>Communicate with potential hires</p>
        </div>
        <div class="employer-action-card" onclick="location.href='../settings/account.html'">
            <i class="fas fa-shield-alt"></i>
            <h3>Account Security</h3>
            <p>Manage passwords and access</p>
        </div>
    </div>
</div>

<script src="../assets/js/main.js?v=1.2"></script>
<script>
    async function initDashboard() {
        const user = await requireAuth('employer');
        if (!user) return;

        const res = await apiGet(`${API}/dashboard-stats.php`);
        
        if (res && res.success && res.role === 'employer') {
            const s = res.stats;
            document.getElementById('s-jobs').textContent = s.total_jobs || 0;
            document.getElementById('s-apps').textContent = s.total_applications || 0;
            document.getElementById('s-acc').textContent  = s.accepted || 0;
            document.getElementById('s-pend').textContent = s.pending || 0;
        }
    }

    document.addEventListener('DOMContentLoaded', initDashboard);
</script>
</body>
</html>

