/**
 * ── 1. CONFIGURATION & AUTO-PATHING ──
 */
const getBase = () => {
    const path = window.location.pathname;
    const isLocal = ['localhost', '127.0.0.1', '::1'].includes(window.location.hostname);
    
    // If running on local XAMPP/WAMP, dynamically extract the first folder segment
    // This allows the folder to be named 'newjob', 'job_portal', or anything else!
    if (isLocal) {
        const segments = path.split('/');
        if (segments.length > 1 && segments[1] !== '') {
            return '/' + segments[1];
        }
    }
    
    // Fallback for production server (runs on root domain)
    return '';
};

const BASE = getBase(); 
const API  = BASE + '/api';

// Global state for role-based features
window.state = {
    user: null,
    loading: true
};

// ── Fetch Wrapper ─────────────────────────────────────────────────────────────
async function _request(method, endpoint, data = null, isFormData = false) {
    // Cache busting for GET requests to prevent stale session checks
    const separator = endpoint.includes('?') ? '&' : '?';
    const finalEndpoint = method === 'GET' ? `${endpoint}${separator}cb=${Date.now()}` : endpoint;
    
    const url = finalEndpoint.startsWith('http') ? finalEndpoint : API + finalEndpoint;
    const token = localStorage.getItem('auth_token');
    const csrf = localStorage.getItem('csrf_token');

    const opts = { 
        method, 
        credentials: 'include', 
        headers: isFormData ? {} : { 'Content-Type': 'application/json' } 
    };

    if (token) {
        opts.headers['Authorization'] = `Bearer ${token}`;
    }
    if (csrf) {
        opts.headers['X-CSRF-Token'] = csrf;
    }

    if (data) {
        opts.body = isFormData ? data : JSON.stringify(data);
    }

    try {
        const res = await fetch(url, opts);
        
        // Auto-redirect if session expires, but NOT for auth check (action=me) 
        // and NOT if we are already on the auth pages to avoid blinking/loops.
        const path = window.location.pathname;
        const isAuthPage = path.includes('/auth/login.html') || path.includes('/auth/register.html');
        
        if ((res.status === 401 || res.status === 403) && 
            !url.includes('action=login') && 
            !url.includes('action=register') && 
            !url.includes('action=me') &&
            !isAuthPage) {
            
            console.warn('Session expired or unauthorized. Redirecting to login.');
            localStorage.removeItem('auth_token');
            window.location.href = BASE + '/auth/login.html?reason=expired';
            return;
        }

        const ct = res.headers.get('content-type') || '';
        if (!ct.includes('application/json')) {
            const txt = await res.text();
            console.error('PHP Error:', txt.slice(0, 500));
            return { success: false, error: 'Server error.' };
        }

        const json = await res.json();
        const result = res.ok ? json : { success: false, ...json };
        if (typeof result === 'object' && result !== null) {
            result.status = res.status;
        }
        return result;

    } catch (e) { 
        console.error('Network Error:', e);
        return { success: false, error: 'Network error.' }; 
    }
}

// ── API Shortcuts ─────────────────────────────────────────────────────────────
const apiGet    = ep     => _request('GET',    ep);
const apiPost   = (ep,d) => _request('POST',   ep, d);
const apiPut    = (ep,d) => _request('PUT',    ep, d);
const apiDelete = ep     => _request('DELETE', ep);
const apiPostFile = (ep, fd) => _request('POST', ep, fd, true);

// ── Auth Logic ────────────────────────────────────────────────────────────────
let userPromise = null;
async function getCurrentUser() {
    if (window.state.user) return window.state.user;
    if (userPromise) return userPromise;
    
    userPromise = apiGet('/auth.php?action=me').then(r => {
        if (r && r.success) {
            window.state.user = r.user;
            if (r.csrf_token) {
                localStorage.setItem('csrf_token', r.csrf_token);
            }
            return r.user;
        }
        userPromise = null; // Allow retry on failure
        return null;
    });
    return userPromise;
}

/**
 * Call this at the top of dashboard pages to prevent unauthorized access
 */
async function checkAuth(checkAuth = null) {
    const user = await getCurrentUser();
    if (!user) {
        window.location.href = BASE + '/auth/login.html';
        return null;
    }
    if (checkAuth && user.role !== checkAuth) {
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
    
    const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
    const toastClass = type === 'success' ? 'toast-success' : 'toast-error';
    
    el.innerHTML = `
        <div class="toast ${toastClass} animate-in">
            <i class="fas ${icon}"></i>
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

/**
 * Normalizes image paths to prevent 404s from redundant prefixes
 */
function getImageUrl(path, type = 'photo') {
    if (!path || path === 'null' || path === 'undefined') return '';
    // If it's already a full URL
    if (path.startsWith('http')) return path;
    // If it already has uploads/ prefix, just point to the root uploads
    if (path.includes('uploads/')) {
        return BASE + '/' + path.replace(/^.*uploads\//, 'uploads/');
    }
    // Otherwise, it's a raw filename in the images folder
    return BASE + '/uploads/images/' + path;
}

// ── Navbar ────────────────────────────────────────────────────────────────────
function toggleNavDropdown(e) {
    e.stopPropagation();
    const trigger = e.currentTarget;
    trigger.classList.toggle('active');
}

function toggleTheme() {
    const current = document.documentElement.getAttribute('data-theme');
    const target = current === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', target);
    localStorage.setItem('theme', target);
    
    // Update icons
    const icon = document.querySelector('.theme-toggle i');
    if (icon) {
        icon.className = target === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
    }
}

async function initNavbar() {
    let header = document.querySelector('header.global-nav');
    if (!header) return;
    
    const user = await getCurrentUser();
    
    const logoHtml = `<a href="${BASE}/index.html" class="nav-logo">Smart<span>Job</span></a>`;
    
    const linksHtml = `
        <div class="nav-links">
            <a href="${BASE}/jobs/listing.html">Find Jobs</a>
            <a href="${BASE}/reviews/company-review.html">Reviews</a>
            <a href="${BASE}/trainings.html">Workshops</a>
            <a href="${BASE}/seeker/ai-resume-parser.php">AI Parser</a>
        </div>
    `;

    let actionsHtml = '';
    if (user) {
        const dashboardUrl = BASE + `/${user.role}/dashboard.html`;
        const initials = user.name.slice(0, 1).toUpperCase();
        
        let roleAction = '';
        if (user.role === 'employer') {
            roleAction = `<a href="${BASE}/employer/post-job.html" class="btn btn-primary" style="padding: 8px 16px; font-size: 13px; margin-right: 16px; border-radius: 100px;">Post a Job</a>`;
        } else if (user.role === 'admin') {
            roleAction = `<a href="${BASE}/admin/dashboard.html" class="btn btn-glass" style="padding: 8px 16px; font-size: 13px; margin-right: 16px; border-radius: 100px;">Admin Console</a>`;
        }
        
        actionsHtml = `
            <div class="nav-actions">
                ${roleAction}
                <div class="user-profile-trigger" onclick="toggleNavDropdown(event)">
                    <div class="user-avatar">${initials}</div>
                    <span class="user-name">${escHtml(user.name)}</span>
                    <i class="fas fa-chevron-down" style="font-size:10px; color:#94a3b8;"></i>
                    <div class="nav-dropdown">
                        <div class="dropdown-header">
                            <div class="user-avatar-sm">${initials}</div>
                            <div>
                                <div style="font-weight: 800; font-size: 14px;">${escHtml(user.name)}</div>
                                <div style="font-size: 11px; color: var(--text-dim); text-transform: capitalize;">${user.role} Account</div>
                            </div>
                        </div>
                        <a href="${dashboardUrl}"><i class="fas fa-th-large" style="width:20px;"></i> Dashboard</a>
                        <a href="${BASE}/seeker/profile.html"><i class="fas fa-user-cog" style="width:20px;"></i> Profile Settings</a>
                        <div style="height:1px; background:var(--border); margin:8px 0;"></div>
                        <a href="#" onclick="logout()" class="logout-link"><i class="fas fa-sign-out-alt" style="width:20px;"></i> Logout</a>
                    </div>
                </div>
            </div>
        `;
    } else {
        actionsHtml = `
            <div class="nav-actions">
                <a href="${BASE}/auth/login.html" class="btn-login">Sign In</a>
                <a href="${BASE}/auth/register.html" class="btn-cta">Get Started</a>
            </div>
        `;
    }

    const theme = localStorage.getItem('theme') || 'light';
    const themeIcon = theme === 'dark' ? 'fa-sun' : 'fa-moon';
    const themeToggleHtml = `
        <button class="btn btn-glass theme-toggle" onclick="toggleTheme()" style="width: 40px; height: 40px; padding: 0; border-radius: 12px; margin-right: 12px;">
            <i class="fas ${themeIcon}"></i>
        </button>
    `;

    header.innerHTML = `
        <div class="nav-container">
            <div class="nav-inner">
                ${logoHtml}
                ${linksHtml}
                <div style="display: flex; align-items: center;">
                    ${themeToggleHtml}
                    ${actionsHtml}
                </div>
            </div>
        </div>`;

    document.addEventListener('click', () => {
        document.querySelectorAll('.user-profile-trigger.active').forEach(el => el.classList.remove('active'));
    });
}

document.addEventListener('DOMContentLoaded', () => {
    // Apply saved theme immediately
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);

    initNavbar();

    // NUCLEAR RESET: If we are on login.html with reason=expired, 
    // clear everything to stop redirect loops.
    const params = new URLSearchParams(window.location.search);
    if (window.location.pathname.includes('/auth/login.html') && params.get('reason') === 'expired') {
        console.log('Nuclear reset triggered to stop loop.');
        localStorage.clear();
        sessionStorage.clear();
        // Clear cookie manually too
        document.cookie = "auth_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
    }
    
    // Add scroll effect to navbar
    window.addEventListener('scroll', () => {
        const nav = document.querySelector('.global-nav');
        if (nav) {
            if (window.scrollY > 50) {
                nav.classList.add('scrolled');
            } else {
                nav.classList.remove('scrolled');
            }
        }
    });
});