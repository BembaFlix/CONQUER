<?php
session_start();
require_once 'config/database.php';

if(!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$success = false;
$message = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = Database::getInstance()->getConnection();
        
        // Read the SQL file
        $sql = file_get_contents('create_payments_table.sql');
        
        // Execute the SQL
        $pdo->exec($sql);
        
        // Create uploads directory
        if(!is_dir('uploads')) {
            mkdir('uploads', 0777, true);
        }
        
        if(!is_dir('uploads/receipts')) {
            mkdir('uploads/receipts', 0777, true);
        }
        
        $success = true;
        $message = "Payments table created successfully!";
        
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Payments Table</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .btn {
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover {
            background: #0056b3;
        }
        .btn-danger {
            background: #dc3545;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #545b62;
        }
        .btn-container {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .code {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            overflow-x: auto;
            margin: 20px 0;
            border: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Setup Payments Table</h1>
        
        <?php if($message): ?>
        <div class="alert <?php echo $success ? 'alert-success' : 'alert-danger'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>
        
        <p>This script will create the payments table in your database. Make sure you have backup of your database before proceeding.</p>
        
        <div class="code">
CREATE TABLE payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('credit_card', 'gcash', 'paymaya', 'bank_transfer', 'cash') NOT NULL,
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    subscription_period VARCHAR(100),
    payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    transaction_id VARCHAR(100) UNIQUE,
    receipt_image VARCHAR(255),
    notes TEXT,
    payment_details JSON,
    confirmed_by INT,
    confirmation_date DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
        </div>
        
        <form method="POST" onsubmit="return confirm('Are you sure you want to create the payments table? This will overwrite existing table if it exists.');">
            <div class="btn-container">
                <button type="submit" class="btn btn-danger">Create Payments Table</button>
                <a href="admin-payments.php" class="btn btn-secondary">Back to Payments</a>
                <a href="check-payments-table.php" class="btn">Check Current Table</a>
            </div>
        </form>
        
        <div style="margin-top: 30px; padding: 15px; background: #fff3cd; border-radius: 5px; border: 1px solid #ffeaa7;">
            <h3 style="margin-top: 0; color: #856404;">Important Notes:</h3>
            <ul>
                <li>Make sure your database user has CREATE TABLE privileges</li>
                <li>Backup your database before running this script</li>
                <li>Create the uploads/receipts directory manually if it doesn't exist</li>
                <li>Set proper permissions on uploads directory (chmod 755)</li>
            </ul>
        </div>
    </div>
</body>
</html>