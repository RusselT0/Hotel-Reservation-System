
<?php

// Philippine Standard Time
date_default_timezone_set('Asia/Manila');

// Database configuration
$host = 'localhost';
$dbname = 'hotel_reservation_system';
$username = 'root';
$password = '';

try {

    // Establish database connection
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    // Set MySQL session to Philippine Standard Time
    $pdo->exec("SET time_zone = '+08:00'");

} catch (PDOException $e) {

    // Record technical error without exposing credentials
    error_log($e->getMessage());

    http_response_code(500);
    exit('Database connection failed.');
}
