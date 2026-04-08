

const authUrl = (action) => `${API}/auth.php?action=${action}`;

async function handleLogin() {
    clearAlert('alert-box');

    const email    = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;

    if (!email || !password) {
        showAlert('alert-box', 'Please enter both email and password.', 'error');
        return;
    }

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        showAlert('alert-box', 'Please enter a valid email address.', 'error');
        return;
    }

    setLoading('login-btn', true, 'Authenticating...');

    try {
        const res = await apiPost(authUrl('login'), { email, password });
        setLoading('login-btn', false);

        if (res && res.success) {
            showAlert('alert-box', 'Login successful! Redirecting...', 'success');

            const role  = res.user?.role || 'seeker';
            const paths = {
                admin:    `${BASE}/admin/dashboard.php`,
                employer: `${BASE}/employer/dashboard.php`,
                seeker:   `${BASE}/seeker/dashboard.php`,
            };

            setTimeout(() => {
                window.location.href = paths[role] || `${BASE}/index.html`;
            }, 1000);

        } else {
            showAlert('alert-box', res?.error || 'Invalid email or password.', 'error');
        }
    } catch (err) {
        setLoading('login-btn', false);
        showAlert('alert-box', 'Server connection failed. Is XAMPP running?', 'error');
    }
}

async function handleSendOtp() {
    clearAlert('alert-box');

    const name    = document.getElementById('name').value.trim();
    const email   = document.getElementById('email').value.trim();
    const pass    = document.getElementById('password').value;
    const confirm = document.getElementById('confirm-password').value;
    const role    = document.getElementById('role')?.value || 'seeker';

    if (!name || !email || !pass) {
        showAlert('alert-box', 'Please fill in all fields.', 'error');
        return;
    }

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        showAlert('alert-box', 'Please enter a valid email address.', 'error');
        return;
    }

    if (pass.length < 6) {
        showAlert('alert-box', 'Password must be at least 6 characters.', 'error');
        return;
    }

    if (pass !== confirm) {
        showAlert('alert-box', 'Passwords do not match.', 'error');
        return;
    }

    setLoading('register-btn', true, 'Sending Code...');

    const res = await apiPost(authUrl('send-otp'), { name, email, password: pass, role });
    setLoading('register-btn', false);

    if (res && res.success) {
        if (typeof toggleSteps === 'function') toggleSteps('otp');

        if (res.dev_otp) {
            const otpInput = document.getElementById('otp');
            if (otpInput) otpInput.value = res.dev_otp;
            showAlert('alert-box', `Dev mode — OTP auto-filled: ${res.dev_otp}`, 'info');
            if (window.showToast) showToast('System', `OTP Sent: ${res.dev_otp}`, 'fas fa-key');
        } else {
            showAlert('alert-box', 'Verification code sent to your email!', 'success');
            if (window.showToast) showToast('Registration', 'A 6-digit verification code has been sent.', 'fas fa-envelope');
        }
    } else {
        showAlert('alert-box', res?.error || 'Failed to send code.', 'error');
    }
}

async function handleVerifyOtp() {
    clearAlert('alert-box');

    const email = document.getElementById('email').value.trim();
    const otp   = document.getElementById('otp')?.value.trim();

    if (!otp || otp.length < 6) {
        showAlert('alert-box', 'Please enter the 6-digit code.', 'error');
        return;
    }

    setLoading('verify-btn', true, 'Verifying...');

    const res = await apiPost(authUrl('verify-otp'), { email, otp });
    setLoading('verify-btn', false);

    if (res && res.success) {
        showAlert('alert-box', 'Account created! Redirecting to login...', 'success');
        setTimeout(() => {
            window.location.href = `${BASE}/auth/login.html`;
        }, 1500);
    } else {
        showAlert('alert-box', res?.error || 'Verification failed. Try again.', 'error');
    }
}

async function handleForgotPasswordRequest() {
    clearAlert('alert-box');

    const email = document.getElementById('email').value.trim();

    if (!email) {
        showAlert('alert-box', 'Please enter your email address.', 'error');
        return;
    }

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        showAlert('alert-box', 'Please enter a valid email address.', 'error');
        return;
    }

    setLoading('send-otp-btn', true, 'Sending reset code...');

    const res = await apiPost(authUrl('forgot-password'), { email });
    setLoading('send-otp-btn', false);

    if (res && res.success) {
        if (typeof toggleResetSteps === 'function') toggleResetSteps('reset');

        if (res.dev_otp) {
            const otpField = document.getElementById('otp');
            if (otpField) otpField.value = res.dev_otp;
            showAlert('alert-box', `Dev mode — Reset code auto-filled: ${res.dev_otp}`, 'info');
            if (window.showToast) showToast('System', `Reset Code: ${res.dev_otp}`, 'fas fa-key');
        } else {
            showAlert('alert-box', 'Reset code sent! Check your inbox.', 'success');
            if (window.showToast) showToast('Identity', 'A password reset code has been sent.', 'fas fa-user-shield');
        }
    } else {
        showAlert('alert-box', res?.error || 'Email not found.', 'error');
    }
}

async function handleResetPassword() {
    clearAlert('alert-box');

    const email   = document.getElementById('email').value.trim();
    const otp     = document.getElementById('otp').value.trim();
    const newPass = document.getElementById('new-password').value;
    const confirm = document.getElementById('confirm-password').value;

    if (!otp || otp.length < 6) {
        showAlert('alert-box', 'Please enter the 6-digit reset code.', 'error');
        return;
    }

    if (newPass.length < 6) {
        showAlert('alert-box', 'Password must be at least 6 characters.', 'error');
        return;
    }

    if (newPass !== confirm) {
        showAlert('alert-box', 'Passwords do not match.', 'error');
        return;
    }

    setLoading('reset-btn', true, 'Resetting password...');

    const res = await apiPost(authUrl('reset-password'), {
        email,
        otp,
        new_password: newPass
    });

    setLoading('reset-btn', false);

    if (res && res.success) {
        showAlert('alert-box', 'Password reset! Redirecting to login...', 'success');
        setTimeout(() => {
            window.location.href = `${BASE}/auth/login.html`;
        }, 1500);
    } else {
        showAlert('alert-box', res?.error || 'Reset failed. Try again.', 'error');
    }
}

