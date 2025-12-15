<?php
session_start();
require_once 'config/database.php';

if(!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Check if it's a POST request
if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get JSON data
$input = json_decode(file_get_contents('php://input'), true);
$payments = $input['payments'] ?? [];
$admin_id = $_SESSION['user_id'];

if(empty($payments)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No payments selected']);
    exit();
}

try {
    $pdo = Database::getInstance()->getConnection();
    
    // Start transaction
    $pdo->beginTransaction();
    
    $successCount = 0;
    $errors = [];
    
    foreach($payments as $payment_id) {
        $payment_id = (int)$payment_id;
        
        try {
            // Update payment status
            $stmt = $pdo->prepare("
                UPDATE payments 
                SET status = 'completed', 
                    confirmed_by = ?, 
                    confirmation_date = NOW(),
                    updated_at = NOW()
                WHERE id = ? AND status = 'pending'
            ");
            
            $result = $stmt->execute([$admin_id, $payment_id]);
            
            if($result && $stmt->rowCount() > 0) {
                $successCount++;
                
                // Get user details for email
                $paymentStmt = $pdo->prepare("SELECT user_id, transaction_id, amount FROM payments WHERE id = ?");
                $paymentStmt->execute([$payment_id]);
                $payment = $paymentStmt->fetch(PDO::FETCH_ASSOC);
                
                if($payment) {
                    // Get user email
                    $userStmt = $pdo->prepare("SELECT email, full_name FROM users WHERE id = ?");
                    $userStmt->execute([$payment['user_id']]);
                    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if($user && isset($user['email'])) {
                        // Send email notification
                        $to = $user['email'];
                        $subject = "Payment Approved - CONQUER Gym";
                        $message = "
                            Dear " . ($user['full_name'] ?? 'Member') . ",
                            
                            Your payment has been approved!
                            
                            Payment Details:
                            - Transaction ID: " . ($payment['transaction_id'] ?? 'N/A') . "
                            - Amount: $" . number_format($payment['amount'] ?? 0, 2) . "
                            - Date: " . date('F j, Y') . "
                            - Status: Approved ✅
                            
                            Thank you for your payment!
                            
                            Best regards,
                            CONQUER Gym Team
                        ";
                        
                        $headers = "From: payments@conquergym.com\r\n";
                        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
                        
                        // Uncomment to actually send email
                        // mail($to, $subject, $message, $headers);
                    }
                }
            }
            
        } catch (PDOException $e) {
            $errors[] = "Payment #$payment_id: " . $e->getMessage();
            continue;
        }
    }
    
    // Commit transaction
    $pdo->commit();
    
    $response = [
        'success' => true,
        'count' => $successCount,
        'message' => "$successCount payment(s) approved successfully."
    ];
    
    if(!empty($errors)) {
        $response['errors'] = $errors;
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (PDOException $e) {
    // Rollback transaction on error
    if(isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    // Rollback transaction on error
    if(isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>