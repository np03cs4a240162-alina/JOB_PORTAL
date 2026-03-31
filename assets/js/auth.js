/**
 * ── LOGIN ──
 * Handles user authentication and role-based redirection
 */
async function handleLogin(e) {
    e.preventDefault();
    clearAlert('alert-box');
    
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;

    if (!email || !password) {
        showAlert('alert-box', 'Please enter both email and password.', 'error');
        return;
    }

    setLoading('login-btn', true, 'Logging in...');
    
    // apiPost is defined in main.js
    const result = await apiPost('/auth.php?action=login', { email, password });
    
    setLoading('login-btn', false);

    if (result.success) {
        showAlert('alert-box', 'Login successful! Redirecting...', 'success');
        
        // Ensure role-based paths match your folder structure
        const paths = { 
            admin:    BASE + '/admin/dashboard.html', 
            employer: BASE + '/employer/dashboard.html', 
            seeker:   BASE + '/seeker/dashboard.html' 
        };
        
        setTimeout(() => {
            window.location.href = paths[result.user.role] || BASE + '/index.html';
        }, 800);
    } else {
        showAlert('alert-box', result.error || 'Invalid credentials.', 'error');
    }
}

/**
 * ── REGISTER STEP 1: Send OTP ──
 * Validates inputs and triggers the verification email
 */
async function handleSendOtp(e) {
    e.preventDefault();
    clearAlert('alert-box');

    const name = document.getElementById('name').value.trim();
    const email = document.getElementById('email').value.trim();
    const pass = document.getElementById('password').value;
    const confirm = document.getElementById('confirm-password')?.value;
    const role = document.getElementById('role')?.value || 'seeker';

    // Client-side Validation
    if (pass !== confirm) { 
        showAlert('alert-box', 'Passwords do not match.', 'error'); 
        return; 
    }
    if (pass.length < 6) {
        showAlert('alert-box', 'Password must be at least 6 characters.', 'error');
        return;
    }

    setLoading('register-btn', true, 'Sending OTP...');

    const result = await apiPost('/auth.php?action=send-otp', {
        name, email, password: pass, role
    });

    setLoading('register-btn', false);

    if (result.success) {
        // Use the toggleSteps helper from register.html
        if (typeof toggleSteps === 'function') {
            toggleSteps('otp');
        } else {
            document.getElementById('step-register').style.display = 'none';
            document.getElementById('step-otp').style.display = 'block';
        }

        // Auto-fill OTP in Dev Mode
        if (result.dev_otp) {
            document.getElementById('otp').value = result.dev_otp;
            showAlert('alert-box', `<strong>DEV MODE:</strong> Code auto-filled from log.`, 'info');
        } else {
            showAlert('alert-box', result.message || 'Verification code sent!', 'success');
        }
    } else {
        showAlert('alert-box', result.error || 'Could not send OTP.', 'error');
    }
}

/**
 * ── REGISTER STEP 2: Verify OTP ──
 * Completes account creation after code verification
 */
async function handleVerifyOtp(e) {
    e.preventDefault();
    clearAlert('alert-box');

    const email = document.getElementById('email').value.trim();
    const otp = document.getElementById('otp').value.replace(/\s/g, ''); // Remove any accidental spaces

    if (otp.length !== 6) {
        showAlert('alert-box', 'Please enter a valid 6-digit code.', 'error');
        return;
    }

    setLoading('verify-btn', true, 'Verifying...');

    const result = await apiPost('/auth.php?action=verify-otp', { email, otp });

    setLoading('verify-btn', false);

    if (result.success) {
        showAlert('alert-box', 'Account created! Redirecting to login...', 'success');
        setTimeout(() => {
            window.location.href = BASE + '/auth/login.html';
        }, 2000);
    } else {
        showAlert('alert-box', result.error || 'OTP verification failed.', 'error');
    }
}