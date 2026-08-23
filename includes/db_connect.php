<?php

define('DB_HOST', 'localhost');
define('DB_PORT', '5432'); 
define('DB_USER', 'postgres');
define('DB_PASS', 'Laksh.2912');
define('DB_NAME', 'collage_admission_system_db');

try {

    $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";options='--client_encoding=UTF8'";


    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];


    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {


    die("Database Connection Failed: " . $e->getMessage());
}
