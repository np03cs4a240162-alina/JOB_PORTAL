<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Applications - JSTACK</title>
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
    <h2 class="title" style="margin:0;">My Applications</h2>
    <a href="../jobs/listing.html" class="btn-primary" style="text-decoration:none; font-size:14px;">Browse More Jobs</a>
  </div>

  <div id="alert-box"></div>

  <div class="table-container shadow-sm" style="background: white; border-radius: 8px; overflow: hidden;">
    <table style="width: 100%; border-collapse: collapse;">
      <thead>
        <tr style="background: #0a66c2; color: white; text-align: left;">
          <th style="padding: 15px;">Job Title</th>
          <th>Company</th>
          <th>Location</th>
          <th>Applied On</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody id="apps-table">
        <tr>
          <td colspan="5" style="text-align:center; padding: 40px;">
            <i class="fas fa-spinner fa-spin"></i> Loading your applications...
          </td>
        </tr>
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

    const user = await requireAuth('seeker');
    if (!user) return;

    const res = await apiGet(`${API}/applications.php`);
    const tbody = document.getElementById('apps-table');

    if (!res.success || !res.data || res.data.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="5" style="text-align:center; padding: 60px; color: #888;">
            <div style="font-size: 40px; color: #ddd; margin-bottom: 10px;"><i class="fas fa-folder-open"></i></div>
            No applications found. <a href="../jobs/listing.html" style="color:#0a66c2;">Browse jobs now</a>
          </td>
        </tr>`;
      return;
    }

    tbody.innerHTML = res.data.map(a => {

      const statusInfo = {
        pending:  { color: '#856404', bg: '#fff3cd', icon: 'fa-clock' },
        accepted: { color: '#155724', bg: '#d4edda', icon: 'fa-check-circle' },
        rejected: { color: '#721c24', bg: '#f8d7da', icon: 'fa-times-circle' }
      }[a.status] || { color: '#333', bg: '#eee', icon: 'fa-info-circle' };

      return `
      <tr style="border-bottom: 1px solid #eee;">
        <td style="padding: 15px;">
          <strong style="color: #333;">${escHtml(a.job_title)}</strong>
        </td>
        <td style="color: #666;">${escHtml(a.company)}</td>
        <td style="color: #666;"><i class="fas fa-map-marker-alt" style="font-size: 12px;"></i> ${escHtml(a.location || 'Remote')}</td>
        <td style="font-size: 13px; color: #888;">${new Date(a.applied_at).toLocaleDateString()}</td>
        <td>
          <span style="display: inline-flex; align-items: center; gap: 5px; background: ${statusInfo.bg}; color: ${statusInfo.color}; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; text-transform: uppercase;">
            <i class="fas ${statusInfo.icon}"></i> ${a.status}
          </span>
        </td>
      </tr>`;
    }).join('');
  }

  init();
</script>
</body>
</html>

