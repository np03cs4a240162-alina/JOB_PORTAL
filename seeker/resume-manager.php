<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Resume Manager - JSTACK</title>
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
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
      <h2 style="margin:0; color: #333;"><i class="fas fa-file-pdf"></i> Resume Manager</h2>
      <a href="dashboard.php" class="btn-secondary" style="text-decoration:none; font-size:14px;">← Dashboard</a>
    </div>

    <div id="alert-box"></div>

    <div class="upload-section" style="background: #f8f9fa; padding: 20px; border-radius: 8px; border: 2px dashed #dee2e6; margin-bottom: 30px;">
      <label style="display:block; margin-bottom: 12px; font-weight: 600; color: #555;">
        Upload New Resume (PDF, DOC, DOCX — max 5MB)
      </label>
      <input type="file" id="resume-file" accept=".pdf,.doc,.docx" style="width: 100%; margin-bottom: 15px;">
      <button id="upload-btn" onclick="handleUpload()" 
        style="width: 100%; background: #0a66c2; color: white; border: none; padding: 12px; border-radius: 6px; font-weight: bold; cursor: pointer;">
        <i class="fas fa-upload"></i> Upload Resume
      </button>
    </div>

    <h4 style="color:#0a66c2; margin-bottom:15px; border-bottom: 2px solid #f0f2f5; padding-bottom: 10px;">
      Your Uploaded Resumes
    </h4>
    <div id="resume-list">
      <div style="text-align:center; padding: 20px; color: #888;">
        <i class="fas fa-spinner fa-spin"></i> Loading...
      </div>
    </div>
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
    loadResumes();
  }

  
  async function loadResumes() {
    const res = await apiGet(`${API}/upload.php`);
    const list = document.getElementById('resume-list');
    
    if (!res.success || !res.data || res.data.length === 0) {
      list.innerHTML = `
        <div style="text-align:center; padding: 30px; color:#888;">
          <i class="fas fa-file-excel" style="font-size: 30px; display:block; margin-bottom:10px; color:#ddd;"></i>
          No resumes uploaded yet.
        </div>`; 
      return;
    }

    list.innerHTML = res.data.map(r => `
      <div style="padding:15px; background:#fff; border: 1px solid #eee; border-radius:8px; margin-bottom:10px; display:flex; justify-content:space-between; align-items:center;">
        <div style="display:flex; align-items:center; gap: 12px;">
          <i class="fas fa-file-alt" style="color:#0a66c2; font-size:20px;"></i>
          <div>
            <div style="font-weight: 600; font-size:14px; color:#333;">${escHtml(r.filename)}</div>
            <small style="color:#999;">Uploaded on ${new Date(r.uploaded_at).toLocaleDateString()}</small>
          </div>
        </div>
        <div style="display:flex; gap: 8px;">
          <a href="${API}/../uploads/resumes/${r.filename}" target="_blank" class="btn-sm" style="background:#e7f3ff; color:#0a66c2; padding:6px 10px; border-radius:4px; text-decoration:none; font-size:12px;">
            <i class="fas fa-download"></i>
          </a>
          <button class="btn-sm danger" onclick="deleteResume(${r.id})" style="background:#fee; color:#d9534f; border:none; padding:6px 10px; border-radius:4px; cursor:pointer;">
            <i class="fas fa-trash"></i>
          </button>
        </div>
      </div>`).join('');
  }

  
  async function handleUpload() {
    const fileInput = document.getElementById('resume-file');
    const file = fileInput.files[0];
    
    if (!file) { 
      showAlert('alert-box', 'Please select a file to upload.', 'error'); 
      return; 
    }

    if (file.size > 5 * 1024 * 1024) {
      showAlert('alert-box', 'File is too large. Max size is 5MB.', 'error');
      return;
    }

    setLoading('upload-btn', true, 'Uploading...');
    
    const form = new FormData();
    form.append('resume', file);

    try {
      const response = await fetch(API + '/upload.php', { 
        method: 'POST', 
        credentials: 'include', 
        body: form 
      });
      const res = await response.json();
      
      setLoading('upload-btn', false);

      if (res.success) {
        showAlert('alert-box', 'Resume uploaded successfully!', 'success');
        fileInput.value = ''; // Reset input
        loadResumes(); // Refresh list
      } else {
        showAlert('alert-box', res.error || 'Upload failed.', 'error');
      }
    } catch (err) {
      setLoading('upload-btn', false);
      showAlert('alert-box', 'Connection error. Please try again.', 'error');
    }
  }

  
  async function deleteResume(id) {
    if (!confirm('Are you sure you want to delete this resume?')) return;
    
    const res = await apiDelete(`${API}/upload.php?id=${id}`);
    if (res.success) {
      showAlert('alert-box', 'Resume deleted.', 'success');
      loadResumes();
    } else {
      showAlert('alert-box', res.error || 'Delete failed.', 'error');
    }
  }

  init();
</script>
</body>
</html>

