<?php
require_once '../config/session.php';
$user = requireRole('admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Jobs - JSTACK Admin</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    table { width:100%; border-collapse:collapse; background:white; }
    th, td { padding:12px 14px; text-align:left; border-bottom:1px solid #eee; font-size:14px; }
    th { background:#f8f9fa; color:#555; font-size:12px; text-transform:uppercase; }
    tr:hover { background:#fafbff; }
    .badge { padding:3px 10px; border-radius:12px; font-size:11px; font-weight:700; }
    .badge-active { background:#e8f5e9; color:#2e7d32; }
    .badge-closed { background:#f5f5f5; color:#999; }
    .search-row { display:flex; gap:10px; margin-bottom:20px; }
    .search-row input { flex:1; padding:8px 12px; border:1px solid #ddd; border-radius:6px; font-size:14px; }
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
  <h2 class="title">Manage Job Listings</h2>

  <div class="search-row">
    <input type="text" id="search-text"
           placeholder="Search by title, company or category..."
           oninput="filterJobs()">
    <button onclick="loadJobs()"
            style="padding:8px 16px;background:#666;color:white;border:none;border-radius:6px;cursor:pointer;">
      Reset
    </button>
  </div>

  <div style="background:white;border-radius:8px;border:1px solid #eee;overflow:hidden;">
    <div id="alert-box"></div>
    <table>
      <thead>
        <tr>
          <th>ID</th><th>Title</th><th>Company</th><th>Category</th>
          <th>Location</th><th>Status</th><th>Actions</th>
        </tr>
      </thead>
      <tbody id="jobs-table">
        <tr><td colspan="7" style="text-align:center;padding:40px;color:#aaa;">Loading...</td></tr>
      </tbody>
    </table>
  </div>
</div>

<script src="../assets/js/main.js?v=1.2"></script>
<script>
  let allJobs = [];

  document.addEventListener('DOMContentLoaded', loadJobs);

  async function loadJobs() {
    document.getElementById('search-text').value = '';
    document.getElementById('jobs-table').innerHTML =
      '<tr><td colspan="7" style="text-align:center;padding:40px;color:#aaa;">Loading...</td></tr>';

    const res = await apiGet(`${API}/jobs.php?admin_view=1`);
    if (res && res.success) {
      allJobs = res.data || [];
      renderTable(allJobs);
    } else {
      showAlert('alert-box', res?.error || 'Failed to load jobs.', 'error');
    }
  }

  function renderTable(data) {
    const tbody = document.getElementById('jobs-table');
    if (!data.length) {
      tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:40px;color:#aaa;">No jobs found.</td></tr>';
      return;
    }
    tbody.innerHTML = data.map(j => `
      <tr>
        <td><strong>#${j.id}</strong></td>
        <td><strong>${esc(j.title)}</strong></td>
        <td>${esc(j.company)}</td>
        <td>${esc(j.category || 'N/A')}</td>
        <td>${esc(j.location || 'N/A')}</td>
        <td>
          <span class="badge badge-${j.status}">
            ${j.status.toUpperCase()}
          </span>
        </td>
        <td>
          <button onclick="deleteJob(${j.id})"
                  style="background:none;border:none;color:#d32f2f;cursor:pointer;font-size:13px;text-decoration:underline;">
            Delete
          </button>
        </td>
      </tr>`).join('');
  }

  function filterJobs() {
    const q = document.getElementById('search-text').value.toLowerCase();
    renderTable(allJobs.filter(j =>
      (j.title||'').toLowerCase().includes(q) ||
      (j.company||'').toLowerCase().includes(q) ||
      (j.category||'').toLowerCase().includes(q)
    ));
  }

  async function deleteJob(id) {
    if (!confirm('Delete this job and all its applications? This cannot be undone.')) return;

    const res = await apiDelete(`${API}/jobs.php?id=${id}`);
    if (res && res.success) {
      showAlert('alert-box', 'Job deleted successfully.', 'success');
      loadJobs();
    } else {
      showAlert('alert-box', res?.error || 'Delete failed.', 'error');
    }
  }

  const esc = s => String(s).replace(/[&<>"']/g, m =>
    ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
</script>
</body>
</html>

