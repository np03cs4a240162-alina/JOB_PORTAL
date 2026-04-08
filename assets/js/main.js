

const BASE = '/jobportalsystem';
const API  = BASE + '/api';

function showAlert(boxId, message, type = 'info') {
    const box = document.getElementById(boxId);
    if (!box) return;
    box.innerHTML = `<div class="alert alert-${type}" style="padding:10px 14px;border-radius:6px;margin-bottom:12px;">${message}</div>`;
}
function clearAlert(boxId) {
    const box = document.getElementById(boxId);
    if (box) box.innerHTML = '';
}

function setLoading(btnId, loading, text = '') {
    const btn = document.getElementById(btnId);
    if (!btn) return;
    if (!btn.dataset.original) btn.dataset.original = btn.textContent;
    btn.disabled    = loading;
    btn.textContent = loading ? (text || 'Loading...') : btn.dataset.original;
}

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

async function requireRole(expectedRole = null) {
    return await requireAuth(expectedRole);
}

async function requireLogin() {
    return await requireAuth();
}

window.requireRole = requireRole;
window.requireLogin = requireLogin;

async function getCurrentUser() {
    try {
        const res = await apiGet(`${API}/auth.php?action=me`);
        return (res && res.success) ? res.user : null;
    } catch (err) {
        return null;
    }
}

async function handleLogout() {
    await apiPost(`${API}/auth.php?action=logout`, {});
    window.location.href = `${BASE}/auth/login.html`;
}

function escHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

window.logout = handleLogout;



function showToast(title, message, icon = "fas fa-bell") {
    let container = document.querySelector(".toast-container");
    if (!container) {
        container = document.createElement("div");
        container.className = "toast-container";
        document.body.appendChild(container);
    }

    const toast = document.createElement("div");
    toast.className = "toast";
    toast.innerHTML = `
        <i class="${icon}"></i>
        <div class="toast-info">
            <strong>${title}</strong>
            <p>${message}</p>
        </div>
        <div class="toast-close">&times;</div>
    `;

    container.appendChild(toast);

    const closeBtn = toast.querySelector(".toast-close");
    closeBtn.onclick = () => {
        toast.classList.add("hide");
        setTimeout(() => toast.remove(), 400);
    };

    setTimeout(() => {
        if (toast.parentNode) {
            toast.classList.add("hide");
            setTimeout(() => toast.remove(), 400);
        }
    }, 6000);
}


function updateBadges(notifCount, msgCount) {

    const notifLinks = document.querySelectorAll("a[href*=\"notifications\"]");
    notifLinks.forEach(link => {
        link.classList.add("nav-link-badge");
        let badge = link.querySelector(".badge-count");
        if (!badge) {
            badge = document.createElement("span");
            badge.className = "badge-count";
            link.appendChild(badge);
        }
        if (notifCount > 0) {
            link.classList.add("has-new");
            badge.textContent = notifCount;
        } else {
            link.classList.remove("has-new");
        }
    });

    const msgLinks = document.querySelectorAll("a[href*=\"inbox\"], a[href*=\"chat\"]");
    msgLinks.forEach(link => {
        link.classList.add("nav-link-badge");
        let badge = link.querySelector(".badge-count");
        if (!badge) {
            badge = document.createElement("span");
            badge.className = "badge-count";
            link.appendChild(badge);
        }
        if (msgCount > 0) {
            link.classList.add("has-new");
            badge.textContent = msgCount;
        } else {
            link.classList.remove("has-new");
        }
    });
}


let lastTotalNotifications = -1;
async function pollActivity() {
    const user = await getCurrentUser();
    if (!user) return;

    try {
        const resN = await apiGet(`${API}/notifications.php`);
        const notifications = (resN && Array.isArray(resN.data)) ? resN.data : (Array.isArray(resN) ? resN : []);
        const unreadN = notifications.filter(n => !n.is_read);

        if (lastTotalNotifications !== -1 && notifications.length > lastTotalNotifications) {
            const newest = notifications[0]; // Assuming newest first
            if (newest) showToast("New Activity", newest.message);
        }
        lastTotalNotifications = notifications.length;

        const resM = await apiGet(`${API}/messages.php`);
        const msgSyncCount = (resM && Array.isArray(resM)) ? resM.length : 0;
        
        updateBadges(unreadN.length, msgSyncCount);
    } catch (err) {}
}

if (!window.location.href.includes("login.html")) {
    window.addEventListener("DOMContentLoaded", () => {
        pollActivity();
        setInterval(pollActivity, 30000); // 30 seconds interval
    });
}

window.showToast = showToast;


