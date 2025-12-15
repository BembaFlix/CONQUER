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

// Get action and payment ID
$action = isset($_GET['action']) ? $_GET['action'] : '';
$paymentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$reason = isset($_GET['reason']) ? $_GET['reason'] : '';

// Validate inputs
if(empty($action) || $paymentId <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit();
}

try {
    $pdo = Database::getInstance()->getConnection();
    
    // Check what columns exist in payments table
    $columnsStmt = $pdo->query("SHOW COLUMNS FROM payments");
    $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Determine which columns to update
    $hasConfirmedBy = in_array('confirmed_by', $columns);
    $hasConfirmedAt = in_array('confirmed_at', $columns);
    $hasUpdatedAt = in_array('updated_at', $columns);
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Get payment details first
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE id = ?");
    $stmt->execute([$paymentId]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$payment) {
        throw new Exception("Payment not found");
    }
    
    $response = [];
    
    switch($action) {
        case 'approve':
            // Build UPDATE query based on available columns
            $updateFields = ["status = 'completed'"];
            $updateParams = [];
            
            if($hasConfirmedBy) {
                $updateFields[] = "confirmed_by = ?";
                $updateParams[] = $_SESSION['user_id'];
            }
            
            if($hasConfirmedAt) {
                $updateFields[] = "confirmed_at = NOW()";
            }
            
            if($hasUpdatedAt) {
                $updateFields[] = "updated_at = NOW()";
            }
            
            $updateSql = "UPDATE payments SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $updateParams[] = $paymentId;
            
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute($updateParams);
            
            // If this is for a subscription, update user's subscription (if table exists)
            if(!empty($payment['subscription_id']) && !empty($payment['user_id'])) {
                try {
                    // Check if user_subscriptions table exists
                    $subscriptionTableExists = $pdo->query("SHOW TABLES LIKE 'user_subscriptions'")->rowCount() > 0;
                    
                    if($subscriptionTableExists) {
                        $subscriptionStmt = $pdo->prepare("
                            UPDATE user_subscriptions 
                            SET status = 'active',
                                start_date = CURDATE(),
                                end_date = DATE_ADD(CURDATE(), INTERVAL 1 MONTH)
                            WHERE id = ? AND user_id = ?
                        ");
                        $subscriptionStmt->execute([$payment['subscription_id'], $payment['user_id']]);
                    }
                } catch (Exception $e) {
                    // Log but don't fail the payment update
                    error_log("Subscription update error: " . $e->getMessage());
                }
            }
            
            $response = ['success' => true, 'message' => 'Payment approved successfully'];
            break;
            
        case 'reject':
            // Build UPDATE query for rejection
            $updateFields = ["status = 'failed'"];
            $updateParams = [];
            
            // Add reason to notes
            if(!empty($reason)) {
                $updateFields[] = "notes = CONCAT(COALESCE(notes, ''), ' [Rejected: " . addslashes($reason) . "]')";
            }
            
            if($hasConfirmedBy) {
                $updateFields[] = "confirmed_by = ?";
                $updateParams[] = $_SESSION['user_id'];
            }
            
            if($hasConfirmedAt) {
                $updateFields[] = "confirmed_at = NOW()";
            }
            
            if($hasUpdatedAt) {
                $updateFields[] = "updated_at = NOW()";
            }
            
            $updateSql = "UPDATE payments SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $updateParams[] = $paymentId;
            
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute($updateParams);
            
            $response = ['success' => true, 'message' => 'Payment rejected successfully'];
            break;
            
        case 'refund':
            // Build UPDATE query for refund
            $updateFields = ["status = 'refunded'"];
            $updateParams = [];
            
            // Add reason to notes
            if(!empty($reason)) {
                $updateFields[] = "notes = CONCAT(COALESCE(notes, ''), ' [Refunded: " . addslashes($reason) . "]')";
            }
            
            if($hasConfirmedBy) {
                $updateFields[] = "confirmed_by = ?";
                $updateParams[] = $_SESSION['user_id'];
            }
            
            if($hasConfirmedAt) {
                $updateFields[] = "confirmed_at = NOW()";
            }
            
            if($hasUpdatedAt) {
                $updateFields[] = "updated_at = NOW()";
            }
            
            $updateSql = "UPDATE payments SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $updateParams[] = $paymentId;
            
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute($updateParams);
            
            $response = ['success' => true, 'message' => 'Payment refunded successfully'];
            break;
            
        default:
            throw new Exception("Invalid action");
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (Exception $e) {
    // Rollback on error
    if(isset($pdo)) {
        $pdo->rollBack();
    }
    
    // Log error
    error_log("Payment processing error: " . $e->getMessage());
    
    // Return error response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Error processing payment: ' . $e->getMessage()
    ]);
}
?>