/**
 * ── 1. CONFIGURATION & AUTO-PATHING ──
 */
const getBase = () => {
    // Automatically detects folder name (e.g., /smartjobportal)
    const path = window.location.pathname;
    const segments = path.split('/');
    return window.location.hostname === 'localhost' ? `/${segments[1]}` : '';
};

const BASE = getBase(); 
const API  = BASE + '/api';

// ── Fetch Wrapper ─────────────────────────────────────────────────────────────
async function _request(method, endpoint, data = null, isFormData = false) {
    const url = endpoint.startsWith('http') ? endpoint : API + endpoint;

    const opts = { 
        method, 
        credentials: 'include', 
        headers: isFormData ? {} : { 'Content-Type': 'application/json' } 
    };

    if (data) {
        opts.body = isFormData ? data : JSON.stringify(data);
    }

    try {
        const res = await fetch(url, opts);
        
        // Auto-redirect if session expires (except on login check)
        if (res.status === 401 && !url.includes('action=me')) {
            window.location.href = BASE + '/auth/login.html?reason=expired';
            return;
        }

        const ct = res.headers.get('content-type') || '';

        if (!ct.includes('application/json')) {
            const txt = await res.text();
            console.error('PHP Error detected:', txt.slice(0, 500));
            return { success: false, error: 'Server error. Please check XAMPP Apache logs.' };
        }

        const json = await res.json();
        return res.ok ? json : { success: false, ...json };

    } catch (e) { 
        console.error('Network Error:', e);
        return { success: false, error: 'Network error. Is XAMPP running?' }; 
    }
}

// ── API Shortcuts ─────────────────────────────────────────────────────────────
const apiGet    = ep     => _request('GET',    ep);
const apiPost   = (ep,d) => _request('POST',   ep, d);
const apiPut    = (ep,d) => _request('PUT',    ep, d);
const apiDelete = ep     => _request('DELETE', ep);

// ── Auth Logic ────────────────────────────────────────────────────────────────
async function getCurrentUser() {
    const r = await apiGet('/auth.php?action=me');
    return (r && r.success) ? r.user : null;
}

/**
 * Call this at the top of dashboard pages to prevent unauthorized access
 */
async function checkAuth(requiredRole = null) {
    const user = await getCurrentUser();
    if (!user) {
        window.location.href = BASE + '/auth/login.html';
        return null;
    }
    if (requiredRole && user.role !== requiredRole) {
        window.location.href = BASE + '/index.html';
        return null;
    }
    return user;
}

async function logout() {
    await apiPost('/auth.php?action=logout', {});
    window.location.href = BASE + '/auth/login.html';
}

// ── UI Helpers ────────────────────────────────────────────────────────────────
function showAlert(id, message, type = 'success') {
    const el = document.getElementById(id);
    if (!el) return;
    
    const themes = {
        success: ['#d4edda','#155724','#c3e6cb'],
        error:   ['#f8d7da','#721c24','#f5c6cb'],
        info:    ['#e7f3ff','#0a66c2','#b3d7ff']
    };
    const cfg = themes[type] || themes.info;
    
    el.innerHTML = `
        <div style="padding:12px 16px; border-radius:6px; margin-bottom:16px; 
                    background:${cfg[0]}; color:${cfg[1]}; border:1px solid ${cfg[2]}; 
                    display:flex; align-items:center; gap:10px;">
            <span style="font-weight:bold;">${type === 'error' ? '✕' : '✓'}</span>
            <span>${message}</span>
        </div>`;
    el.scrollIntoView({ behavior:'smooth', block:'center' });
}

function setLoading(btnId, isLoading, label = 'Processing...') {
    const btn = document.getElementById(btnId);
    if (!btn) return;
    
    if (isLoading) { 
        btn.dataset.orig = btn.innerHTML; 
        btn.disabled = true; 
        btn.innerHTML = `<span class="spinner-icon"></span> ${label}`; 
    } else { 
        btn.disabled = false; 
        btn.innerHTML = btn.dataset.orig || 'Submit'; 
    }
}

function escHtml(str) {
    if (!str) return '';
    const d = document.createElement('div');
    d.textContent = String(str);
    return d.innerHTML;
}

// ── Navbar ────────────────────────────────────────────────────────────────────
async function initNavbar() {
    const nav = document.getElementById('nav-actions');
    if (!nav) return;
    
    const user = await getCurrentUser();
    if (user) {
        const dashboard = BASE + `/${user.role}/dashboard.html`;
        nav.innerHTML = `
            <div style="display: flex; align-items: center; gap: 15px;">
                <span style="color:white; font-size:14px;">Hi, <strong>${escHtml(user.name)}</strong></span>
                <a href="${dashboard}" style="color:white; text-decoration:none;">Dashboard</a>
                <button onclick="logout()" style="background:#ff4d4d; border:none; color:white; padding:5px 10px; border-radius:4px; cursor:pointer; font-weight:bold;">Logout</button>
            </div>`;
    } else {
        nav.innerHTML = `
            <a href="${BASE}/auth/login.html" style="color:white; text-decoration:none; margin-right:15px;">Login</a>
            <a href="${BASE}/auth/register.html" style="background:white; color:#0a66c2; padding:5px 15px; border-radius:4px; text-decoration:none; font-weight:bold;">Register</a>
        `;
    }
}

document.addEventListener('DOMContentLoaded', initNavbar);