/**
 * SmartJob Nepal — Complete E2E OTP & Authentication Orchestrator
 */

/**
 * ── METHOD 1: STANDARD PASSWORD LOGIN ──
 * Handles user authentication via standard email and password
 */
async function handleLogin(e) {
    e.preventDefault();
    
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;

    if (!email || !password) {
        showAlert('alert-box', 'Please enter both email and password.', 'error');
        return;
    }

    setLoading('login-btn', true, 'Logging in...');
    
    const result = await apiPost('/auth.php?action=login', { email, password });
    
    setLoading('login-btn', false);

    if (result.success) {
        showAlert('alert-box', 'Login successful! Redirecting...', 'success');
        
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
 * ── SIGNUP STEP 1: Send Registration OTP ──
 * Validates inputs and triggers the verification email
 */
async function handleSendOtp(e) {
    e.preventDefault();

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
        if (typeof toggleSteps === 'function') {
            toggleSteps('otp');
        } else {
            document.getElementById('step-register').style.display = 'none';
            document.getElementById('step-otp').style.display = 'block';
        }

        // Start 30s Cooldown Countdown
        startOtpCooldown();

        // Auto-fill OTP in Dev Mode Fallback
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
 * ── SIGNUP STEP 2: Verify Registration OTP & Create Account ──
 */
async function handleVerifyOtp(e) {
    e.preventDefault();

    const email = document.getElementById('email').value.trim();
    const otp = document.getElementById('otp').value.replace(/\s/g, ''); 

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

/**
 * ── SIGNUP STEP 3: Resend Registration OTP ──
 */
async function handleResendOtp(e) {
    e.preventDefault();

    const name = document.getElementById('name').value.trim();
    const email = document.getElementById('email').value.trim();
    const pass = document.getElementById('password').value;
    const role = document.getElementById('role')?.value || 'seeker';

    setLoading('resend-btn', true, 'Resending...');

    const result = await apiPost('/auth.php?action=send-otp', {
        name, email, password: pass, role
    });

    setLoading('resend-btn', false);

    if (result.success) {
        startOtpCooldown();
        if (result.dev_otp) {
            document.getElementById('otp').value = result.dev_otp;
            showAlert('alert-box', `<strong>DEV MODE:</strong> Code auto-filled from log.`, 'info');
        } else {
            showAlert('alert-box', result.message || 'New verification code sent!', 'success');
        }
    } else {
        showAlert('alert-box', result.error || 'Could not resend OTP.', 'error');
    }
}

/**
 * ── METHOD 2: PASSWORDLESS SECURE OTP LOGIN (STEP 1: SEND CODE) ──
 */
async function handleLoginOtpSend(e) {
    e.preventDefault();

    const email = document.getElementById('email-otp').value.trim();

    if (!email) {
        showAlert('alert-box', 'Please enter your email address.', 'error');
        return;
    }

    setLoading('login-otp-send-btn', true, 'Sending OTP...');

    const result = await apiPost('/auth.php?action=login-otp-send', { email });

    setLoading('login-otp-send-btn', false);

    if (result.success) {
        // Toggle Step Views
        document.getElementById('login-otp-step1').style.display = 'none';
        document.getElementById('login-otp-step2').style.display = 'block';

        // Start 30s Cooldown Countdown
        startLoginOtpCooldown();

        // Auto-fill OTP in Dev Mode Fallback
        if (result.dev_otp) {
            document.getElementById('otp-code').value = result.dev_otp;
            showAlert('alert-box', `<strong>DEV MODE:</strong> Code auto-filled from log.`, 'info');
        } else {
            showAlert('alert-box', result.message || 'Verification code sent!', 'success');
        }
    } else {
        showAlert('alert-box', result.error || 'Could not send OTP.', 'error');
    }
}

/**
 * ── METHOD 2: PASSWORDLESS SECURE OTP LOGIN (STEP 2: VERIFY CODE) ──
 */
async function handleLoginOtpVerify(e) {
    e.preventDefault();

    const email = document.getElementById('email-otp').value.trim();
    const otp = document.getElementById('otp-code').value.replace(/\s/g, '');

    if (otp.length !== 6) {
        showAlert('alert-box', 'Please enter a valid 6-digit code.', 'error');
        return;
    }

    setLoading('login-otp-verify-btn', true, 'Signing In...');

    const result = await apiPost('/auth.php?action=login-otp-verify', { email, otp });

    setLoading('login-otp-verify-btn', false);

    if (result.success) {
        showAlert('alert-box', 'Verification successful! Redirecting...', 'success');
        
        const paths = { 
            admin:    BASE + '/admin/dashboard.html', 
            employer: BASE + '/employer/dashboard.html', 
            seeker:   BASE + '/seeker/dashboard.html' 
        };
        
        setTimeout(() => {
            window.location.href = paths[result.user.role] || BASE + '/index.html';
        }, 800);
    } else {
        showAlert('alert-box', result.error || 'OTP verification failed.', 'error');
    }
}

/**
 * ── METHOD 2: PASSWORDLESS SECURE OTP LOGIN (STEP 3: RESEND CODE) ──
 */
async function handleResendLoginOtp(e) {
    e.preventDefault();

    const email = document.getElementById('email-otp').value.trim();

    setLoading('resend-login-otp-btn', true, 'Resending...');

    const result = await apiPost('/auth.php?action=login-otp-send', { email });

    setLoading('resend-login-otp-btn', false);

    if (result.success) {
        startLoginOtpCooldown();
        if (result.dev_otp) {
            document.getElementById('otp-code').value = result.dev_otp;
            showAlert('alert-box', `<strong>DEV MODE:</strong> Code auto-filled from log.`, 'info');
        } else {
            showAlert('alert-box', result.message || 'New verification code sent!', 'success');
        }
    } else {
        showAlert('alert-box', result.error || 'Could not resend OTP.', 'error');
    }
}

/**
 * ── REGISTRATION COOLDOWN COUNTDOWN TIMER ──
 */
let otpTimerInterval = null;
function startOtpCooldown() {
    const resendBtn = document.getElementById('resend-btn');
    const timerSpan = document.getElementById('cooldown-timer');
    if (!resendBtn || !timerSpan) return;
    
    let seconds = 30;
    resendBtn.disabled = true;
    timerSpan.innerText = `(${seconds}s)`;
    
    clearInterval(otpTimerInterval);
    otpTimerInterval = setInterval(() => {
        seconds--;
        if (seconds <= 0) {
            clearInterval(otpTimerInterval);
            resendBtn.disabled = false;
            timerSpan.innerText = '';
        } else {
            timerSpan.innerText = `(${seconds}s)`;
        }
    }, 1000);
}

/**
 * ── PASSWORDLESS LOGIN COOLDOWN COUNTDOWN TIMER ──
 */
let loginOtpTimerInterval = null;
function startLoginOtpCooldown() {
    const resendBtn = document.getElementById('resend-login-otp-btn');
    const timerSpan = document.getElementById('login-cooldown-timer');
    if (!resendBtn || !timerSpan) return;
    
    let seconds = 30;
    resendBtn.disabled = true;
    timerSpan.innerText = `(${seconds}s)`;
    
    clearInterval(loginOtpTimerInterval);
    loginOtpTimerInterval = setInterval(() => {
        seconds--;
        if (seconds <= 0) {
            clearInterval(loginOtpTimerInterval);
            resendBtn.disabled = false;
            timerSpan.innerText = '';
        } else {
            timerSpan.innerText = `(${seconds}s)`;
        }
    }, 1000);
}

// ── FORGOT PASSWORD OTP FLOW ──
// Step 1: Send OTP to email
async function handleForgotOtpSend(e) {
    e.preventDefault();
    const email = document.getElementById('forgot-email').value.trim();
    if (!email) { showAlert('alert-box', 'Please enter your email address.', 'error'); return; }
    setLoading('forgot-send-btn', true, 'Sending OTP...');
    const result = await apiPost('/auth.php?action=forgot-otp-send', { email });
    setLoading('forgot-send-btn', false);
    if (result.success) {
        // Reveal verification step
        document.getElementById('forgot-step1').style.display = 'none';
        document.getElementById('forgot-step2').style.display = 'block';
        startForgotOtpCooldown();
        if (result.dev_otp) {
            document.getElementById('forgot-otp').value = result.dev_otp;
            showAlert('alert-box', `<strong>DEV MODE:</strong> Code auto-filled from log.`, 'info');
        } else {
            showAlert('alert-box', result.message || 'OTP sent to your email.', 'success');
        }
    } else {
        showAlert('alert-box', result.error || 'Could not send OTP.', 'error');
    }
}

// Step 2: Verify OTP and reset password
async function handleForgotPasswordVerify(e) {
    e.preventDefault();
    const email = document.getElementById('forgot-email').value.trim();
    const otp = document.getElementById('forgot-otp').value.replace(/\s/g, '');
    const newpass = document.getElementById('newpass').value;
    const confirm = document.getElementById('confirm').value;
    if (!email || !otp || !newpass || !confirm) { showAlert('alert-box', 'All fields are required.', 'error'); return; }
    if (newpass !== confirm) { showAlert('alert-box', 'Passwords do not match.', 'error'); return; }
    if (newpass.length < 6) { showAlert('alert-box', 'Password must be at least 6 characters.', 'error'); return; }
    setLoading('forgot-verify-btn', true, 'Resetting...');
    const result = await apiPost('/auth.php?action=forgot-otp-verify', { email, otp, newpass, confirm });
    setLoading('forgot-verify-btn', false);
    if (result.success) {
        showAlert('alert-box', result.message || 'Password updated. You can now log in.', 'success');
        setTimeout(() => { window.location.href = BASE + '/auth/login.html'; }, 2000);
    } else {
        showAlert('alert-box', result.error || 'OTP verification failed.', 'error');
    }
}

// Resend OTP for Forgot Password
async function handleResendForgotOtp(e) {
    e.preventDefault();
    const email = document.getElementById('forgot-email').value.trim();
    if (!email) { showAlert('alert-box', 'Please enter your email address.', 'error'); return; }
    setLoading('resend-forgot-btn', true, 'Resending...');
    const result = await apiPost('/auth.php?action=forgot-otp-send', { email });
    setLoading('resend-forgot-btn', false);
    if (result.success) {
        startForgotOtpCooldown();
        if (result.dev_otp) {
            document.getElementById('forgot-otp').value = result.dev_otp;
            showAlert('alert-box', `<strong>DEV MODE:</strong> Code auto-filled from log.`, 'info');
        } else {
            showAlert('alert-box', result.message || 'New OTP sent.', 'success');
        }
    } else {
        showAlert('alert-box', result.error || 'Could not resend OTP.', 'error');
    }
}

// Countdown timer for Forgot Password OTP resend
let forgotOtpTimerInterval = null;
function startForgotOtpCooldown() {
    const resendBtn = document.getElementById('resend-forgot-btn');
    const timerSpan = document.getElementById('forgot-cooldown-timer');
    if (!resendBtn || !timerSpan) return;
    let seconds = 30;
    resendBtn.disabled = true;
    timerSpan.innerText = `(${seconds}s)`;
    clearInterval(forgotOtpTimerInterval);
    forgotOtpTimerInterval = setInterval(() => {
        seconds--;
        if (seconds <= 0) {
            clearInterval(forgotOtpTimerInterval);
            resendBtn.disabled = false;
            timerSpan.innerText = '';
        } else {
            timerSpan.innerText = `(${seconds}s)`;
        }
    }, 1000);
}