<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Applicants - JSTACK</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/fontawesome.min.css">
</head>
<body>

<header class="navbar">
  <div class="nav-container">
    <h2>JSTACK</h2>
    <div id="nav-actions"></div> </div>
</header>

<div class="container" style="margin-top: 30px;">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2 class="title" style="margin:0;">Job Applicants</h2>
    <a href="dashboard.php" class="btn-secondary" style="text-decoration:none; font-size:14px;">← Back to Dashboard</a>
  </div>

  <div id="alert-box"></div>

  <div class="table-container shadow-sm" style="background: white; border-radius: 8px; overflow: hidden;">
    <table style="width: 100%; border-collapse: collapse;">
      <thead>
        <tr style="background: #0a66c2; color: white; text-align: left;">
          <th style="padding: 12px;">Applicant</th>
          <th>Email</th>
          <th>Job Title</th>
          <th>Applied Date</th>
          <th>Status</th>
          <th style="text-align: center;">Actions</th>
        </tr>
      </thead>
      <tbody id="applicants-table">
        <tr><td colspan="6" style="text-align:center; padding: 40px;">
          <i class="fas fa-spinner fa-spin"></i> Loading applicants...
        </td></tr>
      </tbody>
    </table>
  </div>
</div>

<footer style="margin-top: 50px; text-align: center; color: #888; font-size: 14px;">
  <p>© 2026 JSTACK</p>
</footer>

<script src="../assets/js/main.js?v=1.2"></script>
<script>
  
  async function init() {

    const user = await requireAuth('employer');
    if (!user) return;

    const res = await apiGet(`${API}/applications.php`);
    const tbody = document.getElementById('applicants-table');

    if (!res || !res.success || !res.data || res.data.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="6" style="text-align:center; padding: 50px; color: #666;">
            <div style="font-size: 40px; margin-bottom: 10px; color: #ccc;"><i class="fas fa-user-slash"></i></div>
            No applications received yet.
          </td>
        </tr>`;
      return;
    }

    tbody.innerHTML = res.data.map(a => {

      const statusColor = a.status === 'accepted' ? '#28a745' : (a.status === 'rejected' ? '#dc3545' : '#ffc107');
      const textColor = a.status === 'pending' ? '#000' : '#fff';

      return `
      <tr style="border-bottom: 1px solid #eee;">
        <td style="padding: 15px;">
          <div style="font-weight: 600; color: #333;">${escHtml(a.seeker_name)}</div>
        </td>
        <td style="color: #666;">${escHtml(a.seeker_email)}</td>
        <td style="color: #0a66c2; font-weight: 500;">${escHtml(a.job_title)}</td>
        <td style="font-size: 13px; color: #888;">
          ${new Date(a.applied_at).toLocaleDateString(undefined, {month:'short', day:'numeric', year:'numeric'})}
        </td>
        <td>
          <span style="background: ${statusColor}; color: ${textColor}; padding: 4px 10px; border-radius: 20px; font-size: 11px; text-transform: uppercase; font-weight: bold;">
            ${a.status}
          </span>
        </td>
        <td style="text-align: center;">
          ${a.status === 'pending' ? `
            <button class="btn-sm" onclick="updateStatus(${a.id}, 'accepted')" style="background:#28a745; color:white; border:none; padding:5px 10px; border-radius:4px; cursor:pointer; margin-right:5px;">
              <i class="fas fa-check"></i>
            </button>
            <button class="btn-sm" onclick="updateStatus(${a.id}, 'rejected')" style="background:#dc3545; color:white; border:none; padding:5px 10px; border-radius:4px; cursor:pointer;">
              <i class="fas fa-times"></i>
            </button>
          ` : `<span style="color:#bbb; font-size:12px;">Finalized</span>`}
        </td>
      </tr>`;
    }).join('');
  }

  
  async function updateStatus(id, newStatus) {
    setLoading(`verify-btn-${id}`, true, 'Updating...'); // Optional: unique button ID
    
    const res = await apiPost(`${API}/applications.php?action=update-status`, { 
        id: id, 
        status: newStatus 
    });

    if (res.success) {
      showAlert('alert-box', `Application successfully ${newStatus}!`, 'success');
      init(); // Refresh the list
    } else {
      showAlert('alert-box', res.error || 'Failed to update status.', 'error');
    }
  }

  init();
</script>

</body>
</html>

