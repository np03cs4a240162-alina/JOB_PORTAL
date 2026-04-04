// ── Job Fetching ──────────────────────────────────────────────────────────────
async function loadAllJobs(keyword='', category='', location='') {
    let url = `${API}/jobs.php?action=search`;
    if (keyword)  url += `&keyword=${encodeURIComponent(keyword)}`;
    if (category && category !== 'All') url += `&category=${encodeURIComponent(category)}`;
    if (location) url += `&location=${encodeURIComponent(location)}`;
    
    const res = await apiGet(url);
    // Standardize return to ensure 'data' is always an array for .map()
    return res.success ? res : { success: false, data: [] };
}

// ── Ajax Autocomplete ─────────────────────────────────────────────────────────
async function setupAutocomplete(inputId, listId) {
    const input = document.getElementById(inputId);
    const list  = document.getElementById(listId);
    if (!input || !list) return;

    input.addEventListener('input', async () => {
        const q = input.value.trim();
        list.innerHTML = '';
        if (q.length < 2) { list.style.display = 'none'; return; }

        // Updated: Backend now returns {success: true, data: [...]}
        const res = await apiGet(`${API}/jobs.php?action=autocomplete&q=${encodeURIComponent(q)}`);
        
        if (res.success && Array.isArray(res.data) && res.data.length) {
            list.style.display = 'block';
            res.data.forEach(title => {
                const li = document.createElement('li');
                li.textContent = title;
                li.className = 'autocomplete-item'; // Use CSS class for cleaner code
                li.onclick = () => { 
                    input.value = title; 
                    list.style.display = 'none'; 
                };
                list.appendChild(li);
            });
        } else { 
            list.style.display = 'none'; 
        }
    });

    document.addEventListener('click', e => { 
        if (!input.contains(e.target)) list.style.display = 'none'; 
    });
}

// ── Applications ──────────────────────────────────────────────────────────────
/**
 * Submits a job application with an optional resume ID
 */
async function applyToJob(jobId, resumeId = 0, resumeNote = '') {
    // Matches the updated backend 'action=apply' logic
    return apiPost(`${API}/applications.php?action=apply`, { 
        job_id: jobId, 
        resume_id: resumeId,
        resume_note: resumeNote 
    });
}

async function getMyApplications() { 
    return apiGet(`${API}/applications.php`); // Backend handles role-based filtering
}

async function updateApplicationStatus(appId, status) { 
    // Matches 'action=update-status' in the updated backend
    return apiPost(`${API}/applications.php?action=update-status`, { 
        id: appId, 
        status: status 
    });
}

// ── Saved Jobs ────────────────────────────────────────────────────────────────
async function saveJobAction(jobId, btn) {
    let isSeeker = false;
    if (typeof currentUser !== 'undefined' && currentUser && currentUser.role === 'seeker') isSeeker = true;
    if (typeof state !== 'undefined' && state && state.user && state.user.role === 'seeker') isSeeker = true;

    if (!isSeeker) {
        showAlert('alert-box', 'Please login as a seeker to save jobs.', 'error');
        return;
    }

    btn.disabled = true;
    const res = await apiPost(`${API}/saved_jobs.php`, { job_id: jobId });
    
    if (res.success) {
        btn.innerHTML = '<i class="fas fa-bookmark"></i> Saved';
        btn.classList.add('btn-saved'); // Better than inline styles
    } else {
        btn.disabled = false;
        // Logic for "Already Saved" (409 conflict)
        if (res.status === 409) btn.innerHTML = '<i class="fas fa-bookmark"></i> Saved';
        else alert(res.error || "Failed to save job.");
    }
}

// ── Render Card ───────────────────────────────────────────────────────────────
function renderJobCard(job) {
    const closed  = job.status === 'closed';
    const company = escHtml(job.employer_name || job.company || 'JSTACK');
    let isSeeker = false;
    if (typeof currentUser !== 'undefined' && currentUser && currentUser.role === 'seeker') isSeeker = true;
    if (typeof state !== 'undefined' && state && state.user && state.user.role === 'seeker') isSeeker = true;
    
    const saveBtn = isSeeker ? 
        `<button class="btn-icon-only" title="Save Job" onclick="saveJobAction(${job.id}, this)">
            <i class="far fa-bookmark"></i>
         </button>` : '';

    return `
    <div class="job-card">
        <div class="job-card-header">
            <h3>${escHtml(job.title)}</h3>
            ${saveBtn}
        </div>
        <p class="job-company"><strong>${company}</strong> — ${escHtml(job.location || 'Remote')}</p>
        <p class="job-salary">${escHtml(job.salary || 'Negotiable')}</p>
        <div class="job-tags">
            <span class="tag">${escHtml(job.category || 'Other')}</span>
            <span class="job-date">${new Date(job.created_at).toLocaleDateString()}</span>
        </div>
        <div class="job-actions">
            <button class="btn-full" onclick="location.href='${BASE}/jobs/detail.html?id=${job.id}'" ${closed ? 'disabled' : ''}>
                ${closed ? 'Closed' : 'View Details'}
            </button>
        </div>
    </div>`;
}