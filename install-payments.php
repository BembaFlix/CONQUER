<?php
session_start();
require_once 'config/database.php';

if(!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$success = false;
$messages = [];

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = Database::getInstance()->getConnection();
        
        // Step 1: Check if payments table exists
        $tableExists = $pdo->query("SHOW TABLES LIKE 'payments'")->rowCount() > 0;
        
        if($tableExists) {
            $messages[] = "Payments table already exists.";
            
            // Check if transaction_id column exists
            $columnExists = false;
            try {
                $stmt = $pdo->query("SHOW COLUMNS FROM payments LIKE 'transaction_id'");
                $columnExists = $stmt->rowCount() > 0;
            } catch (Exception $e) {
                $columnExists = false;
            }
            
            if(!$columnExists) {
                // Add missing columns
                $sql = "
                    ALTER TABLE payments 
                    ADD COLUMN transaction_id VARCHAR(100) AFTER payment_date,
                    ADD COLUMN reference_number VARCHAR(100) AFTER transaction_id,
                    ADD COLUMN receipt_image VARCHAR(255) AFTER reference_number,
                    ADD COLUMN notes TEXT AFTER receipt_image,
                    ADD COLUMN payment_details TEXT AFTER notes,
                    ADD COLUMN confirmed_by INT AFTER payment_details,
                    ADD COLUMN confirmation_date DATETIME AFTER confirmed_by,
                    ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER confirmation_date,
                    ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at
                ";
                
                $pdo->exec($sql);
                $messages[] = "Added missing columns to payments table.";
            } else {
                $messages[] = "All required columns already exist.";
            }
            
        } else {
            // Create the table from scratch
            $sql = "
                CREATE TABLE payments (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    user_id INT NOT NULL,
                    amount DECIMAL(10,2) NOT NULL,
                    payment_method ENUM('credit_card', 'gcash', 'paymaya', 'bank_transfer', 'cash') NOT NULL,
                    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
                    subscription_period VARCHAR(100),
                    payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
                    transaction_id VARCHAR(100) UNIQUE,
                    reference_number VARCHAR(100),
                    receipt_image VARCHAR(255),
                    notes TEXT,
                    payment_details TEXT,
                    confirmed_by INT,
                    confirmation_date DATETIME,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (confirmed_by) REFERENCES users(id) ON DELETE SET NULL
                )
            ";
            
            $pdo->exec($sql);
            $messages[] = "Created payments table successfully.";
            
            // Create indexes
            $pdo->exec("CREATE INDEX idx_payments_user_id ON payments(user_id)");
            $pdo->exec("CREATE INDEX idx_payments_status ON payments(status)");
            $pdo->exec("CREATE INDEX idx_payments_payment_date ON payments(payment_date)");
            $pdo->exec("CREATE INDEX idx_payments_transaction_id ON payments(transaction_id)");
            
            $messages[] = "Created indexes for better performance.";
        }
        
        // Step 2: Insert sample data if requested
        if(isset($_POST['insert_sample_data'])) {
            $sampleData = [
                ['user_id' => 1, 'amount' => 49.99, 'payment_method' => 'credit_card', 'status' => 'completed', 'subscription_period' => 'Monthly Membership', 'notes' => 'Monthly payment'],
                ['user_id' => 2, 'amount' => 79.99, 'payment_method' => 'gcash', 'status' => 'completed', 'subscription_period' => 'Champion Membership', 'notes' => 'GCash payment - Ref: GC123456'],
                ['user_id' => 3, 'amount' => 29.99, 'payment_method' => 'cash', 'status' => 'pending', 'subscription_period' => 'Basic Membership', 'notes' => 'Cash payment at reception'],
                ['user_id' => 4, 'amount' => 49.99, 'payment_method' => 'paymaya', 'status' => 'pending', 'subscription_period' => 'Monthly Membership', 'notes' => 'PayMaya payment - Ref: PM789012'],
                ['user_id' => 5, 'amount' => 79.99, 'payment_method' => 'bank_transfer', 'status' => 'completed', 'subscription_period' => 'Champion Membership', 'notes' => 'BDO Transfer - Ref: BDO2024001']
            ];
            
            $inserted = 0;
            foreach($sampleData as $data) {
                $transaction_id = 'TXN' . date('YmdHis') . rand(100, 999);
                $payment_date = date('Y-m-d H:i:s', strtotime('-' . rand(1, 30) . ' days'));
                
                $stmt = $pdo->prepare("
                    INSERT INTO payments (user_id, amount, payment_method, status, subscription_period, transaction_id, payment_date, notes) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $data['user_id'],
                    $data['amount'],
                    $data['payment_method'],
                    $data['status'],
                    $data['subscription_period'],
                    $transaction_id,
                    $payment_date,
                    $data['notes']
                ]);
                
                $inserted++;
            }
            
            $messages[] = "Inserted $inserted sample payment records.";
        }
        
        // Step 3: Create uploads directory
        if(!is_dir('uploads')) {
            mkdir('uploads', 0755, true);
            $messages[] = "Created uploads directory.";
        }
        
        if(!is_dir('uploads/receipts')) {
            mkdir('uploads/receipts', 0755, true);
            $messages[] = "Created uploads/receipts directory.";
        }
        
        $success = true;
        
    } catch (PDOException $e) {
        $messages[] = "Error: " . $e->getMessage();
    } catch (Exception $e) {
        $messages[] = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install Payments System</title>
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
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .btn {
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin: 5px;
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
        .btn-success {
            background: #28a745;
        }
        .btn-success:hover {
            background: #218838;
        }
        .btn-container {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        .checkbox-group {
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .message-list {
            margin: 20px 0;
            padding-left: 20px;
        }
        .message-list li {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Install Payments System</h1>
        
        <?php if(!empty($messages)): ?>
        <div class="alert <?php echo $success ? 'alert-success' : 'alert-danger'; ?>">
            <h3 style="margin-top: 0;"><?php echo $success ? 'Success!' : 'Error!'; ?></h3>
            <ul class="message-list">
                <?php foreach($messages as $msg): ?>
                <li><?php echo htmlspecialchars($msg); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <div class="alert alert-info">
            <h3 style="margin-top: 0;">Before You Begin:</h3>
            <ol>
                <li>Make sure your database user has CREATE TABLE and ALTER TABLE privileges</li>
                <li>Backup your database before proceeding</li>
                <li>This will create/modify the payments table</li>
                <li>If the table already exists, missing columns will be added</li>
            </ol>
        </div>
        
        <form method="POST" onsubmit="return confirm('Are you sure you want to proceed with the installation?');">
            <div class="checkbox-group">
                <label>
                    <input type="checkbox" name="insert_sample_data" value="1" checked>
                    Insert sample payment data (5 records)
                </label>
                <p style="margin: 5px 0 0 20px; color: #666; font-size: 0.9em;">
                    This will insert sample payments for testing. User IDs 1-5 must exist in your users table.
                </p>
            </div>
            
            <div class="btn-container">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-database"></i> Install Payments System
                </button>
                <a href="admin-payments.php" class="btn">
                    <i class="fas fa-arrow-left"></i> Back to Payments
                </a>
                <a href="check-payments-table.php" class="btn">
                    <i class="fas fa-search"></i> Check Current Table
                </a>
            </div>
        </form>
        
        <div style="margin-top: 30px; padding: 15px; background: #fff3cd; border-radius: 5px; border: 1px solid #ffeaa7;">
            <h3 style="margin-top: 0; color: #856404;">SQL Queries That Will Be Executed:</h3>
            <pre style="background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto;">
-- Create table if it doesn't exist
CREATE TABLE IF NOT EXISTS payments (...);

-- Or alter table if it exists
ALTER TABLE payments ADD COLUMN ...;

-- Create indexes
CREATE INDEX idx_payments_user_id ON payments(user_id);
CREATE INDEX idx_payments_status ON payments(status);
CREATE INDEX idx_payments_payment_date ON payments(payment_date);
CREATE INDEX idx_payments_transaction_id ON payments(transaction_id);

-- Insert sample data (if selected)
INSERT INTO payments (...) VALUES (...);
            </pre>
        </div>
    </div>
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</body>
</html>