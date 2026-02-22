<?php
// ========================================
// RZN WEBSITE - DATABASE SETUP SCRIPT
// ========================================

echo "<h1>RZN Website Database Setup</h1>";
echo "<hr>";

// Support both local development and Railway deployment
if (getenv('RAILWAY_ENVIRONMENT')) {
    // Railway environment
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

echo "<h2>Step 1: Creating Database Connection...</h2>";

try {
    // Connect to MySQL (without specifying database)
    $pdo = new PDO(
        "mysql:host=$host;port=$port;charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✅ Connected to MySQL<br>";
    
    // Create database
    echo "<h2>Step 2: Creating Database...</h2>";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $db");
    echo "✅ Database '$db' created/verified<br>";
    
    // Select database
    $pdo->exec("USE $db");
    echo "✅ Using database '$db'<br>";
    
    // Create tables
    echo "<h2>Step 3: Creating Tables...</h2>";
    
    // Create users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(100) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('leader', 'admin', 'member', 'pending') DEFAULT 'pending',
            avatar LONGTEXT,
            facebook_url VARCHAR(255),
            youtube_url VARCHAR(255),
            tiktok_url VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    echo "✅ Users table created<br>";
    
    // Create activity_log table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS activity_log (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT,
            action VARCHAR(100) NOT NULL,
            details TEXT,
            ip_address VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "✅ Activity log table created<br>";
    
    // Insert initial users
    echo "<h2>Step 4: Inserting Initial Admin Users...</h2>";
    
    // Clear existing users
    $pdo->exec("DELETE FROM activity_log");
    $pdo->exec("DELETE FROM users");
    
    // Password hash for "RZN2025"
    $passwordHash = password_hash('RZN2025', PASSWORD_DEFAULT);
    
    // Insert users
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
    
    $admins = [
        ['RZN.J3em', 'j3em@rzn.org', 'leader'],
        ['RZN.Neilla', 'neilla@rzn.org', 'admin'],
        ['RZN.Wthelly', 'wthelly@rzn.org', 'admin']
    ];
    
    foreach ($admins as $admin) {
        $stmt->execute([$admin[0], $admin[1], $passwordHash, $admin[2]]);
        echo "✅ User created: <strong>{$admin[0]}</strong> ({$admin[2]})<br>";
    }
    
    echo "<hr>";
    echo "<h2>✅ Setup Complete!</h2>";
    echo "<p><strong>Your database is ready!</strong></p>";
    echo "<h3>Login Credentials:</h3>";
    echo "<ul>";
    echo "<li><strong>Username:</strong> RZN.J3em, RZN.Neilla, or RZN.Wthelly</li>";
    echo "<li><strong>Password:</strong> RZN2025</li>";
    echo "</ul>";
    echo "<hr>";
    echo "<p><a href='index.html' style='padding: 10px 20px; background: #ffd700; color: #000; text-decoration: none; border-radius: 5px; font-weight: bold;'>Go to Website</a></p>";
    
} catch(PDOException $e) {
    echo "<h2 style='color: red;'>❌ Setup Failed</h2>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Troubleshooting:</strong></p>";
    echo "<ul>";
    echo "<li>Make sure MySQL is running</li>";
    echo "<li>Check that username and password are correct</li>";
    echo "<li>Verify your MySQL host is 'localhost'</li>";
    echo "</ul>";
}
?>
