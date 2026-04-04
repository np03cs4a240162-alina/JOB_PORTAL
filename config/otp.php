<?php
/**
 * config/otp.php
 * DB-based OTP — compatible with PHP 7.4+
 */

function generateOtp($email) {
    $email = strtolower(trim($email));

    try {
        $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    } catch (Exception $e) {
        $otp = str_pad((string)mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    $db = getDB();

    $db->prepare('DELETE FROM otp_tokens WHERE email = ?')->execute([$email]);

    $db->prepare(
        'INSERT INTO otp_tokens (email, otp_code, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))'
    )->execute([$email, $otp]);

    // Send email and also log to file
    sendOtpEmail($email, $otp);

    return $otp;
}

function verifyOtp($email, $otp) {
    $email = strtolower(trim($email));
    $otp   = trim((string)$otp);

    if (!$email || strlen($otp) !== 6) return false;

    $db   = getDB();
    $stmt = $db->prepare(
        'SELECT id, otp_code FROM otp_tokens
         WHERE email = ? AND expires_at > NOW() LIMIT 1'
    );
    $stmt->execute([$email]);
    $row = $stmt->fetch();

    if (!$row) return false;

    return hash_equals((string)$row['otp_code'], $otp);
}

function clearOtp($email) {
    $email = strtolower(trim($email));
    getDB()->prepare('DELETE FROM otp_tokens WHERE email = ?')->execute([$email]);
}

function sendOtpEmail($toEmail, $otp) {
    $toEmail = strtolower(trim($toEmail));

    // Always log OTP to file so you can see it even if email fails
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $entry = sprintf(
        "[%s] TO: %s | OTP: %s | EXPIRES: %s\n",
        date('Y-m-d H:i:s'),
        $toEmail,
        $otp,
        date('Y-m-d H:i:s', strtotime('+10 minutes'))
    );
    file_put_contents($logDir . '/otp_debug.txt', $entry, FILE_APPEND | LOCK_EX);

    // Send real email — safely, so it doesn't crash if your laptop has no SMTP server
    $subject = 'Your JSTACK Verification Code';
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: JSTACK <noreply@jstack.com>\r\n";
    $headers .= "Reply-To: noreply@jstack.com\r\n";

    $message = '
    <html>
    <body style="font-family:Arial,sans-serif;background:#f0f4f8;padding:40px 20px;">
        <div style="max-width:440px;margin:auto;background:#fff;border-radius:12px;
                    padding:36px;border:1px solid #e2e8f0;">
            <h2 style="color:#0a66c2;margin:0 0 8px;">JSTACK</h2>
            <p style="color:#475569;margin:0 0 24px;font-size:14px;">Your verification code is:</p>
            <div style="font-size:36px;font-weight:700;letter-spacing:8px;color:#1e293b;text-align:center;padding:20px;background:#f8fafc;border-radius:8px;border:1px solid #e2e8f0;margin-bottom:24px;">
                ' . $otp . '
            </div>
            <p style="color:#64748b;font-size:13px;margin:0 0 8px;">This code expires in <strong>10 minutes</strong>.</p>
        </div>
    </body>
    </html>';

    // Temporarily disable custom error handler so mail() doesn't trigger a 500
    $prevHandler = set_error_handler(null);
    $sent = @mail($toEmail, $subject, $message, $headers);
    set_error_handler($prevHandler);

    // Update log with result
    $status = $sent ? 'SENT OK' : 'FAILED - Laptop SMTP not configured (Check log for OTP below)';
    file_put_contents($logDir . '/otp_debug.txt', "[mail()] $status\n", FILE_APPEND | LOCK_EX);

    return $sent;
}