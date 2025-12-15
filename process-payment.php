<?php
session_start();
require_once 'config/database.php';

if(!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'member') {
    header('Location: login.php');
    exit();
}

if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: user-payments.php');
    exit();
}

try {
    $pdo = Database::getInstance()->getConnection();
    $user_id = $_SESSION['user_id'];
    
    // Get user info
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    // Validate input
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $payment_method = filter_input(INPUT_POST, 'payment_method', FILTER_SANITIZE_STRING);
    $subscription_period = filter_input(INPUT_POST, 'subscription_period', FILTER_SANITIZE_STRING);
    $notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING);
    
    if(!$amount || $amount <= 0) {
        $_SESSION['error'] = "Invalid amount specified";
        header('Location: user-payments.php');
        exit();
    }
    
    // Handle file upload for receipt
    $receipt_image = null;
    if(isset($_FILES['receipt_image']) && $_FILES['receipt_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/receipts/';
        if(!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_ext = pathinfo($_FILES['receipt_image']['name'], PATHINFO_EXTENSION);
        $file_name = 'receipt_' . time() . '_' . $user_id . '.' . $file_ext;
        $file_path = $upload_dir . $file_name;
        
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
        $file_type = $_FILES['receipt_image']['type'];
        
        if(in_array($file_type, $allowed_types)) {
            if(move_uploaded_file($_FILES['receipt_image']['tmp_name'], $file_path)) {
                $receipt_image = $file_path;
            }
        }
    }
    
    // Generate transaction ID
    $transaction_id = 'TXN' . strtoupper(uniqid());
    
    // Insert payment record
    $stmt = $pdo->prepare("
        INSERT INTO payments 
        (user_id, amount, payment_method, status, subscription_period, 
         transaction_id, receipt_image, notes, payment_date)
        VALUES (?, ?, ?, 'pending', ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $user_id,
        $amount,
        $payment_method,
        $subscription_period,
        $transaction_id,
        $receipt_image,
        $notes
    ]);
    
    // Send email notification to admin
    $admin_email = 'admin@conquergym.com'; // Change to your admin email
    $subject = "New Payment Pending Approval";
    $message = "
        New payment submitted by {$user['full_name']} ({$user['email']})
        
        Amount: \${$amount}
        Method: {$payment_method}
        Transaction ID: {$transaction_id}
        Period: {$subscription_period}
        
        Please review and confirm in the admin panel.
    ";
    
    mail($admin_email, $subject, $message);
    
    $_SESSION['success'] = "Payment submitted successfully! Transaction ID: {$transaction_id}";
    header('Location: user-payments.php');
    exit();
    
} catch(PDOException $e) {
    error_log("Payment processing error: " . $e->getMessage());
    $_SESSION['error'] = "Error processing payment. Please try again.";
    header('Location: user-payments.php');
    exit();
}
?>