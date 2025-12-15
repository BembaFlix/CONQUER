<?php
session_start();
require_once 'config/database.php';

if(!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login.php');
    exit();
}

try {
    $pdo = Database::getInstance()->getConnection();
    
    // Check if payments table exists
    $tableExists = $pdo->query("SHOW TABLES LIKE 'payments'")->rowCount() > 0;
    
    if($tableExists) {
        // Check table structure
        $columns = $pdo->query("DESCRIBE payments")->fetchAll(PDO::FETCH_ASSOC);
        $columnNames = array_column($columns, 'Field');
        
        // Required columns
        $requiredColumns = ['id', 'user_id', 'amount', 'payment_method', 'status', 'payment_date', 'transaction_id'];
        $missingColumns = array_diff($requiredColumns, $columnNames);
        
        if(empty($missingColumns)) {
            // Count records
            $count = $pdo->query("SELECT COUNT(*) FROM payments")->fetchColumn();
            
            $_SESSION['admin_message'] = "Payments table exists with $count records. All required columns are present.";
            $_SESSION['admin_message_type'] = 'success';
        } else {
            $_SESSION['admin_message'] = "Payments table exists but missing columns: " . implode(', ', $missingColumns);
            $_SESSION['admin_message_type'] = 'warning';
        }
    } else {
        $_SESSION['admin_message'] = "Payments table does not exist. Please run the SQL script to create it.";
        $_SESSION['admin_message_type'] = 'danger';
    }
    
} catch (PDOException $e) {
    $_SESSION['admin_message'] = "Database error: " . $e->getMessage();
    $_SESSION['admin_message_type'] = 'danger';
}

header('Location: admin-payments.php');
exit();
?>