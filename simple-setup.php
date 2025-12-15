<?php
// Simple setup without requiring login
require_once 'config/database.php';

try {
    $pdo = Database::getInstance()->getConnection();
    
    echo "<h1>Simple Database Setup</h1>";
    
    // 1. Check users table
    echo "<h2>1. Users Table</h2>";
    try {
        $usersColumns = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN, 0);
        echo "Users table columns: " . implode(', ', $usersColumns) . "<br>";
        
        // Check for password column
        if(!in_array('password', $usersColumns)) {
            echo "Adding password column... ";
            $pdo->exec("ALTER TABLE users ADD COLUMN password VARCHAR(255)");
            echo "Done!<br>";
        }
        
        // Check for user_type column
        if(!in_array('user_type', $usersColumns)) {
            echo "Adding user_type column... ";
            $pdo->exec("ALTER TABLE users ADD COLUMN user_type VARCHAR(20) DEFAULT 'member'");
            echo "Done!<br>";
        }
        
    } catch (Exception $e) {
        echo "Error with users table: " . $e->getMessage() . "<br>";
    }
    
    // 2. Create payments table
    echo "<h2>2. Payments Table</h2>";
    try {
        $tableExists = $pdo->query("SHOW TABLES LIKE 'payments'")->rowCount() > 0;
        
        if(!$tableExists) {
            echo "Creating payments table... ";
            $sql = "
                CREATE TABLE payments (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    user_id INT,
                    amount DECIMAL(10,2),
                    payment_method VARCHAR(50),
                    status VARCHAR(20) DEFAULT 'pending',
                    payment_date DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ";
            $pdo->exec($sql);
            echo "Done!<br>";
            
            // Add sample data
            echo "Adding sample data... ";
            $sampleSQL = "
                INSERT INTO payments (user_id, amount, payment_method, status, payment_date) VALUES
                (1, 49.99, 'credit_card', 'completed', DATE_SUB(NOW(), INTERVAL 5 DAY)),
                (1, 49.99, 'gcash', 'pending', DATE_SUB(NOW(), INTERVAL 2 DAY)),
                (2, 79.99, 'bank_transfer', 'completed', DATE_SUB(NOW(), INTERVAL 7 DAY)),
                (3, 29.99, 'cash', 'pending', DATE_SUB(NOW(), INTERVAL 1 DAY))
            ";
            $pdo->exec($sampleSQL);
            echo "Done!<br>";
        } else {
            echo "Payments table already exists.<br>";
            
            // Count records
            $count = $pdo->query("SELECT COUNT(*) FROM payments")->fetchColumn();
            echo "Total payments: " . $count . "<br>";
        }
        
    } catch (Exception $e) {
        echo "Error with payments table: " . $e->getMessage() . "<br>";
    }
    
    // 3. Create directories
    echo "<h2>3. File Directories</h2>";
    if(!is_dir('uploads')) {
        mkdir('uploads', 0755, true);
        echo "Created uploads directory<br>";
    }
    
    if(!is_dir('uploads/receipts')) {
        mkdir('uploads/receipts', 0755, true);
        echo "Created uploads/receipts directory<br>";
    }
    
    echo "<h2 style='color: green;'>Setup Complete!</h2>";
    echo "<a href='admin-payments.php'>Go to Payments Page</a>";
    
} catch (PDOException $e) {
    echo "<h2 style='color: red;'>Database Error</h2>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "Please check your database connection in config/database.php";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple Setup</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        h2 { margin-top: 30px; }
    </style>
</head>
<body>
</body>
</html>