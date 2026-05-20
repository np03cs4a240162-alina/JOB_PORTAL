<?php
// Automated auth flow test using dev OTP if returned by server
$base = 'http://localhost/JOB_PORTAL/api';
$cookieJar = __DIR__ . '/auth_cookies.txt';
@unlink($cookieJar);

function req($method, $url, $data = null, $cookieJar = null, $headers = []) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    if ($cookieJar) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
    }
    if ($data !== null) {
        $json = json_encode($data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        $headers[] = 'Content-Type: application/json';
    }
    if (!empty($headers)) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $resp = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    $decoded = json_decode($resp, true);
    return ['code'=>$info['http_code'],'raw'=>$resp,'json'=>$decoded];
}

$ts = time();
$email = "qa_test_{$ts}@example.com";
$pass = 'Password123!';
$name = 'QA Tester';
$role = 'seeker';

echo "Starting auth flow for $email\n";

// 1: send-otp
$send = req('POST', "$base/auth.php?action=send-otp", ['name'=>$name,'email'=>$email,'password'=>$pass,'role'=>$role], $cookieJar);
echo "send-otp: {$send['code']}\n";
if ($send['json'] && isset($send['json']['dev_otp'])) {
    $otp = $send['json']['dev_otp'];
    echo "dev_otp received: $otp\n";
} else {
    echo "No dev_otp returned; cannot auto-verify.\n";
    file_put_contents(__DIR__.'/qa_auth_flow_report.json', json_encode(['result'=>'no_dev_otp','send'=>$send], JSON_PRETTY_PRINT));
    exit(0);
}

// 2: verify-otp
$verify = req('POST', "$base/auth.php?action=verify-otp", ['email'=>$email,'otp'=>$otp], $cookieJar);
echo "verify-otp: {$verify['code']}\n";

// 3: check me
$me = req('GET', "$base/auth.php?action=me", null, $cookieJar);
echo "me: {$me['code']}\n";

// 4: login (password)
$login = req('POST', "$base/auth.php?action=login", ['email'=>$email,'password'=>$pass], $cookieJar);
echo "login: {$login['code']}\n";

// 5: forgot-otp-send
$forgot = req('POST', "$base/auth.php?action=forgot-otp-send", ['email'=>$email], $cookieJar);
echo "forgot-otp-send: {$forgot['code']}\n";
$resetOtp = $forgot['json']['dev_otp'] ?? null;
if ($resetOtp) echo "forgot dev otp: $resetOtp\n";

// 6: forgot-otp-verify (set new password)
if ($resetOtp) {
    $new = 'NewPass123!';
    $reset = req('POST', "$base/auth.php?action=forgot-otp-verify", ['email'=>$email,'otp'=>$resetOtp,'newpass'=>$new,'confirm'=>$new], $cookieJar);
    echo "forgot-otp-verify: {$reset['code']}\n";
    // try login with new password
    $login2 = req('POST', "$base/auth.php?action=login", ['email'=>$email,'password'=>$new], $cookieJar);
    echo "login with new password: {$login2['code']}\n";
}

file_put_contents(__DIR__.'/qa_auth_flow_report.json', json_encode(['send'=>$send,'verify'=>$verify,'me'=>$me,'login'=>$login,'forgot'=>$forgot,'reset'=>$reset ?? null,'login2'=>$login2 ?? null], JSON_PRETTY_PRINT));
echo "Auth flow finished. Report saved to tests/qa_auth_flow_report.json\n";
