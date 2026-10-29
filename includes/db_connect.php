<?php

// Load database configuration from environment variables for security.
// Fallback to default values for local development.
$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '5432';
$user = getenv('DB_USER') ?: 'postgres';
$pass = getenv('DB_PASS') ?: 'Laksh.2912';
$name = getenv('DB_NAME') ?: 'collage_admission_system_db';

$dsn = "pgsql:host={$host};port={$port};dbname={$name};options='--client_encoding=UTF8'";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // In production, avoid exposing detailed error messages.
    die('Database Connection Failed.');
}
?>
