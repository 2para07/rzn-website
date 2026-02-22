<?php
// ========================================
// DATABASE CONNECTION
// ========================================

// Support both local development and Railway deployment
if (getenv('RAILWAY_ENVIRONMENT')) {
    // Railway environment - use environment variables
    $host = getenv('DB_HOST') ?: 'localhost';
    $db = getenv('DB_NAME') ?: 'rzn_members';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASSWORD') ?: '';
    $port = getenv('DB_PORT') ?: '3306';
} else {
    // Local development
    $host = 'localhost';
    $db = 'rzn_members';
    $user = 'root';
    $pass = '';
    $port = '3306';
}

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    
    // Connection successful
    
} catch(PDOException $e) {
    // Log error but don't expose details
    error_log("Database Connection Error: " . $e->getMessage());
    
    // Return JSON error if AJAX call
    if (!empty($_POST) || !empty($_GET)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database connection error']);
        exit;
    }
    
    // Show error
    die("Database connection failed. Please check if MySQL is running and database 'rzn_members' exists.");
}

