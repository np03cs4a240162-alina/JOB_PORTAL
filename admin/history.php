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
    "SELECT al.id, al.created_at AS timestamp, al.user_name, al.role, al.action, al.details " .
    "FROM activity_logs al " .
    "ORDER BY al.created_at DESC LIMIT 200"
);
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8" />
    <title>Audit History – SmartJob Nepal</title>
    <link rel="stylesheet" href="../assets/css/style.css" />
    <style>
        .audit-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .audit-table th, .audit-table td { padding: 12px; border: 1px solid var(--border); text-align: left; font-size: 14px; }
        .audit-table th { background: var(--bg-surface); font-weight: 800; color: var(--text-main); }
        .audit-table tbody tr:nth-child(even) { background: var(--bg-deep); }
        pre { margin: 0; white-space: pre-wrap; word-break: break-all; font-family: monospace; }
    </style>
</head>
<body class="portal-layout">
    <header class="global-nav"></header>
    
    <main class="container" style="max-width:1200px; margin:auto; padding:120px 0 40px 0;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
            <h1 style="font-size:28px; font-weight:900; color:var(--text-main); margin: 0;">System Activity Logs</h1>
            <a href="dashboard.html" class="btn btn-glass" style="font-size: 13px; padding: 10px 16px;">
                <i class="fas fa-arrow-left" style="margin-right: 6px;"></i> Dashboard
            </a>
        </div>
        <div class="table-container" style="overflow-x:auto;">
            <table class="audit-table">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>User</th>
                        <th>Role</th>
                        <th>Action</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['timestamp']); ?></td>
                            <td><?= htmlspecialchars($row['user_name']); ?></td>
                            <td><span class="badge badge-info" style="text-transform: uppercase;"><?= htmlspecialchars($row['role']); ?></span></td>
                            <td style="color: var(--primary); font-weight:700;"><?= htmlspecialchars($row['action']); ?></td>
                            <td><pre style="color: var(--text-muted); font-weight: 500;"><?= htmlspecialchars($row['details']); ?></pre></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
    <footer class="premium-footer" style="padding:60px 0; border-top:1px solid var(--border); margin-top:80px; text-align:center; color:var(--text-dim); font-size:14px;">
        <p>&copy; 2026 SmartJob Nepal. All rights reserved.</p>
    </footer>
    <script src="../assets/js/main.js"></script>
</body>
</html>
