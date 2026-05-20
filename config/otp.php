<?php
/**
 * SmartJob Nepal — Secure SMTP & Database OTP Manager
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * 1. Safe Environment Variables (.env) Parser
 */
function loadEnv(): array {
    static $env = null;
    if ($env !== null) return $env;
    
    $envFile = __DIR__ . '/../.env';
    if (!file_exists($envFile)) {
        return [];
    }
    
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $parsed = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            
            // Remove wrapping single/double quotes
            if (preg_match('/^"((?:[^"\\\\]|\\\\.)*)"$/', $value, $matches)) {
                $value = stripcslashes($matches[1]);
            } elseif (preg_match("/^'((?:[^'\\\\]|\\\\.)*)'$/", $value, $matches)) {
                $value = stripcslashes($matches[1]);
            }
            $parsed[$key] = $value;
        }
    }
    $env = $parsed;
    return $env;
}

/**
 * 2. Secure OTP Generation (Database-Backed)
 */
function generateOtp(string $email, string $type = 'verification'): string {
    $db = getDB();
    
    // Cryptographically secure 6-digit OTP code
    $otp = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    
    // 5-minute expiry limit (300 seconds)
    $expiry = date('Y-m-d H:i:s', time() + 300);
    
    // Clear any previous pending codes of this type for the email
    $stmt = $db->prepare("DELETE FROM email_otps WHERE email = ? AND type = ?");
    $stmt->execute([$email, $type]);
    
    // Insert new record with 0 attempts
    $stmt = $db->prepare("INSERT INTO email_otps (email, code, type, expiry, attempts) VALUES (?, ?, ?, ?, 0)");
    $stmt->execute([$email, $otp, $type, $expiry]);
    
    return $otp;
}

/**
 * 3. Unified OTP Verification Logic
 * Validates expiration, verifies code securely, increments failed tries, and enforces a 3-attempts limit
 */
function verifyOtp(string $email, string $otp, string $type = 'verification'): array {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT * FROM email_otps WHERE email = ? AND type = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$email, $type]);
    $stored = $stmt->fetch();
    
    if (!$stored) {
        return ['success' => false, 'error' => 'No active verification code found. Please send code again.'];
    }
    
    // Check brute-force attempts lockout (Max 3 attempts)
    if ((int)$stored['attempts'] >= 3) {
        // Permanently invalidate the OTP on lock
        $db->prepare("DELETE FROM email_otps WHERE id = ?")->execute([$stored['id']]);
        return ['success' => false, 'error' => 'Too many failed attempts. Code locked. Please request a new code.'];
    }
    
    // Check expiration (5 minutes)
    if (time() > strtotime($stored['expiry'])) {
        $db->prepare("DELETE FROM email_otps WHERE id = ?")->execute([$stored['id']]);
        return ['success' => false, 'error' => 'The verification code has expired (5-minute limit). Please request a new one.'];
    }
    
    // Compare code securely with timing-attack protection
    if (hash_equals($stored['code'], $otp)) {
        // Success: Clear OTP immediately so it cannot be re-used
        $db->prepare("DELETE FROM email_otps WHERE id = ?")->execute([$stored['id']]);
        return ['success' => true];
    } else {
        // Increment attempts on failure
        $newAttempts = (int)$stored['attempts'] + 1;
        $db->prepare("UPDATE email_otps SET attempts = ? WHERE id = ?")->execute([$newAttempts, $stored['id']]);
        
        $remaining = 3 - $newAttempts;
        if ($remaining <= 0) {
            $db->prepare("DELETE FROM email_otps WHERE id = ?")->execute([$stored['id']]);
            return ['success' => false, 'error' => 'Too many failed attempts. Code locked. Please request a new code.'];
        }
        
        return ['success' => false, 'error' => "Invalid verification code. {$remaining} attempt(s) remaining."];
    }
}

/**
 * 4. Resend Rate-Limiting Cooldown Check (30 seconds)
 * Checks if a code was requested for this email within the last 30 seconds.
 */
function checkOtpRateLimit(string $email, string $type): ?int {
    $db = getDB();
    $stmt = $db->prepare("SELECT (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(created_at)) AS elapsed FROM email_otps WHERE email = ? AND type = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$email, $type]);
    $lastOtp = $stmt->fetch();
    
    if ($lastOtp && $lastOtp['elapsed'] !== null) {
        $elapsed = (int)$lastOtp['elapsed'];
        if ($elapsed < 30) {
            return 30 - $elapsed;
        }
    }
    return null;
}

/**
 * 5. Secure HTML Email Dispatcher featuring PHPMailer & Dev Mode Fallbacks
 */
function sendOtpEmail(string $toEmail, string $otp, string $type = 'verification'): array {
    $db = getDB();
    
    // Load config environment values
    $env = loadEnv();
    $smtpEnabled = filter_var($env['SMTP_ENABLED'] ?? false, FILTER_VALIDATE_BOOLEAN);
    
    // Build Premium HTML Email Template
    $subject = 'SmartJob Nepal — Secure Verification Code';
    $messageHtml = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='utf-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <style>
            body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; background-color: #f8fafc; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 40px auto; padding: 48px; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 20px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.02); }
            .logo { font-size: 28px; font-weight: 900; letter-spacing: -1.5px; color: #0f172a; text-align: center; margin-bottom: 32px; }
            .logo span { color: #f47c48; }
            .heading { font-size: 22px; font-weight: 800; color: #0f172a; text-align: center; margin-bottom: 12px; letter-spacing: -0.5px; }
            .subtext { font-size: 15px; color: #475569; text-align: center; margin-bottom: 36px; font-weight: 500; }
            .otp-box { font-size: 38px; font-weight: 900; letter-spacing: 12px; color: #0f172a; background-color: #f8fafc; border: 2px solid #e2e8f0; border-radius: 16px; padding: 24px; text-align: center; font-family: 'Courier New', Courier, monospace; margin: 36px 0; }
            .notice { font-size: 13px; color: #94a3b8; text-align: center; line-height: 1.5; margin-top: 36px; font-weight: 500; }
            .divider { border-top: 1px solid #e2e8f0; margin: 32px 0 24px; }
            .footer { font-size: 12px; color: #64748b; text-align: center; font-weight: 600; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='logo'>Smart<span>Job</span></div>
            <div class='heading'>Verification Code</div>
            <div class='subtext'>Use the secure dynamic verification code below to authorize your sign-in or account creation:</div>
            
            <div class='otp-box'>{$otp}</div>
            
            <p class='notice'>
                This one-time passcode is valid for exactly <strong>5 minutes</strong> and will lock after 3 failed attempts. If you did not make this request, you can safely ignore this email.
            </p>
            <div class='divider'></div>
            <div class='footer'>&copy; 2026 SmartJob Nepal. All rights reserved.</div>
        </div>
    </body>
    </html>";

    // 1. Live SMTP Mode using PHPMailer
    if ($smtpEnabled) {
        try {
            require_once __DIR__ . '/../vendor/autoload.php';
            
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = $env['SMTP_HOST']   ?? 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $env['SMTP_USER']   ?? '';
            $mail->Password   = $env['SMTP_PASS']   ?? '';
            $mail->SMTPSecure = ($env['SMTP_SECURE'] ?? 'tls') === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = (int)($env['SMTP_PORT'] ?? 587);
            
            // Set sender & recipient
            $mail->setFrom($env['SMTP_USER'] ?? 'no-reply@smartjob.com', $env['SMTP_FROM_NAME'] ?? 'SmartJob Nepal');
            $mail->addAddress($toEmail);
            
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $messageHtml;
            
            $mail->send();
            
            // Write success log
            $logMessage = "[" . date('Y-m-d H:i:s') . "] SMTP Success: Sent live OTP to $toEmail (PHPMailer)\n";
            file_put_contents(__DIR__ . '/../otp_debug.txt', $logMessage, FILE_APPEND);
            
            return ['success' => true];
        } catch (Exception $e) {
            $errorMsg = "[" . date('Y-m-d H:i:s') . "] SMTP Failure to $toEmail: " . $mail->ErrorInfo . " | Error: " . $e->getMessage() . "\n";
            file_put_contents(__DIR__ . '/../otp_debug.txt', $errorMsg, FILE_APPEND);
            return ['success' => false, 'error' => 'SMTP delivery failed. Check credentials in .env file.'];
        }
    }
    
    // 2. Dev Mode Fallback: Log directly to otp_debug.txt
    $logMessage = "[" . date('Y-m-d H:i:s') . "] To: $toEmail | OTP: $otp\n";
    file_put_contents(__DIR__ . '/../otp_debug.txt', $logMessage, FILE_APPEND);
    
    return ['success' => true, 'dev' => true];
}