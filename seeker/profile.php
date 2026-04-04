<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Profile - JSTACK</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/fontawesome.min.css">
</head>
<body>

<header class="navbar">
  <div class="nav-container">
    <h2>JSTACK</h2>
    <div id="nav-actions"></div> </div>
</header>

<div class="container" style="max-width: 700px; margin-top: 40px;">
  <div class="form-box shadow-sm" style="background: white; padding: 30px; border-radius: 8px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
      <h2 style="margin:0; color: #333;"><i class="fas fa-user-edit"></i> Edit Professional Profile</h2>
      <a href="dashboard.php" class="btn-secondary" style="text-decoration:none; font-size:14px;">← Dashboard</a>
    </div>

    <div id="alert-box"></div>

    <div class="profile-form">
      <div class="input-group" style="margin-bottom: 15px;">
        <label style="font-weight: 600;">Full Name</label>
        <input type="text" id="fullname" placeholder="e.g. John Doe" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
        <div class="input-group">
          <label style="font-weight: 600;">Phone Number</label>
          <input type="text" id="phone" placeholder="+977 98XXXXXXXX" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
        </div>
        <div class="input-group">
          <label style="font-weight: 600;">Years of Experience</label>
          <input type="text" id="experience" placeholder="e.g. 3 years" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
        </div>
      </div>

      <div class="input-group" style="margin-bottom: 15px;">
        <label style="font-weight: 600;">Key Skills (Comma separated)</label>
        <input type="text" id="skills" placeholder="PHP, MySQL, JavaScript, UI/UX" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
      </div>

      <div class="input-group" style="margin-bottom: 25px;">
        <label style="font-weight: 600;">Professional Bio</label>
        <textarea id="bio" rows="5" placeholder="Tell employers about your background..." style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px; font-family: inherit; resize: vertical;"></textarea>
      </div>

      <button id="save-btn" onclick="handleSave()" 
        style="width: 100%; background: #0a66c2; color: white; border: none; padding: 14px; border-radius: 6px; font-size: 16px; font-weight: bold; cursor: pointer;">
        Update Profile
      </button>
    </div>
  </div>
</div>

<footer style="margin-top: 50px; text-align: center; color: #888; font-size: 14px;">
  <p>© 2026 JSTACK</p>
</footer>

<script src="../assets/js/main.js?v=1.2"></script>
<script>
  /**
   * Load existing profile data
   */
  async function init() {
    // 1. Ensure only seekers can access this page
    const user = await requireAuth('seeker');
    if (!user) return;

    // 2. Fetch profile from /api/profiles.php
    const res = await apiGet(`${API}/profiles.php`);
    
    if (res && res.success && res.data) {
      const p = res.data;
      document.getElementById('fullname').value   = p.name || '';
      document.getElementById('phone').value      = p.phone || '';
      document.getElementById('skills').value     = p.skills || '';
      document.getElementById('experience').value = p.experience || '';
      document.getElementById('bio').value        = p.bio || '';
    }
  }

  /**
   * Save profile updates
   */
  async function handleSave() {
    const name = document.getElementById('fullname').value.trim();
    if (!name) {
      showAlert('alert-box', 'Full name is required.', 'error');
      return;
    }

    setLoading('save-btn', true, 'Saving changes...');

    // Use apiPost with an update action to handle the profile change
    const res = await apiPost(`${API}/profiles.php?action=update`, {
      name:       name,
      phone:      document.getElementById('phone').value.trim(),
      skills:     document.getElementById('skills').value.trim(),
      experience: document.getElementById('experience').value.trim(),
      bio:        document.getElementById('bio').value.trim(),
    });

    setLoading('save-btn', false);

    if (res.success) {
      showAlert('alert-box', 'Profile successfully updated!', 'success');
      // Update the name in the navbar immediately without refreshing
      const navName = document.querySelector('#nav-actions strong');
      if (navName) navName.textContent = name;
    } else {
      showAlert('alert-box', res.error || 'Failed to update profile.', 'error');
    }
  }

  init();
</script>
</body>
</html>