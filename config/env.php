<?php
/**
 * Environment Configuration Loader
 * Loads variables from .env file
 */

if (!function_exists('loadEnv')) {
    function loadEnv($path = __DIR__ . '/../.env') {
        if (!file_exists($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) continue;

            // Parse KEY=VALUE
            if (strpos($line, '=') !== false) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Remove quotes if present
                if (preg_match('/^"(.+)"$/', $value, $m)) {
                    $value = $m[1];
                }

                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
    }
}

// Load environment variables
loadEnv(__DIR__ . '/../.env');

// Helper function to get environment variable
if (!function_exists('env')) {
    function env(string $key, $default = null) {
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }
}
