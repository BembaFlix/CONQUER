<?php
session_start();
require_once 'config/database.php';

if(!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login.php');
    exit();
}

try {
    $pdo = Database::getInstance()->getConnection();
    echo "<h1>Database Diagnostic</h1>";
    
    // 1. Check connection
    echo "<h2>1. Database Connection</h2>";
    echo "Connection successful!<br>";
    echo "Database: " . $pdo->query("SELECT DATABASE()")->fetchColumn() . "<br><br>";
    
    // 2. Check if payments table exists
    echo "<h2>2. Check Payments Table</h2>";
    $tableExists = $pdo->query("SHOW TABLES LIKE 'payments'")->rowCount() > 0;
    
    if($tableExists) {
        echo "✓ Payments table exists<br>";
        
        // Show table structure
        echo "<h3>Table Structure:</h3>";
        $columns = $pdo->query("DESCRIBE payments")->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach($columns as $col) {
            echo "<tr>";
            echo "<td>" . $col['Field'] . "</td>";
            echo "<td>" . $col['Type'] . "</td>";
            echo "<td>" . $col['Null'] . "</td>";
            echo "<td>" . $col['Key'] . "</td>";
            echo "<td>" . $col['Default'] . "</td>";
            echo "<td>" . $col['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Count records
        $count = $pdo->query("SELECT COUNT(*) FROM payments")->fetchColumn();
        echo "<br>Total records: " . $count . "<br>";
        
    } else {
        echo "✗ Payments table does NOT exist<br>";
        
        // Show all tables
        echo "<h3>Available Tables:</h3>";
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        echo "<ul>";
        foreach($tables as $table) {
            echo "<li>" . $table . "</li>";
        }
        echo "</ul>";
    }
    
    // 3. Check users table (for foreign key)
    echo "<h2>3. Check Users Table</h2>";
    $usersTableExists = $pdo->query("SHOW TABLES LIKE 'users'")->rowCount() > 0;
    
    if($usersTableExists) {
        echo "✓ Users table exists<br>";
        $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        echo "Total users: " . $userCount . "<br>";
        
        // Show sample users
        echo "<h3>Sample Users (first 5):</h3>";
        $users = $pdo->query("SELECT id, full_name, email FROM users LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th></tr>";
        foreach($users as $user) {
            echo "<tr>";
            echo "<td>" . $user['id'] . "</td>";
            echo "<td>" . $user['full_name'] . "</td>";
            echo "<td>" . $user['email'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "✗ Users table does NOT exist<br>";
    }
    
} catch (PDOException $e) {
    echo "<h2>Database Error</h2>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "Check your config/database.php file.<br>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Diagnostic</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        h1 { color: #333; }
        h2 { color: #666; margin-top: 30px; }
        h3 { color: #888; }
        table { border-collapse: collapse; margin: 10px 0; }
        th { background: #f0f0f0; padding: 8px; }
        td { padding: 8px; border: 1px solid #ddd; }
        ul { padding-left: 20px; }
        .success { color: green; }
        .error { color: red; }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 5px;
        }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div style="margin-top: 30px;">
        <a href="admin-payments.php" class="btn">Back to Payments</a>
        <a href="create-payments-table.php" class="btn">Create Payments Table</a>
        <a href="install-payments.php" class="btn">Run Installer</a>
    </div>
</body>
</html>