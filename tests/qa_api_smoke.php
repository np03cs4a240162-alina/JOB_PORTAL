<?php
// Simple API smoke test for Smart Job Portal
$base = 'http://localhost/JOB_PORTAL/api';
$dir = __DIR__ . '/../api';
$files = glob($dir . '/*.php');
$curl = curl_init();
$cookieJar = __DIR__ . '/cookies.txt';
@unlink($cookieJar);

function doRequest($method, $url, $data = null, $cookieJar = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    if ($cookieJar) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
    }
    if ($method !== 'GET' && $data !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    }
    $resp = curl_exec($ch);
    $info = curl_getinfo($ch);
    $err = curl_error($ch);
    curl_close($ch);
    return ['status' => $info['http_code'], 'body' => $resp, 'error' => $err];
}

$report = [];
foreach ($files as $f) {
    $name = basename($f);
    $url = $base . '/' . $name;
    // Test GET
    $g = doRequest('GET', $url, null, $cookieJar);
    // Test POST (empty body)
    $p = doRequest('POST', $url, [], $cookieJar);

    $report[$name] = [
        'GET_status' => $g['status'],
        'GET_body_snippet' => substr($g['body'], 0, 300),
        'POST_status' => $p['status'],
        'POST_body_snippet' => substr($p['body'], 0, 300),
        'POST_error' => $p['error']
    ];
}
// Pretty print
foreach ($report as $file => $r) {
    echo "---- $file ----\n";
    echo "GET: {$r['GET_status']}\n";
    echo "GET body: " . preg_replace('/\s+/', ' ', trim($r['GET_body_snippet'])) . "\n";
    echo "POST: {$r['POST_status']}\n";
    echo "POST body: " . preg_replace('/\s+/', ' ', trim($r['POST_body_snippet'])) . "\n";
    if ($r['POST_error']) echo "POST error: {$r['POST_error']}\n";
    echo "\n";
}

file_put_contents(__DIR__ . '/qa_api_smoke_report.json', json_encode($report, JSON_PRETTY_PRINT));
echo "Report saved to tests/qa_api_smoke_report.json\n";
