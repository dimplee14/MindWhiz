<?php
$envFile = __DIR__ . '/../../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        if (!getenv($key)) {
            putenv("$key=$value");
        }
    }
}
// Database connection with environment-aware defaults so it works locally and in production
// $host = getenv('DB_HOST') ?: 'localhost';
// $user = getenv('DB_USERNAME') ?: 'root';
// $pass = getenv('DB_PASSWORD') ?: '';
// $db   = getenv('DB_DATABASE') ?: 'memory_test';
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'memory_test';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = mysqli_connect($host, $user, $pass, $db);
    // IMPORTANT for Gujarati + UTF-8
    mysqli_set_charset($conn, 'utf8mb4');
    // Set timezone to match PHP and MySQL
    mysqli_query($conn, "SET time_zone = '+00:00'");
    date_default_timezone_set('UTC');
} catch (mysqli_sql_exception $e) {
    // Surface a clear message instead of a 500
    http_response_code(500);
    echo 'Database connection failed. Please check DB credentials and database name.';
    exit;
}
?>