<?php

require_once '../config/session.php';
$user = requireRole('admin'); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - JSTACK</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .admin-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-top: 30px;
            animation: slideUp 0.8s var(--ease-out);
        }

        .admin-card {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-light);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            margin-bottom: 20px;
        }

        .admin-card-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--bg-soft);
        }

        .admin-card-header h3 { font-size: 16px; font-weight: 800; color: var(--text-dark); margin: 0; }
        .admin-card-header i { color: var(--primary); }

        .admin-list { padding: 0; margin: 0; list-style: none; }
        .admin-list-item {
            padding: 16px 24px;
            border-bottom: 1px solid var(--border-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .admin-list-item:last-child { border-bottom: none; }

        .user-info h4 { font-size: 14px; font-weight: 700; color: var(--text-dark); margin: 0; }
        .user-info p { font-size: 12px; color: var(--text-light); margin: 0; text-transform: capitalize; }

        .date-badge {
            background: var(--primary-light);
            color: var(--primary);
            font-size: 11px;
            font-weight: 800;
            padding: 4px 10px;
            border-radius: 6px;
        }

        .quick-nav-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
            padding: 24px;
        }

        .admin-nav-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 18px;
            background: #fff;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            color: var(--text-dark);
            font-size: 14px;
            font-weight: 600;
            transition: var(--transition);
            text-decoration: none;
        }

        .admin-nav-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: var(--primary-light);
            transform: translateX(5px);
        }

        .admin-nav-btn i { width: 20px; text-align: center; }

        @media (max-width: 992px) {
            .admin-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<header class="navbar">
    <div class="nav-container">
        <h2>JSTACK <span style="font-weight:normal; opacity:0.6; font-size: 16px;">SuperAdmin</span></h2>
        <nav>
            <a href="manage-users.php"><i class="fas fa-users-cog"></i> Users</a>
            <a href="manage-jobs.php"><i class="fas fa-tasks"></i> Jobs</a>
            <a href="reports.php"><i class="fas fa-chart-pie"></i> Reports</a>
        </nav>
        <div id="nav-actions">
            <span style="font-weight: 600; font-size: 14px; color: var(--text-mid);"><i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($user['name']); ?></span>
            <button onclick="logout()" class="btn-sm danger"><i class="fas fa-sign-out-alt"></i></button>
        </div>
    </div>
</header>

<div class="container" style="margin-top: 40px;">
    <div style="margin-bottom: 30px;">
        <h1 style="font-size: 28px; font-weight: 900; color: var(--text-dark);">System Overview</h1>
        <p style="color: var(--text-light);">Manage users, oversee listings, and monitor platform health.</p>
    </div>

    
    <div class="dashboard-grid">
        <div class="stat-card">
            <span class="stat-value" id="total-users">...</span>
            <span class="stat-label">Total Registered Users</span>
        </div>
        <div class="stat-card">
            <span class="stat-value" id="active-jobs" style="background: linear-gradient(135deg, var(--secondary) 0%, var(--secondary-dark) 100%); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;">...</span>
            <span class="stat-label">Active Job Listings</span>
        </div>
        <div class="stat-card">
            <span class="stat-value" id="total-apps" style="background: linear-gradient(135deg, var(--warning) 0%, #d97706 100%); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;">...</span>
            <span class="stat-label">Total Applications</span>
        </div>
    </div>

    <div class="admin-grid">
        
        <div class="admin-card">
            <div class="admin-card-header">
                <h3><i class="fas fa-user-plus"></i> Recent User Onboarding</h3>
                <a href="manage-users.php" style="font-size: 12px; font-weight: 700; color: var(--primary);">Manage All</a>
            </div>
            <div id="recent-users-list" class="admin-list">
                <div style="padding:40px; text-align:center; color:var(--text-light);">Loading users...</div>
            </div>
        </div>

        
        <div class="admin-card">
            <div class="admin-card-header">
                <h3><i class="fas fa-bolt"></i> Control Panel</h3>
            </div>
            <div class="quick-nav-grid">
                <a href="manage-users.php" class="admin-nav-btn">
                    <i class="fas fa-users"></i> User Directory
                </a>
                <a href="manage-jobs.php" class="admin-nav-btn">
                    <i class="fas fa-briefcase"></i> Job Moderation
                </a>
                <a href="reports.php" class="admin-nav-btn">
                    <i class="fas fa-file-contract"></i> Audit Reports
                </a>
                <a href="authorization-roles.php" class="admin-nav-btn">
                    <i class="fas fa-user-lock"></i> Role Permissions
                </a>
                <hr style="border:0; border-top:1px solid var(--border-light); margin: 10px 0;">
                <button onclick="logout()" class="admin-nav-btn" style="color: var(--danger); border-color: rgba(239, 68, 68, 0.2);">
                    <i class="fas fa-power-off"></i> Secure Logout
                </button>
            </div>
        </div>
    </div>
</div>

<script src="../assets/js/main.js?v=1.2"></script>
<script>
    async function initDashboard() {
        const user = await requireAuth('admin');
        if (!user) return;

        const res = await apiGet(`${API}/dashboard-stats.php`);
        
        if (res && res.success) {
            document.getElementById('total-users').textContent = res.stats?.total_users || 0;
            document.getElementById('active-jobs').textContent = res.stats?.active_jobs || 0;
            document.getElementById('total-apps').textContent  = res.stats?.total_applications || 0;

            renderRecentUsers(res.recent_users);
        }
    }

    function renderRecentUsers(users) {
        const list = document.getElementById('recent-users-list');
        if(!users || users.length === 0) {
            list.innerHTML = "<div style='padding:40px; text-align:center; color:var(--text-light);'>No recent activity.</div>";
            return;
        }

        list.innerHTML = users.slice(0, 5).map(u => `
            <div class="admin-list-item">
                <div class="user-info">
                    <h4>${escHtml(u.name)}</h4>
                    <p>${escHtml(u.role)} • ${escHtml(u.email)}</p>
                </div>
                <span class="date-badge">${new Date(u.created_at).toLocaleDateString()}</span>
            </div>
        `).join('');
    }

    document.addEventListener('DOMContentLoaded', initDashboard);
</script>
</body>
</html>

