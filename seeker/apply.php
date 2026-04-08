<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Apply for Job - JSTACK</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/fontawesome.min.css">
</head>
<body>

<header class="navbar">
  <div class="nav-container">
    <h2>JSTACK</h2>
    <div id="nav-actions"></div> </div>
</header>

<div class="container" style="max-width: 600px; margin-top: 40px;">
  <div class="form-box shadow-sm" style="background: white; padding: 30px; border-radius: 8px;">
    <a href="../jobs/listing.html" style="text-decoration:none; color:#0a66c2; font-size:14px; display:block; margin-bottom:20px;">
      <i class="fas fa-arrow-left"></i> Back to Job Listings
    </a>

    <h2 id="job-title" style="margin-bottom: 10px; color: #333;">Loading Job Details...</h2>
    <p id="job-meta" style="color: #666; margin-bottom: 25px; font-size: 14px;"></p>
    
    <hr style="border: 0; border-top: 1px solid #eee; margin-bottom: 25px;">
    
    <div id="alert-box"></div>

    <div id="apply-form">
      <div class="input-group" style="margin-bottom: 20px;">
        <label style="display:block; margin-bottom: 8px; font-weight: 600; color: #555;">
          Cover Note / Professional Summary
        </label>
        <textarea id="note" rows="8" 
          placeholder="Describe why you're a great fit for this role. Mention relevant skills and experience..." 
          style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-family: inherit; resize: vertical;"
          required></textarea>
      </div>

      <button id="apply-btn" onclick="handleApply()" 
        style="width: 100%; background: #0a66c2; color: white; border: none; padding: 14px; border-radius: 6px; font-size: 16px; font-weight: bold; cursor: pointer;">
        Submit Application
      </button>
    </div>
  </div>
</div>

<footer style="margin-top: 50px; text-align: center; color: #888; font-size: 14px;">
  <p>© 2026 JSTACK</p>
</footer>

<script src="../assets/js/main.js?v=1.2"></script>
<script>

  const jobId = new URLSearchParams(location.search).get('id');

  
  async function init() {

    const user = await requireAuth('seeker');
    if (!user) return;

    if (!jobId) {
      showAlert('alert-box', 'Invalid request. No job selected.', 'error');
      document.getElementById('apply-form').style.display = 'none';
      return;
    }

    const res = await apiGet(`${API}/jobs.php?id=${jobId}`);
    
    if (res && res.success && res.data) {
      const job = res.data;
      document.getElementById('job-title').textContent = `Apply for: ${escHtml(job.title)}`;
      document.getElementById('job-meta').innerHTML = `
        <i class="fas fa-building"></i> ${escHtml(job.company)} &nbsp; | &nbsp; 
        <i class="fas fa-map-marker-alt"></i> ${escHtml(job.location)}
      `;
    } else {
      showAlert('alert-box', 'Job not found or no longer active.', 'error');
      document.getElementById('apply-form').style.display = 'none';
      document.getElementById('job-title').textContent = 'Job Unavailable';
    }
  }

  
  async function handleApply() {
    const note = document.getElementById('note').value.trim();

    if (!note || note.length < 20) {
      showAlert('alert-box', 'Please provide a more detailed cover note (at least 20 characters).', 'error');
      return;
    }

    setLoading('apply-btn', true, 'Sending Application...');

    const res = await apiPost(`${API}/applications.php?action=apply`, { 
      job_id: parseInt(jobId), 
      resume_note: note 
    });

    setLoading('apply-btn', false);

    if (res.success) {
      showAlert('alert-box', '<strong>Success!</strong> Your application has been sent to the employer.', 'success');

      document.getElementById('apply-form').style.opacity = '0.5';
      document.getElementById('apply-btn').disabled = true;

      setTimeout(() => {
        location.href = 'applied-jobs.html';
      }, 2000);
    } else {
      showAlert('alert-box', res.error || 'Could not submit application.', 'error');
    }
  }

  init();
</script>
</body>
</html>


