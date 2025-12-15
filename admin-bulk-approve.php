<?php
session_start();

// Debug mode
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

// Check if user is logged in as admin
if(!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$paymentIds = isset($input['ids']) ? $input['ids'] : [];

// Validate input
if(empty($paymentIds) || !is_array($paymentIds)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No payments selected']);
    exit();
}

try {
    $pdo = Database::getInstance()->getConnection();
    
    // Prepare statement for updating payments
    $stmt = $pdo->prepare("
        UPDATE payments 
        SET status = 'completed', 
            confirmed_by = ?,
            confirmed_at = NOW()
        WHERE id = ? AND status = 'pending'
    ");
    
    $approvedCount = 0;
    
    foreach($paymentIds as $paymentId) {
        $paymentId = (int)$paymentId;
        if($paymentId > 0) {
            $stmt->execute([$_SESSION['user_id'], $paymentId]);
            if($stmt->rowCount() > 0) {
                $approvedCount++;
            }
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'approved' => $approvedCount,
        'message' => "Successfully approved $approvedCount payment(s)"
    ]);
    
} catch (Exception $e) {
    // Log error
    error_log("Bulk approve error: " . $e->getMessage());
    
    // Return error response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Error processing bulk approval: ' . $e->getMessage()
    ]);
}
?>