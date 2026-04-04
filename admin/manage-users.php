<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | JSTACK Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Table Badges */
        .badge { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: bold; }
        .badge-error { background: #ffebee; color: #c62828; }
        .badge-success { background: #e8f5e9; color: #2e7d32; }
        .badge-info { background: #e3f2fd; color: #1565c0; }
        
        /* Table specific styles */
        table { width: 100%; border-collapse: collapse; margin-top: 10px; background: white; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; color: #555; font-size: 13px; }
        tr:hover { background: #fafafa; }
        
        .search-bar { display: flex; gap: 10px; margin-bottom: 20px; }
        .search-bar input { padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
    </style>
</head>
<body>

<div class="navbar">
    <h2 style="color:white; margin:0;">JSTACK Admin</h2>
    <nav style="margin-left:auto;">
        <a href="dashboard.php" style="color:white; margin-right:15px; text-decoration:none;">Dashboard</a>
        <a href="manage-users.php" style="color:white; font-weight:bold; text-decoration:underline;">Users</a>
        <a href="#" onclick="logout()" style="color:white; margin-left:15px;">Logout</a>
    </nav>
</div>

<div class="container" style="max-width: 1000px; margin: 40px auto; padding: 0 20px;">
    <h2 class="title">User Management</h2>

    <form class="form-box" onsubmit="event.preventDefault(); createUser();" style="background:#fff; border:1px solid #ddd; padding:25px; border-radius:8px; margin-bottom:30px;">
        <h3 style="margin-top:0;">➕ Add New User</h3>
        <div id="create-alert"></div>
        
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:16px;">
            <div class="input-group">
                <label>Full Name</label>
                <input type="text" id="new-name" placeholder="Enter full name" style="width:100%; padding:8px;">
            </div>
            <div class="input-group">
                <label>Email Address</label>
                <input type="email" id="new-email" placeholder="email@example.com" style="width:100%; padding:8px;">
            </div>
            <div class="input-group">
                <label>Password</label>
                <input type="password" id="new-password" placeholder="Min 6 characters" style="width:100%; padding:8px;">
            </div>
            <div class="input-group">
                <label>Assign Role</label>
                <select id="new-role" style="width:100%; padding:8px;">
                    <option value="seeker">Job Seeker</option>
                    <option value="employer">Employer</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
        </div>
        
        <div style="margin-top: 20px; display: flex; justify-content: flex-end;">
            <button id="btn-add-user" type="submit" class="btn-primary" style="padding: 10px 25px; cursor:pointer;">
                Add User
            </button>
        </div>
    </form>

    <div class="search-bar">
        <input type="text" id="search-text" placeholder="Search by name or email..." oninput="localSearch()" style="flex-grow:2;">
        <input type="number" id="search-id" placeholder="ID..." style="width:80px;">
        <button onclick="searchById()" class="btn-primary" style="padding:0 15px;">Find</button>
        <button onclick="loadUsers()" style="padding:0 15px; background:#666; color:white; border:none; border-radius:4px; cursor:pointer;">Reset</button>
    </div>

    <div class="table-container" style="background:white; border-radius:8px; border:1px solid #ddd; overflow:hidden;">
        <div id="table-alert"></div>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User Details</th>
                    <th>Role</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="users-table">
                </tbody>
        </table>
    </div>
</div>

<script src="../assets/js/main.js?v=1.2"></script>
<script>
    // Config
    const API_URL = `${API}/users.php`;
    let allUsers = [];
    const roleClass = { 
        admin: 'badge-error', 
        employer: 'badge-success', 
        seeker: 'badge-info' 
    };

    window.onload = loadUsers;

    /**
     * 1. GET ALL USERS
     */
    async function loadUsers() {
        const tbody = document.getElementById('users-table');
        document.getElementById('search-text').value = '';
        document.getElementById('search-id').value = '';
        
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:40px;">Loading users...</td></tr>';

        try {
            // Using the apiGet function from your main.js
            const res = await apiGet(API_URL);
            
            if (res && res.success) {
                allUsers = res.data;
                renderTable(allUsers);
            } else {
                showAlert('table-alert', res.message || 'Access Denied.', 'error');
            }
        } catch (err) {
            showAlert('table-alert', 'Server connection failed.', 'error');
        }
    }

    /**
     * 2. RENDER TABLE
     */
    function renderTable(data) {
        const tbody = document.getElementById('users-table');
        
        if (!data || data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:40px; color:#888;">No users found.</td></tr>';
            return;
        }

        tbody.innerHTML = data.map(u => `
            <tr>
                <td><strong>#${u.id}</strong></td>
                <td>
                    <div style="font-weight:bold;">${escHtml(u.name)}</div>
                    <div style="font-size:12px; color:#666;">${escHtml(u.email)}</div>
                </td>
                <td>
                    <span class="badge ${roleClass[u.role] || 'badge-info'}">
                        ${u.role.toUpperCase()}
                    </span>
                </td>
                <td style="font-size:12px; color:#888;">${new Date(u.created_at).toLocaleDateString()}</td>
                <td>
                    ${u.role !== 'admin' ? `
                        <button onclick="deleteUser(${u.id})" style="background:none; border:none; color:#d32f2f; cursor:pointer; font-size:12px; text-decoration:underline;">
                            Delete
                        </button>
                    ` : '<span style="font-size:11px; color:#999; font-style:italic;">Protected</span>'}
                </td>
            </tr>
        `).join('');
    }

    /**
     * 3. CREATE USER (POST)
     */
    async function createUser() {
        const payload = {
            name: document.getElementById('new-name').value.trim(),
            email: document.getElementById('new-email').value.trim(),
            password: document.getElementById('new-password').value,
            role: document.getElementById('new-role').value
        };

        if (!payload.name || !payload.email || !payload.password) {
            showAlert('create-alert', 'All fields are required.', 'error');
            return;
        }

        const res = await apiPost(API_URL, payload);

        if (res && res.success) {
            showAlert('create-alert', 'User added successfully!', 'success');
            document.getElementById('new-name').value = '';
            document.getElementById('new-email').value = '';
            document.getElementById('new-password').value = '';
            loadUsers(); // Refresh the list
        } else {
            showAlert('create-alert', res.message || 'Failed to create user.', 'error');
        }
    }

    /**
     * 4. DELETE USER (DELETE)
     */
    async function deleteUser(id) {
        if (!confirm("Warning: Deleting this user will remove all their profile data. Continue?")) return;

        try {
            // Use apiDelete (assuming it's in your main.js) or fetch directly
            const response = await fetch(`${API_URL}?id=${id}`, { method: 'DELETE' });
            const res = await response.json();

            if (res.success) {
                loadUsers();
                showAlert('table-alert', 'User removed.', 'success');
            } else {
                showAlert('table-alert', res.message || 'Delete failed.', 'error');
            }
        } catch (err) {
            showAlert('table-alert', 'Request failed.', 'error');
        }
    }

    /**
     * SEARCH HELPERS
     */
    function localSearch() {
        const q = document.getElementById('search-text').value.toLowerCase();
        const filtered = allUsers.filter(u => 
            u.name.toLowerCase().includes(q) || 
            u.email.toLowerCase().includes(q)
        );
        renderTable(filtered);
    }

    async function searchById() {
        const id = document.getElementById('search-id').value.trim();
        if (!id) return;
        const res = await apiGet(`${API_URL}?id=${id}`);
        if (res && res.success) {
            renderTable([res.data]);
        } else {
            renderTable([]);
        }
    }

    function escHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
</script>

</body>
</html>