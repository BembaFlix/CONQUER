<?php
session_start();
require_once 'config/database.php';

if(!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login.php');
    exit();
}

try {
    $pdo = Database::getInstance()->getConnection();
    
    echo "<h1>Debug Payments Data</h1>";
    
    // Check if payments table exists
    $tableExists = $pdo->query("SHOW TABLES LIKE 'payments'")->rowCount() > 0;
    
    if(!$tableExists) {
        echo "<h2 style='color: red;'>Payments table does not exist!</h2>";
        echo "<p>Run the setup script to create it.</p>";
        echo "<a href='simple-setup.php'>Run Setup</a>";
        exit();
    }
    
    // Get all payments
    $payments = $pdo->query("SELECT * FROM payments LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Raw Payment Data (first 10 records):</h2>";
    echo "<pre>";
    print_r($payments);
    echo "</pre>";
    
    // Check users table
    echo "<h2>Users Table Sample:</h2>";
    $users = $pdo->query("SELECT id, full_name, email FROM users LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($users);
    echo "</pre>";
    
    // Test the join query
    echo "<h2>Test Join Query:</h2>";
    $sql = "
        SELECT p.*, 
               COALESCE(u.full_name, 'Unknown User') as full_name,
               COALESCE(u.email, 'No email') as email,
               u.phone,
               gm.MembershipPlan
        FROM payments p
        LEFT JOIN users u ON p.user_id = u.id
        LEFT JOIN gym_members gm ON u.email = gm.Email
        ORDER BY p.payment_date DESC
        LIMIT 5
    ";
    
    try {
        $joinedPayments = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>";
        print_r($joinedPayments);
        echo "</pre>";
    } catch (Exception $e) {
        echo "Join query error: " . $e->getMessage();
        
        // Try simpler query
        echo "<h3>Simple query (no joins):</h3>";
        $simplePayments = $pdo->query("SELECT * FROM payments LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>";
        print_r($simplePayments);
        echo "</pre>";
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Payments</title>
</head>
<body>
    <div style="margin-top: 30px;">
        <a href="admin-payments.php" style="padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;">
            Back to Payments
        </a>
    </div>
</body>
</html>