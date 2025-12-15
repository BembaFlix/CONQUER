<?php
// Simple database setup
require_once 'config/database.php';

try {
    $pdo = Database::getInstance()->getConnection();
    
    echo "<h1>Setting up database...</h1>";
    
    // 1. Create payments table
    $sql = "
        CREATE TABLE IF NOT EXISTS payments (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT,
            amount DECIMAL(10,2),
            payment_method VARCHAR(50),
            status VARCHAR(20) DEFAULT 'pending',
            payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            transaction_id VARCHAR(100),
            notes TEXT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ";
    
    $pdo->exec($sql);
    echo "✓ Payments table created<br>";
    
    // 2. Insert sample data
    $sampleData = [
        [1, 49.99, 'credit_card', 'completed', date('Y-m-d H:i:s', strtotime('-5 days')), 'TXN001', 'Monthly payment'],
        [2, 79.99, 'gcash', 'completed', date('Y-m-d H:i:s', strtotime('-3 days')), 'TXN002', 'GCash payment'],
        [3, 29.99, 'cash', 'pending', date('Y-m-d H:i:s', strtotime('-1 day')), 'TXN003', 'Cash payment'],
        [1, 49.99, 'paymaya', 'pending', date('Y-m-d H:i:s'), 'TXN004', 'PayMaya payment'],
        [2, 79.99, 'bank_transfer', 'completed', date('Y-m-d H:i:s', strtotime('-2 days')), 'TXN005', 'Bank transfer']
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO payments (user_id, amount, payment_method, status, payment_date, transaction_id, notes) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $count = 0;
    foreach($sampleData as $data) {
        $stmt->execute($data);
        $count++;
    }
    
    echo "✓ $count sample payments inserted<br>";
    
    // 3. Create uploads directory
    if(!is_dir('uploads')) {
        mkdir('uploads', 0755, true);
        echo "✓ Created uploads directory<br>";
    }
    
    if(!is_dir('uploads/receipts')) {
        mkdir('uploads/receipts', 0755, true);
        echo "✓ Created uploads/receipts directory<br>";
    }
    
    echo "<h2 style='color: green;'>Setup complete!</h2>";
    echo "<a href='admin-payments.php'>Go to Payments Page</a>";
    
} catch (PDOException $e) {
    echo "<h2 style='color: red;'>Error</h2>";
    echo "Error: " . $e->getMessage() . "<br>";
    
    // Try simpler creation
    try {
        $simpleSQL = "CREATE TABLE IF NOT EXISTS payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            amount DECIMAL(10,2),
            payment_method VARCHAR(50),
            status VARCHAR(20)
        )";
        
        $pdo->exec($simpleSQL);
        echo "✓ Simple payments table created<br>";
        
        // Insert minimal data
        $pdo->exec("INSERT INTO payments (user_id, amount, payment_method, status) VALUES (1, 49.99, 'credit_card', 'completed')");
        echo "✓ Test payment inserted<br>";
        
        echo "<a href='admin-payments.php'>Go to Payments Page</a>";
        
    } catch (PDOException $e2) {
        echo "Even simple creation failed: " . $e2->getMessage();
    }
}
?>