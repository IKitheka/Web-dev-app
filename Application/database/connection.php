<?php
$host = getenv("DB_HOST") ?: 'localhost';
$port = getenv("DB_PORT") ?: '5432';
$dbname = getenv("DB_NAME") ?: 'Intern';
$username = getenv("DB_USER") ?: 'postgres';
$password = getenv("DB_PASS") ?: '101121';

try {

    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $username, $password);
    
    // Set PDO attributes
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    
    die("Database connection failed: " . $e->getMessage());
}

?>