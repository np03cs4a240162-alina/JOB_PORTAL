<?php
require_once '../config/session.php';
$user = requireRole('admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Applications - JSTACK Admin</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    table { width:100%; border-collapse:collapse; background:white; }
    th, td { padding:12px 14px; text-align:left; border-bottom:1px solid #eee; font-size:14px; }
    th { background:#f8f9fa; color:#555; font-size:12px; text-transform:uppercase; }
    tr:hover { background:#fafbff; }
    .badge { padding:3px 10px; border-radius:12px; font-size:11px; font-weight:700; }
    .badge-pending  { background:#fff8e1; color:#f57f17; }
    .badge-accepted { background:#e8f5e9; color:#2e7d32; }
    .badge-rejected { background:#ffebee; color:#c62828; }
    .filter-row { display:flex; gap:10px; margin-bottom:20px; flex-wrap:wrap; }
    .filter-row select, .filter-row input {
      padding:8px 12px; border:1px solid #ddd; border-radius:6px; font-size:14px;
    }
    .filter-row input { flex:1; min-width:200px; }
  </style>
</head>
<body>

<header class="navbar">
  <h2>JSTACK <span style="font-weight:normal;opacity:.8;">| Admin</span></h2>
  <nav>
    <a href="dashboard.php" style="color:white;margin-right:16px;text-decoration:none;">Dashboard</a>
    <button onclick="handleLogout()" style="background:#c62828;color:white;border:none;padding:6px 14px;border-radius:5px;cursor:pointer;">Logout</button>
  </nav>
</header>

<div class="container" style="margin-top:30px;">
  <h2 class="title">Manage Applications</h2>

  <div class="filter-row">
    <select id="filter-status" onchange="filterApps()">
      <option value="">All Statuses</option>
      <option value="pending">Pending</option>
      <option value="accepted">Accepted</option>
      <option value="rejected">Rejected</option>
    </select>
    <input type="text" id="filter-search"
           placeholder="Search applicant or job title..."
           oninput="filterApps()">
  </div>

  <div style="background:white;border-radius:8px;border:1px solid #eee;overflow:hidden;">
    <div id="alert-box"></div>
    <table>
      <thead>
        <tr>
          <th>Applicant</th>
          <th>Job Title</th>
          <th>Company</th>
          <th>Applied</th>
          <th>Status</th>
          <th>Update</th>
        </tr>
      </thead>
      <tbody id="apps-table">
        <tr><td colspan="6" style="text-align:center;padding:40px;color:#aaa;">Loading...</td></tr>
      </tbody>
    </table>
  </div>
</div>

<script src="../assets/js/main.js?v=1.2"></script>
<script>
  let allApps = [];

  document.addEventListener('DOMContentLoaded', loadApps);

  async function loadApps() {
    document.getElementById('apps-table').innerHTML =
      '<tr><td colspan="6" style="text-align:center;padding:40px;color:#aaa;">Loading...</td></tr>';

    const res = await apiGet(`${API}/applications.php?admin_view=1`);
    if (res && res.success) {
      allApps = res.data || [];
      renderTable(allApps);
    } else {
      showAlert('alert-box', res?.error || 'Failed to load applications.', 'error');
    }
  }

  function renderTable(apps) {
    const tbody = document.getElementById('apps-table');
    if (!apps.length) {
      tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:40px;color:#aaa;">No applications found.</td></tr>';
      return;
    }
    tbody.innerHTML = apps.map(a => `
      <tr>
        <td>
          <strong>${esc(a.seeker_name || 'Unknown')}</strong><br>
          <small style="color:#888;">${esc(a.seeker_email || '')}</small>
        </td>
        <td>${esc(a.job_title || 'N/A')}</td>
        <td>${esc(a.company || 'N/A')}</td>
        <td style="font-size:12px;color:#999;">${new Date(a.applied_at).toLocaleDateString()}</td>
        <td><span class="badge badge-${a.status}">${a.status.toUpperCase()}</span></td>
        <td>
          <select onchange="updateStatus(${a.id}, this.value)"
                  style="padding:5px 8px;border-radius:5px;border:1px solid #ddd;font-size:13px;">
            <option value="pending"  ${a.status==='pending'  ?'selected':''}>Pending</option>
            <option value="accepted" ${a.status==='accepted' ?'selected':''}>Accept</option>
            <option value="rejected" ${a.status==='rejected' ?'selected':''}>Reject</option>
          </select>
        </td>
      </tr>`).join('');
  }

  function filterApps() {
    const status = document.getElementById('filter-status').value;
    const q      = document.getElementById('filter-search').value.toLowerCase();
    renderTable(allApps.filter(a =>
      (!status || a.status === status) &&
      ((a.seeker_name||'').toLowerCase().includes(q) ||
       (a.job_title||'').toLowerCase().includes(q))
    ));
  }

  async function updateStatus(id, status) {

    const res = await apiPut(`${API}/applications.php?id=${id}`, { status });
    if (res && res.success) {
      showAlert('alert-box', `Status updated to "${status}".`, 'success');
      loadApps();
    } else {
      showAlert('alert-box', res?.error || 'Update failed.', 'error');
    }
  }

  const esc = s => String(s).replace(/[&<>"']/g, m =>
    ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
</script>
</body>
</html>

