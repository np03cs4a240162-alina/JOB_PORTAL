<?php
// E2E RBAC test script for the NewJob project.
// Usage: set environment variables and run `php tests/rbac_e2e.php`
// Required env vars: BASE_URL, EMPLOYER_EMAIL, EMPLOYER_PASS, SEEKER_EMAIL, SEEKER_PASS

function env($key, $fallback = null) {
    $v = getenv($key);
    return $v !== false ? $v : $fallback;
}

$BASE = rtrim(env('BASE_URL', 'http://localhost/NewJob'), '/');
$EMP_EMAIL = env('EMPLOYER_EMAIL');
$EMP_PASS  = env('EMPLOYER_PASS');
$SEEK_EMAIL = env('SEEKER_EMAIL');
$SEEK_PASS  = env('SEEKER_PASS');

if (!$EMP_EMAIL || !$EMP_PASS || !$SEEK_EMAIL || !$SEEK_PASS) {
    fwrite(STDERR, "Please set EMPLOYER_EMAIL, EMPLOYER_PASS, SEEKER_EMAIL, SEEKER_PASS environment variables.\n");
    exit(2);
}

function http_request($url, $method = 'GET', $data = null, $cookieFile = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    if ($data !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }
    $res = curl_exec($ch);
    if ($res === false) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new Exception("Curl error: $err");
    }
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $json = json_decode($res, true);
    return ['code' => $code, 'raw' => $res, 'json' => $json];
}

// temp cookie files
$empCookies = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'nj_emp_cookies.txt';
$seekCookies = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'nj_seek_cookies.txt';

echo "Base URL: $BASE\n";

// 1) Employer login
echo "[1] Employer login...\n";
$r = http_request($BASE . '/api/auth.php?action=login', 'POST', ['email' => $EMP_EMAIL, 'password' => $EMP_PASS], $empCookies);
if (!($r['json']['success'] ?? false)) { echo "Employer login failed: " . ($r['raw'] ?? '') . "\n"; exit(3); }
$empUser = $r['json']['user'];
echo "  Logged in as employer id={$empUser['id']} name={$empUser['name']}\n";

// 2) Employer creates a job
echo "[2] Employer create job...\n";
$jobData = [
    'title' => 'E2E Test Job ' . time(),
    'company' => 'E2E Co',
    'location' => 'Remote',
    'description' => 'This is an automated test job.',
    'salary' => '1000',
    'category' => 'IT',
    'type' => 'Full Time',
    'workplace' => 'Remote',
    'industry' => 'IT',
    'experience_level' => 'entry'
];
$r = http_request($BASE . '/api/jobs.php', 'POST', $jobData, $empCookies);
if (!($r['json']['success'] ?? false)) { echo "Create job failed: " . ($r['raw'] ?? '') . "\n"; exit(4); }
$jobId = $r['json']['id'] ?? null;
echo "  Created job id={$jobId}\n";

// 3) Seeker login
echo "[3] Seeker login...\n";
$r = http_request($BASE . '/api/auth.php?action=login', 'POST', ['email' => $SEEK_EMAIL, 'password' => $SEEK_PASS], $seekCookies);
if (!($r['json']['success'] ?? false)) { echo "Seeker login failed: " . ($r['raw'] ?? '') . "\n"; exit(5); }
$seekUser = $r['json']['user'];
echo "  Logged in as seeker id={$seekUser['id']} name={$seekUser['name']}\n";

// 4) Seeker apply to job
echo "[4] Seeker apply to job id={$jobId}...\n";
$r = http_request($BASE . '/api/applications.php?action=apply', 'POST', ['job_id' => (int)$jobId, 'resume_note' => 'Automated test apply.'], $seekCookies);
if (!($r['json']['success'] ?? false)) { echo "Apply failed: " . ($r['raw'] ?? '') . "\n"; exit(6); }
echo "  Applied successfully.\n";

// 5) Employer fetch applications to find the new application id
echo "[5] Employer fetch applications to find application id...\n";
$r = http_request($BASE . '/api/applications.php', 'GET', null, $empCookies);
if (!($r['json']['success'] ?? false)) { echo "Fetch applications failed: " . ($r['raw'] ?? '') . "\n"; exit(7); }
$apps = $r['json']['data'] ?? [];
$appId = null;
foreach ($apps as $a) {
    if ((int)$a['job_id'] === (int)$jobId && (int)$a['seeker_id'] === (int)$seekUser['id']) { $appId = $a['id']; break; }
}
if (!$appId) { echo "Could not find application for job={$jobId} seeker={$seekUser['id']}\n"; exit(8); }
echo "  Found application id={$appId}\n";

// 6) Employer update status
echo "[6] Employer update application status to 'accepted'...\n";
$r = http_request($BASE . '/api/applications.php?action=update-status', 'POST', ['id' => (int)$appId, 'status' => 'accepted'], $empCookies);
if (!($r['json']['success'] ?? false)) { echo "Update status failed: " . ($r['raw'] ?? '') . "\n"; exit(9); }
echo "  Application updated.\n";

// 7) Seeker verify status
echo "[7] Seeker verify application status...\n";
$r = http_request($BASE . '/api/applications.php', 'GET', null, $seekCookies);
if (!($r['json']['success'] ?? false)) { echo "Seeker fetch applications failed: " . ($r['raw'] ?? '') . "\n"; exit(10); }
$found = false;
foreach ($r['json']['data'] ?? [] as $a) {
    if ((int)$a['id'] === (int)$appId) { echo "  Seeker sees application status={$a['status']}\n"; $found = true; break; }
}
if (!$found) { echo "  Seeker could not find application id={$appId}\n"; exit(11); }

echo "E2E flow completed successfully.\n";
exit(0);

?>
