<?php
session_start();
require_once 'config/database.php';

// Enable FULL debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Debug: Output everything at the beginning
echo "<!-- DEBUG START -->\n";
echo "<!-- GET parameters: " . print_r($_GET, true) . " -->\n";
echo "<!-- SESSION user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . " -->\n";
echo "<!-- SESSION user_type: " . ($_SESSION['user_type'] ?? 'NOT SET') . " -->\n";

// Check if user is logged in as admin
if(!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo "<!-- ERROR: Not logged in as admin -->\n";
    header('Location: login.php');
    exit();
}

echo "<!-- User is logged in as admin -->\n";

// Check if action and ID are provided
if(!isset($_GET['action']) || !isset($_GET['id'])) {
    echo "<!-- ERROR: Missing parameters. Action exists: " . (isset($_GET['action']) ? 'YES' : 'NO') . " -->\n";
    echo "<!-- ERROR: Missing parameters. ID exists: " . (isset($_GET['id']) ? 'YES' : 'NO') . " -->\n";
    $_SESSION['admin_message'] = "Invalid request parameters. Action: " . (isset($_GET['action']) ? $_GET['action'] : 'NOT SET') . ", ID: " . (isset($_GET['id']) ? $_GET['id'] : 'NOT SET');
    $_SESSION['admin_message_type'] = 'danger';
    header('Location: admin-payments.php');
    exit();
}

echo "<!-- Parameters received - Action: " . $_GET['action'] . ", ID: " . $_GET['id'] . " -->\n";

$action = $_GET['action'];
$payment_id = (int)$_GET['id'];
$admin_id = $_SESSION['user_id'];

echo "<!-- Processed - Action: $action, Payment ID: $payment_id, Admin ID: $admin_id -->\n";

// Validate action
$valid_actions = ['approve', 'reject', 'refund'];
if(!in_array($action, $valid_actions)) {
    echo "<!-- ERROR: Invalid action: $action -->\n";
    $_SESSION['admin_message'] = "Invalid action specified: $action";
    $_SESSION['admin_message_type'] = 'danger';
    header('Location: admin-payments.php');
    exit();
}

echo "<!-- Action is valid -->\n";

try {
    $pdo = Database::getInstance()->getConnection();
    echo "<!-- Database connection successful -->\n";
    
    // First, check if payment exists
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE id = ?");
    $stmt->execute([$payment_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<!-- Payment query executed. Found: " . ($payment ? 'YES' : 'NO') . " -->\n";
    
    if(!$payment) {
        echo "<!-- ERROR: Payment #$payment_id not found in database -->\n";
        $_SESSION['admin_message'] = "Payment #$payment_id not found.";
        $_SESSION['admin_message_type'] = 'danger';
        header('Location: admin-payments.php');
        exit();
    }
    
    echo "<!-- Payment found: ID=" . $payment['id'] . ", Status=" . ($payment['status'] ?? 'N/A') . " -->\n";
    
    // Determine new status based on action
    switch($action) {
        case 'approve':
            $newStatus = 'completed';
            $message = "Payment approved successfully!";
            echo "<!-- Setting status to: completed -->\n";
            break;
        case 'reject':
            $newStatus = 'failed';
            $message = "Payment rejected.";
            echo "<!-- Setting status to: failed -->\n";
            break;
        case 'refund':
            $newStatus = 'refunded';
            $message = "Payment refunded.";
            $reason = isset($_GET['reason']) ? $_GET['reason'] : 'Refund processed';
            echo "<!-- Setting status to: refunded -->\n";
            break;
    }
    
    // Build the update query
    $sql = "UPDATE payments SET 
            status = :status,
            confirmed_by = :admin_id,
            confirmation_date = NOW(),
            updated_at = NOW()";
    
    $params = [
        ':status' => $newStatus,
        ':admin_id' => $admin_id,
        ':payment_id' => $payment_id
    ];
    
    // Add notes for refund
    if($action == 'refund' && isset($reason)) {
        $sql .= ", notes = CONCAT(COALESCE(notes, ''), '\n[REFUND] ', :reason)";
        $params[':reason'] = $reason;
    }
    
    $sql .= " WHERE id = :payment_id";
    
    echo "<!-- SQL Query: $sql -->\n";
    echo "<!-- Parameters: " . print_r($params, true) . " -->\n";
    
    // Execute the update
    $updateStmt = $pdo->prepare($sql);
    $result = $updateStmt->execute($params);
    
    echo "<!-- Update executed. Result: " . ($result ? 'SUCCESS' : 'FAILED') . " -->\n";
    echo "<!-- Rows affected: " . $updateStmt->rowCount() . " -->\n";
    
    if($result && $updateStmt->rowCount() > 0) {
        $_SESSION['admin_message'] = $message;
        $_SESSION['admin_message_type'] = 'success';
        
        // Log success
        error_log("Payment #$payment_id updated successfully. Status changed to: $newStatus");
        echo "<!-- Success message set in session -->\n";
    } else {
        $_SESSION['admin_message'] = "No changes made. Payment may already be in this status.";
        $_SESSION['admin_message_type'] = 'warning';
        echo "<!-- Warning: No changes made -->\n";
    }
    
    echo "<!-- Redirecting to admin-payments.php -->\n";
    header('Location: admin-payments.php');
    exit();
    
} catch (PDOException $e) {
    // Log detailed error
    error_log("Database Error in admin-process-payment.php: " . $e->getMessage());
    error_log("SQL Error Info: " . print_r($pdo->errorInfo(), true));
    
    echo "<!-- PDOException: " . $e->getMessage() . " -->\n";
    $_SESSION['admin_message'] = "Error updating payment: " . $e->getMessage();
    $_SESSION['admin_message_type'] = 'danger';
    header('Location: admin-payments.php');
    exit();
} catch (Exception $e) {
    error_log("General Error in admin-process-payment.php: " . $e->getMessage());
    
    echo "<!-- Exception: " . $e->getMessage() . " -->\n";
    $_SESSION['admin_message'] = "An error occurred: " . $e->getMessage();
    $_SESSION['admin_message_type'] = 'danger';
    header('Location: admin-payments.php');
    exit();
}
?>