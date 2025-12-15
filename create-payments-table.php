<?php
session_start();
require_once 'config/database.php';

if(!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$message = '';
$success = false;

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = Database::getInstance()->getConnection();
        
        // Simple table creation SQL
        $sql = "
            CREATE TABLE IF NOT EXISTS payments (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                payment_method VARCHAR(50) NOT NULL,
                status VARCHAR(20) DEFAULT 'pending',
                subscription_period VARCHAR(100),
                payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
                transaction_id VARCHAR(100),
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ";
        
        $pdo->exec($sql);
        $message = "Payments table created successfully!";
        $success = true;
        
        // Create uploads directory
        if(!is_dir('uploads')) {
            mkdir('uploads', 0755, true);
        }
        if(!is_dir('uploads/receipts')) {
            mkdir('uploads/receipts', 0755, true);
        }
        
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Create Payments Table</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .btn { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .sql-code { background: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Create Payments Table</h1>
        
        <?php if($message): ?>
        <div class="alert <?php echo $success ? 'alert-success' : 'alert-danger'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>
        
        <p>This will create a basic payments table with essential columns.</p>
        
        <div class="sql-code">
CREATE TABLE payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    subscription_period VARCHAR(100),
    payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    transaction_id VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)
        </div>
        
        <form method="POST">
            <button type="submit" class="btn" onclick="return confirm('Create payments table?')">
                Create Table
            </button>
            <a href="admin-payments.php" style="margin-left: 10px;">Back to Payments</a>
        </form>
    </div>
</body>
</html>