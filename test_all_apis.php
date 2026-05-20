<?php
/**
 * Comprehensive API Testing Script
 * Tests all endpoints to verify they're working properly
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$baseUrl = 'http://localhost/NewJob/api';
$results = [];

// Helper function to make requests
function testApi($method, $endpoint, $data = null, $headers = []) {
    global $baseUrl;
    
    $url = $baseUrl . $endpoint;
    $ch = curl_init($url);
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    
    $defaultHeaders = [
        'Content-Type: application/json',
        'Accept: application/json'
    ];
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($defaultHeaders, $headers));
    
    if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'url' => $url,
        'method' => $method,
        'status' => $httpCode,
        'response' => $response,
        'error' => $error
    ];
}

// Test each API endpoint
$tests = [
    ['GET', '/auth.php?action=check-session', null, 'Check Session'],
    ['GET', '/jobs.php?action=autocomplete&q=software', null, 'Jobs Autocomplete'],
    ['GET', '/jobs.php?action=search&keyword=developer', null, 'Jobs Search'],
    ['GET', '/dashboard-stats.php', null, 'Dashboard Stats'],
    ['GET', '/global-search.php?q=test', null, 'Global Search'],
    ['GET', '/activity-logs.php', null, 'Activity Logs'],
    ['GET', '/notifications.php', null, 'Notifications'],
    ['GET', '/messages.php', null, 'Messages'],
    ['GET', '/reviews.php', null, 'Reviews'],
    ['GET', '/saved.php', null, 'Saved Items'],
    ['GET', '/trainings.php', null, 'Trainings'],
    ['GET', '/profiles.php', null, 'Profiles'],
    ['GET', '/users.php', null, 'Users'],
    ['GET', '/rbac.php', null, 'RBAC'],
];

echo "==== API TEST RESULTS ====\n\n";

foreach ($tests as [$method, $endpoint, $data, $name]) {
    echo "Testing: $name ($method $endpoint)\n";
    $result = testApi($method, $endpoint, $data);
    
    $status = $result['status'];
    $error = $result['error'];
    $response = $result['response'];
    
    // Determine status
    if ($error) {
        echo "❌ ERROR: $error\n";
    } elseif ($status >= 200 && $status < 300) {
        echo "✅ SUCCESS (HTTP $status)\n";
    } elseif ($status >= 400 && $status < 500) {
        echo "⚠️  CLIENT ERROR (HTTP $status) - May need auth or valid params\n";
    } else {
        echo "❌ SERVER ERROR (HTTP $status)\n";
    }
    
    if ($response) {
        $decoded = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "Response: " . json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
        } else {
            echo "Response: " . substr($response, 0, 200) . (strlen($response) > 200 ? "..." : "") . "\n";
        }
    }
    echo "\n";
}

echo "==== TEST COMPLETE ====\n";
?>
