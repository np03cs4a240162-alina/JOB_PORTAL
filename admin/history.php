<?php
require_once '../config/session.php';
require_once '../config/db.php';

// Only allow admins to view the audit log
$user = requireLogin();
if ($user['role'] !== 'admin') {
    header('Location: ../index.html');
    exit;
}

// Fetch the latest 200 activity log entries with user name for display
$db = getDB();
$stmt = $db->prepare(
    "SELECT al.id, al.created_at AS timestamp, al.user_id, al.user_name, al.role, al.action, al.details " .
    "FROM activity_logs al " .
    "ORDER BY al.created_at DESC LIMIT 200"
);
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Secure administrative pipelines audit history log.">
    <title>Global Audit History | SmartJob Nepal</title>
    <link rel="stylesheet" href="../assets/css/style.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <style>
        body {
            background-color: var(--bg-deep);
            color: var(--text-main);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
        }
        .dashboard-layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 40px;
            padding-top: 120px;
            padding-bottom: 60px;
        }
        @media (max-width: 992px) {
            .dashboard-layout {
                grid-template-columns: 1fr;
            }
        }
        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }
        .profile-side-card {
            background: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 32px;
            text-align: center;
            box-shadow: var(--shadow-sm);
        }
        .avatar-container {
            width: 96px;
            height: 96px;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: 800;
            background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);
            color: white;
            box-shadow: 0 8px 24px rgba(239, 68, 68, 0.2);
            overflow: hidden;
        }
        .side-nav {
            background: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .side-nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            border-radius: var(--radius);
            font-size: 14px;
            font-weight: 600;
            color: var(--text-dim);
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .side-nav-link:hover, .side-nav-link.active {
            background: rgba(244, 124, 72, 0.08);
            color: var(--primary);
        }
        .dashboard-panel {
            background: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 32px;
            box-shadow: var(--shadow-sm);
        }
        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 16px;
        }
        .panel-header h3 {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -0.5px;
            color: var(--text-main);
        }
        .search-bar-grid {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-bottom: 32px;
        }
        .filter-tabs {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .filter-tab {
            padding: 10px 24px;
            border-radius: 100px;
            border: 1px solid var(--border);
            background: var(--bg-surface);
            color: var(--text-dim);
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            gap: 8px;
            user-select: none;
        }
        .filter-tab:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: rgba(244, 124, 72, 0.04);
        }
        .filter-tab.active {
            background: var(--primary-gradient, var(--primary));
            border-color: transparent;
            color: white !important;
            box-shadow: 0 4px 12px rgba(244, 124, 72, 0.25);
        }
        .filter-tab-count {
            font-size: 11px;
            font-weight: 800;
            padding: 2px 8px;
            border-radius: 100px;
            transition: all 0.2s ease;
        }
        .filter-tab.active .filter-tab-count {
            background: rgba(255, 255, 255, 0.25);
            color: white;
        }
        .filter-tab:not(.active) .filter-tab-count {
            background: var(--bg-deep);
            color: var(--text-muted);
        }
        .premium-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }
        .premium-table th {
            padding: 16px 20px;
            background: var(--bg-deep);
            color: var(--text-dim);
            font-weight: 700;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid var(--border);
        }
        .premium-table td {
            padding: 20px;
            border-bottom: 1px solid var(--border);
            font-size: 14px;
            font-weight: 500;
            color: var(--text-main);
        }
        .details-pre {
            margin: 8px 0 0 0;
            white-space: pre-wrap;
            word-break: break-all;
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            background: var(--bg-deep);
            padding: 12px;
            border-radius: var(--radius);
            border: 1px solid var(--border);
            color: var(--text-muted);
            display: none;
            animation: slideDown 0.2s ease-out;
        }
        .details-toggle-btn {
            color: var(--primary);
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: transparent;
            border: none;
            padding: 0;
            outline: none;
            transition: 0.2s;
        }
        .details-toggle-btn:hover {
            color: var(--primary-hover, #ff6b3d);
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-4px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    
    <header class="global-nav"></header>
    
    <div class="container dashboard-layout">
        
        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="profile-side-card">
                <div class="avatar-container">
                    <span id="sb-initial">A</span>
                </div>
                <h3 id="sb-name" style="font-size: 18px; font-weight: 800; color: var(--text-main); margin-bottom: 6px;">Admin Command</h3>
                <p style="color: #ef4444; font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 24px;">Secure Command</p>
                <a href="../" class="btn btn-glass" style="width: 100%; padding: 10px; font-size: 13px;">View Live Portal</a>
            </div>

            <nav class="side-nav">
                <a href="dashboard.html" class="side-nav-link"><i class="fas fa-shield-halved"></i> Dashboard</a>
                <a href="manage-users.html" class="side-nav-link"><i class="fas fa-users-gear"></i> Manage Users</a>
                <a href="manage-jobs.html" class="side-nav-link"><i class="fas fa-briefcase"></i> Manage Jobs</a>
                <a href="manage-trainings.html" class="side-nav-link"><i class="fas fa-graduation-cap"></i> Manage Trainings</a>
                <a href="manage-applications.html" class="side-nav-link"><i class="fas fa-file-invoice"></i> Applications</a>
                <a href="reports.html" class="side-nav-link"><i class="fas fa-chart-line"></i> System Reports</a>
                <a href="history.php" class="side-nav-link active"><i class="fas fa-history"></i> Audit History</a>
            </nav>
        </aside>

        <!-- MAIN AREA -->
        <main class="main-area">
            <div style="margin-bottom: 36px;">
                <h1 style="font-size: 32px; font-weight: 900; letter-spacing: -1.5px; color: var(--text-main); margin-bottom: 6px;">Audit History</h1>
                <p style="color: var(--text-muted); font-size: 15px; font-weight: 500;">Complete chronological system logs of administrative and user actions.</p>
            </div>

            <div class="dashboard-panel animate-in">
                <div class="panel-header">
                    <h3>Global Security Trail</h3>
                    <span style="font-size: 13px; font-weight: 700; color: var(--text-dim);"><i class="fas fa-shield-halved" style="margin-right: 4px;"></i> Live security log</span>
                </div>

                <div class="search-bar-grid">
                    <div class="filter-tabs" id="role-tabs">
                        <!-- Role filter tabs will be injected dynamically -->
                    </div>
                    <div style="position: relative; width: 100%;">
                        <i class="fas fa-search" style="position: absolute; left: 20px; top: 50%; transform: translateY(-50%); color: var(--text-dim); font-size: 14px;"></i>
                        <input type="text" id="filter-search" placeholder="Search user name, action, or metadata details..." oninput="filterLogs()" style="width: 100%; padding: 14px 20px 14px 48px; border-radius: 100px; border: 1px solid var(--border); background: var(--bg-deep); color: var(--text-main); font-size: 14px; font-weight: 600; outline: none; transition: all 0.2s;" onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='var(--border)'">
                    </div>
                </div>

                <div class="table-responsive" style="overflow-x: auto; width: 100%;">
                    <table class="premium-table">
                        <thead>
                            <tr>
                                <th style="width: 15%;">When</th>
                                <th style="width: 20%;">User</th>
                                <th style="width: 12%;">Role</th>
                                <th style="width: 25%;">Action</th>
                                <th style="width: 28%;">Details</th>
                            </tr>
                        </thead>
                        <tbody id="logs-table">
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 40px; color: var(--text-dim);">
                                    <i class="fas fa-spinner fa-spin" style="font-size: 24px; color: var(--primary);"></i>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
        
    </div>

    <footer class="premium-footer" style="padding: 60px 0; border-top: 1px solid var(--border); margin-top: 80px; text-align: center; color: var(--text-dim); font-size: 14px; font-weight: 600;">
        <p>&copy; 2026 SmartJob Nepal. All rights reserved.</p>
    </footer>

    <script src="../assets/js/main.js"></script>
    <script>
        // Pass server data to JavaScript array instantly
        const allLogs = <?= json_encode($logs); ?>;
        let currentTabRole = ''; // Default all roles

        async function init() {
            const user = await checkAuth('admin');
            if (user) {
                document.getElementById('sb-name').textContent = user.name;
                document.getElementById('sb-initial').textContent = user.name.charAt(0).toUpperCase();
            }
            renderTabs();
            filterLogs();
        }

        function renderTabs() {
            const counts = {
                all: allLogs.length,
                admin: allLogs.filter(l => l.role === 'admin').length,
                employer: allLogs.filter(l => l.role === 'employer').length,
                seeker: allLogs.filter(l => l.role === 'seeker').length
            };
            const tabs = [
                { id: '', label: 'All Activities', count: counts.all, icon: 'fa-history' },
                { id: 'admin', label: 'Admin', count: counts.admin, icon: 'fa-shield-halved' },
                { id: 'employer', label: 'Employer', count: counts.employer, icon: 'fa-briefcase' },
                { id: 'seeker', label: 'Seeker', count: counts.seeker, icon: 'fa-user' }
            ];
            const container = document.getElementById('role-tabs');
            container.innerHTML = tabs.map(t => `
                <button class="filter-tab ${currentTabRole === t.id ? 'active' : ''}" onclick="selectTab('${t.id}')">
                    <i class="fa-solid ${t.icon}"></i>
                    <span>${t.label}</span>
                    <span class="filter-tab-count">${t.count}</span>
                </button>
            `).join('');
        }

        function selectTab(role) {
            currentTabRole = role;
            renderTabs();
            filterLogs();
        }

        function filterLogs() {
            const q = document.getElementById('filter-search').value.toLowerCase();
            const filtered = allLogs.filter(l => {
                const matchRole = !currentTabRole || l.role === currentTabRole;
                const matchSearch = 
                    (l.user_name || '').toLowerCase().includes(q) ||
                    (l.action || '').toLowerCase().includes(q) ||
                    (l.details || '').toLowerCase().includes(q) ||
                    (String(l.user_id) || '').includes(q);
                return matchRole && matchSearch;
            });
            renderTable(filtered);
        }

        function renderTable(data) {
            const tbody = document.getElementById('logs-table');
            if (!data.length) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding: 40px; color: var(--text-dim); font-weight: 600;">No matching log activity found.</td></tr>';
                return;
            }

            tbody.innerHTML = data.map(l => {
                let badgeClass = 'badge-info';
                if (l.role === 'admin') badgeClass = 'badge-error';
                else if (l.role === 'employer') badgeClass = 'badge-success';
                else if (l.role === 'seeker') badgeClass = 'badge-warning';

                const logTime = new Date(l.timestamp).toLocaleString();
                const expandoId = `expando-${l.id}`;
                const detailContent = escHtml(l.details || 'No meta details.');

                // Simple check if details should show expando toggle or plain text
                const hasExpando = detailContent.length > 30;

                return `
                <tr class="animate-in" style="animation-duration: 0.15s;">
                    <td style="color: var(--text-dim); font-size: 13px; font-weight: 600;">${logTime}</td>
                    <td>
                        <div style="font-weight: 800; color: var(--text-main);">${escHtml(l.user_name)}</div>
                        <div style="font-size: 11px; color: var(--text-dim); font-weight: 600;">ID: ${l.user_id}</div>
                    </td>
                    <td>
                        <span class="badge ${badgeClass}" style="text-transform: uppercase; font-size: 10px; font-weight: 800;">${escHtml(l.role)}</span>
                    </td>
                    <td style="color: var(--primary); font-weight: 700; font-size: 14px;">${escHtml(l.action)}</td>
                    <td>
                        ${hasExpando ? `
                            <button class="details-toggle-btn" onclick="toggleDetails('${expandoId}', this)">
                                <i class="fas fa-circle-chevron-down"></i> View Details
                            </button>
                            <div id="${expandoId}" class="details-pre">${detailContent}</div>
                        ` : `
                            <span style="color: var(--text-muted); font-size: 13px; font-weight: 500;">${detailContent}</span>
                        `}
                    </td>
                </tr>`;
            }).join('');
        }

        function toggleDetails(id, btn) {
            const el = document.getElementById(id);
            const isHidden = el.style.display === 'none' || !el.style.display;
            
            el.style.display = isHidden ? 'block' : 'none';
            btn.innerHTML = isHidden 
                ? '<i class="fas fa-circle-chevron-up"></i> Hide Details' 
                : '<i class="fas fa-circle-chevron-down"></i> View Details';
        }

        init();
    </script>
</body>
</html>
