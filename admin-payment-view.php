<?php
session_start();
require_once 'config/database.php';

if(!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login.php');
    exit();
}

if(!isset($_GET['id'])) {
    header('Location: admin-payments.php');
    exit();
}

$payment_id = (int)$_GET['id'];

try {
    $pdo = Database::getInstance()->getConnection();
    
    // Get payment details with user info
    $stmt = $pdo->prepare("
        SELECT p.*, u.full_name, u.email, u.phone,
               gm.MembershipPlan, gm.JoinDate,
               admin.full_name as confirmed_by_name
        FROM payments p
        LEFT JOIN users u ON p.user_id = u.id
        LEFT JOIN gym_members gm ON u.email = gm.Email
        LEFT JOIN users admin ON p.confirmed_by = admin.id
        WHERE p.id = ?
    ");
    
    $stmt->execute([$payment_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$payment) {
        $_SESSION['admin_message'] = "Payment not found.";
        $_SESSION['admin_message_type'] = 'danger';
        header('Location: admin-payments.php');
        exit();
    }
    
} catch (PDOException $e) {
    $_SESSION['admin_message'] = "Error loading payment: " . $e->getMessage();
    $_SESSION['admin_message_type'] = 'danger';
    header('Location: admin-payments.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Details | Admin</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&family=Montserrat:wght@900&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS -->
    <link rel="stylesheet" href="dashboard-style.css">
    
    <style>
        .payment-details-container {
            padding: 2rem;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .payment-details-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .payment-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .payment-body {
            padding: 2rem;
        }
        
        .detail-group {
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #e9ecef;
        }
        
        .detail-group:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .detail-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
            display: block;
        }
        
        .detail-value {
            color: #2f3542;
            font-size: 1.1rem;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .status-completed {
            background: rgba(46, 213, 115, 0.15);
            color: #2ed573;
            border: 1px solid rgba(46, 213, 115, 0.3);
        }
        
        .status-pending {
            background: rgba(255, 165, 2, 0.15);
            color: #ffa502;
            border: 1px solid rgba(255, 165, 2, 0.3);
        }
        
        .status-failed {
            background: rgba(255, 71, 87, 0.15);
            color: #ff4757;
            border: 1px solid rgba(255, 71, 87, 0.3);
        }
        
        .status-refunded {
            background: rgba(108, 92, 231, 0.15);
            color: #6c5ce7;
            border: 1px solid rgba(108, 92, 231, 0.3);
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 2px solid #e9ecef;
        }
        
        .receipt-preview {
            max-width: 300px;
            margin: 1rem 0;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .receipt-preview img {
            width: 100%;
            height: auto;
            display: block;
        }
        
        .back-button {
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <?php include 'admin-sidebar.php'; ?>
    
    <div class="main-content">
        <div class="top-bar">
            <div class="back-button">
                <button class="btn-secondary" onclick="window.history.back()">
                    <i class="fas fa-arrow-left"></i> Back to Payments
                </button>
            </div>
        </div>
        
        <div class="dashboard-content">
            <div class="payment-details-container">
                <div class="payment-details-card">
                    <div class="payment-header">
                        <div>
                            <h2 style="margin: 0; color: white;">Payment Details</h2>
                            <p style="margin: 0.5rem 0 0 0; opacity: 0.9;">
                                Transaction ID: <?php echo htmlspecialchars($payment['transaction_id'] ?? 'N/A'); ?>
                            </p>
                        </div>
                        <span class="status-badge status-<?php echo htmlspecialchars($payment['status'] ?? 'pending'); ?>">
                            <?php echo ucfirst(htmlspecialchars($payment['status'] ?? 'Pending')); ?>
                        </span>
                    </div>
                    
                    <div class="payment-body">
                        <div class="detail-group">
                            <span class="detail-label">Payment Information</span>
                            <div class="detail-value">
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                                    <div>
                                        <strong>Amount:</strong><br>
                                        <span style="font-size: 1.5rem; color: #2f3542;">
                                            $<?php echo number_format($payment['amount'] ?? 0, 2); ?>
                                        </span>
                                    </div>
                                    <div>
                                        <strong>Payment Method:</strong><br>
                                        <?php echo ucwords(str_replace('_', ' ', $payment['payment_method'] ?? 'Unknown')); ?>
                                    </div>
                                    <div>
                                        <strong>Date:</strong><br>
                                        <?php echo date('F j, Y g:i A', strtotime($payment['payment_date'] ?? 'now')); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="detail-group">
                            <span class="detail-label">Member Information</span>
                            <div class="detail-value">
                                <strong><?php echo htmlspecialchars($payment['full_name'] ?? 'Unknown User'); ?></strong><br>
                                Email: <?php echo htmlspecialchars($payment['email'] ?? 'N/A'); ?><br>
                                Phone: <?php echo htmlspecialchars($payment['phone'] ?? 'N/A'); ?><br>
                                Membership: <?php echo htmlspecialchars($payment['MembershipPlan'] ?? 'N/A'); ?>
                            </div>
                        </div>
                        
                        <?php if($payment['notes']): ?>
                        <div class="detail-group">
                            <span class="detail-label">Notes</span>
                            <div class="detail-value">
                                <?php echo nl2br(htmlspecialchars($payment['notes'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if($payment['receipt_image']): ?>
                        <div class="detail-group">
                            <span class="detail-label">Receipt / Proof of Payment</span>
                            <div class="receipt-preview">
                                <?php if(pathinfo($payment['receipt_image'], PATHINFO_EXTENSION) === 'pdf'): ?>
                                    <a href="<?php echo htmlspecialchars($payment['receipt_image']); ?>" target="_blank" class="btn-primary">
                                        <i class="fas fa-file-pdf"></i> View PDF Receipt
                                    </a>
                                <?php else: ?>
                                    <img src="<?php echo htmlspecialchars($payment['receipt_image']); ?>" alt="Receipt">
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if($payment['status'] === 'completed' && $payment['confirmed_by_name']): ?>
                        <div class="detail-group">
                            <span class="detail-label">Confirmation Details</span>
                            <div class="detail-value">
                                Confirmed by: <?php echo htmlspecialchars($payment['confirmed_by_name']); ?><br>
                                Date: <?php echo date('F j, Y g:i A', strtotime($payment['confirmation_date'] ?? 'now')); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if($payment['status'] === 'pending'): ?>
                        <div class="action-buttons">
                            <button class="btn-success" onclick="approvePayment(<?php echo $payment_id; ?>)">
                                <i class="fas fa-check"></i> Approve Payment
                            </button>
                            <button class="btn-danger" onclick="rejectPayment(<?php echo $payment_id; ?>)">
                                <i class="fas fa-times"></i> Reject Payment
                            </button>
                        </div>
                        <?php elseif($payment['status'] === 'completed'): ?>
                        <div class="action-buttons">
                            <button class="btn-warning" onclick="refundPayment(<?php echo $payment_id; ?>)">
                                <i class="fas fa-undo"></i> Refund Payment
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function approvePayment(paymentId) {
            if(confirm('Are you sure you want to approve this payment?')) {
                window.location.href = 'admin-process-payment.php?action=approve&id=' + paymentId;
            }
        }
        
        function rejectPayment(paymentId) {
            if(confirm('Are you sure you want to reject this payment?')) {
                window.location.href = 'admin-process-payment.php?action=reject&id=' + paymentId;
            }
        }
        
        function refundPayment(paymentId) {
            const reason = prompt('Please enter the reason for refund:');
            if(reason) {
                window.location.href = 'admin-process-payment.php?action=refund&id=' + paymentId + '&reason=' + encodeURIComponent(reason);
            }
        }
    </script>
</body>
</html>