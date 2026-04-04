/**
 * main.js — Global helpers for JSTACK Job Portal
 * XAMPP folder: C:\xampp\htdocs\jobportalsystem\
 */

const BASE = '/jobportalsystem';
const API  = BASE + '/api';

// ── Alert helpers ──
function showAlert(boxId, message, type = 'info') {
    const box = document.getElementById(boxId);
    if (!box) return;
    box.innerHTML = `<div class="alert alert-${type}" style="padding:10px 14px;border-radius:6px;margin-bottom:12px;">${message}</div>`;
}
function clearAlert(boxId) {
    const box = document.getElementById(boxId);
    if (box) box.innerHTML = '';
}

// ── Button loading state ──
function setLoading(btnId, loading, text = '') {
    const btn = document.getElementById(btnId);
    if (!btn) return;
    if (!btn.dataset.original) btn.dataset.original = btn.textContent;
    btn.disabled    = loading;
    btn.textContent = loading ? (text || 'Loading...') : btn.dataset.original;
}

// ── POST ──
async function apiPost(url, data) {
    try {
        const res = await fetch(url, {
            method:      'POST',
            headers:     { 'Content-Type': 'application/json' },
            credentials: 'include',
            body:        JSON.stringify(data)
        });
        if (!res.ok) {
            const text = await res.text();
            throw new Error(`HTTP ${res.status}: ${text.substring(0, 100)}`);
        }
        return await res.json();
    } catch (err) {
        if (!err.message.includes('HTTP 401')) {
            console.error('API POST error:', err);
        }
        return null;
    }
}

// ── GET ──
async function apiGet(url) {
    try {
        const res = await fetch(url, {
            method:      'GET',
            credentials: 'include'
        });
        if (!res.ok) {
            const text = await res.text();
            throw new Error(`HTTP ${res.status}: ${text.substring(0, 100)}`);
        }
        return await res.json();
    } catch (err) {
        if (!err.message.includes('HTTP 401')) {
            console.error('API GET error:', err);
        }
        return null;
    }
}

// ── PUT (update) ──
async function apiPut(url, data) {
    try {
        const res = await fetch(url, {
            method:      'PUT',
            headers:     { 'Content-Type': 'application/json' },
            credentials: 'include',
            body:        JSON.stringify(data)
        });
        return await res.json();
    } catch (err) {
        console.error('API PUT error:', err);
        return null;
    }
}

// ── DELETE ──
async function apiDelete(url) {
    try {
        const res = await fetch(url, {
            method:      'DELETE',
            credentials: 'include'
        });
        return await res.json();
    } catch (err) {
        console.error('API DELETE error:', err);
        return null;
    }
}

// ── Check session (used by .html pages) ──
async function requireAuth(expectedRole = null) {
    const res = await apiGet(`${API}/auth.php?action=me`);
    if (!res || !res.success) {
        window.location.href = `${BASE}/auth/login.html`;
        return null;
    }
    if (expectedRole && res.user.role !== expectedRole) {
        window.location.href = `${BASE}/index.html`;
        return null;
    }
    return res.user;
}

// ── Role protection function — used by both .html and .php views ──
async function requireRole(expectedRole = null) {
    return await requireAuth(expectedRole);
}

// ── Basic session check without specific role ──
async function requireLogin() {
    return await requireAuth();
}

window.requireRole = requireRole;
window.requireLogin = requireLogin;

// ── Check session WITHOUT redirect (silently) ──
async function getCurrentUser() {
    try {
        const res = await apiGet(`${API}/auth.php?action=me`);
        return (res && res.success) ? res.user : null;
    } catch (err) {
        return null;
    }
}

// ── Logout (POST) ──
async function handleLogout() {
    await apiPost(`${API}/auth.php?action=logout`, {});
    window.location.href = `${BASE}/auth/login.html`;
}
// ── XSS Protection ──
function escHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

// Alias for buttons calling onclick="logout()"
window.logout = handleLogout;