<?php
session_start();

// Debug mode
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

// Check if user is logged in as admin
if(!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Initialize variables
$payments = [];
$totalRevenue = 0;
$monthlyRevenue = 0;
$pendingPayments = 0;
$completedPayments = 0;
$paymentMethods = [];
$hasError = false;
$errorMessage = '';
$tableExists = false;
$paymentCount = 0;

// Get filter parameters with proper initialization
$status = isset($_GET['status']) ? $_GET['status'] : '';
$payment_method = isset($_GET['payment_method']) ? $_GET['payment_method'] : '';
$month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20; // Items per page
$offset = ($page - 1) * $limit;

try {
    $pdo = Database::getInstance()->getConnection();
    
    // Check if payments table exists
    $tableExists = $pdo->query("SHOW TABLES LIKE 'payments'")->rowCount() > 0;
    
    if(!$tableExists) {
        $hasError = true;
        $errorMessage = "Payments table not found. Please run the setup script.";
    } else {
        // Build WHERE clause based on filters
        $whereClauses = [];
        $params = [];
        
        if($status && $status !== '') {
            $whereClauses[] = "p.status = ?";
            $params[] = $status;
        }

        if($payment_method && $payment_method !== '') {
            $whereClauses[] = "p.payment_method = ?";
            $params[] = $payment_method;
        }

        if($month && $month !== '') {
            $whereClauses[] = "DATE_FORMAT(p.payment_date, '%Y-%m') = ?";
            $params[] = $month;
        }
        
        if($search && $search !== '') {
            $whereClauses[] = "(p.transaction_id LIKE ? OR p.notes LIKE ? OR u.full_name LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $whereSQL = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';
        
        // Get payment count for pagination
        $countSql = "SELECT COUNT(*) as total FROM payments p 
                     LEFT JOIN users u ON p.user_id = u.id 
                     $whereSQL";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        $paymentCount = (int)$totalCount;
        
        // Calculate total pages
        $totalPages = ceil($paymentCount / $limit);
        
        // Get all payments with filters and pagination
        try {
            $sql = "SELECT p.*, u.full_name, u.email 
                    FROM payments p 
                    LEFT JOIN users u ON p.user_id = u.id 
                    $whereSQL 
                    ORDER BY p.payment_date DESC 
                    LIMIT ? OFFSET ?";
            
            // Add limit and offset to params
            $queryParams = $params;
            $queryParams[] = $limit;
            $queryParams[] = $offset;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($queryParams);
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate stats (from filtered results if filters applied, otherwise from all)
            $statsSql = "SELECT 
                        SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_revenue,
                        SUM(CASE WHEN status = 'completed' AND DATE_FORMAT(payment_date, '%Y-%m') = ? THEN amount ELSE 0 END) as monthly_revenue,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count
                        FROM payments";
            
            $currentMonth = date('Y-m');
            $statsStmt = $pdo->prepare($statsSql);
            $statsStmt->execute([$currentMonth]);
            $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
            
            $totalRevenue = $stats['total_revenue'] ?? 0;
            $monthlyRevenue = $stats['monthly_revenue'] ?? 0;
            $pendingPayments = $stats['pending_count'] ?? 0;
            $completedPayments = $stats['completed_count'] ?? 0;
            
            // Get payment methods statistics
            $paymentMethodsData = $pdo->query("
                SELECT payment_method, 
                       COUNT(*) as count,
                       SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_amount
                FROM payments 
                GROUP BY payment_method
            ")->fetchAll(PDO::FETCH_ASSOC);
            $paymentMethods = $paymentMethodsData ?: [];
            
        } catch (Exception $e) {
            $hasError = true;
            $errorMessage = "Error reading payments: " . $e->getMessage();
            error_log("Payment query error: " . $e->getMessage());
        }
    }
    
} catch (PDOException $e) {
    $hasError = true;
    $errorMessage = "Database connection error: " . $e->getMessage();
    error_log("Database connection error: " . $e->getMessage());
}

// Check for messages from other pages
if(isset($_SESSION['admin_message'])) {
    $admin_message = $_SESSION['admin_message'];
    $admin_message_type = $_SESSION['admin_message_type'];
    unset($_SESSION['admin_message']);
    unset($_SESSION['admin_message_type']);
}

// Check if there are any pending payments for badge
$pendingCount = $pendingPayments;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Management | Admin Dashboard</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&family=Montserrat:wght@900&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS -->
    <link rel="stylesheet" href="dashboard-style.css">
    <style>
        /* Additional styles for payments management */
        .payments-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .payments-table th {
            background: #f8f9fa;
            font-weight: 600;
            padding: 1.25rem 1rem;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
            color: #495057;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .payments-table td {
            padding: 1.25rem 1rem;
            border-bottom: 1px solid #e9ecef;
            vertical-align: top;
            color: #495057;
        }
        
        .payments-table tr:hover {
            background-color: #f8f9fa;
        }
        
        .payments-table small {
            font-size: 0.85rem;
            color: #6c757d;
            line-height: 1.4;
        }
        
        /* Stats grid small */
        .stats-grid-small {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 1.5rem 0;
        }
        
        .stat-card-small {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid #e9ecef;
        }
        
        .stat-card-small:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .stat-card-small.revenue {
            border-top: 4px solid #2ed573;
        }
        
        .stat-card-small.pending {
            border-top: 4px solid #ffa502;
            position: relative;
        }
        
        .stat-card-small.pending::after {
            content: '<?php echo $pendingCount; ?>';
            position: absolute;
            top: -10px;
            right: -10px;
            background: #ff4757;
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: bold;
        }
        
        .stat-card-small.completed {
            border-top: 4px solid #3498db;
        }
        
        .stat-card-small.methods {
            border-top: 4px solid #9b59b6;
        }
        
        .stat-card-small h3 {
            margin: 0;
            color: #2f3542;
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }
        
        .stat-card-small p {
            margin: 0;
            color: #6c757d;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        /* Payment status badges */
        .payment-status {
            display: inline-block;
            padding: 0.4rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            min-width: 100px;
            text-align: center;
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
        
        /* Payment amount styling */
        .payment-amount {
            font-weight: 700;
            font-size: 1.1rem;
            color: #2f3542;
        }
        
        /* Button styles */
        .btn-sm {
            padding: 0.5rem 0.75rem;
            font-size: 0.85rem;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            background: #f8f9fa;
            color: #495057;
            transition: all 0.3s;
            text-decoration: none;
            font-family: inherit;
            font-weight: 500;
            border: 1px solid #dee2e6;
            min-width: 40px;
        }
        
        .btn-sm:hover {
            background: #e9ecef;
            transform: translateY(-1px);
            text-decoration: none;
        }
        
        .btn-sm.btn-success {
            background: #2ed573;
            color: white;
            border-color: #2ed573;
        }
        
        .btn-sm.btn-success:hover {
            background: #25c464;
            border-color: #25c464;
        }
        
        .btn-sm.btn-danger {
            background: #ff4757;
            color: white;
            border-color: #ff4757;
        }
        
        .btn-sm.btn-danger:hover {
            background: #ff3742;
            border-color: #ff3742;
        }
        
        .btn-sm.btn-info {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }
        
        .btn-sm.btn-info:hover {
            background: #2980b9;
            border-color: #2980b9;
        }
        
        /* Filters */
        .payment-filters {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .payment-filters > div {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .payment-filters label {
            font-weight: 600;
            color: #2f3542;
            font-size: 0.9rem;
        }
        
        .payment-filters select,
        .payment-filters input[type="month"],
        .payment-filters input[type="text"],
        .payment-filters input[type="search"] {
            padding: 0.75rem;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.95rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            width: 100%;
        }
        
        .payment-filters select:focus,
        .payment-filters input[type="month"]:focus,
        .payment-filters input[type="text"]:focus,
        .payment-filters input[type="search"]:focus {
            outline: none;
            border-color: #ff4757;
            box-shadow: 0 0 0 3px rgba(255, 71, 87, 0.1);
        }
        
        .filter-actions {
            display: flex;
            align-items: flex-end;
            gap: 0.5rem;
        }
        
        /* Payment method icons */
        .payment-method {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .payment-method-icon {
            width: 24px;
            height: 24px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            color: white;
        }
        
        .method-credit_card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .method-gcash {
            background: linear-gradient(135deg, #0066a1 0%, #00a3e0 100%);
        }
        
        .method-paymaya {
            background: linear-gradient(135deg, #00a859 0%, #00c853 100%);
        }
        
        .method-cash {
            background: linear-gradient(135deg, #2ed573 0%, #1dd1a1 100%);
        }
        
        .method-bank_transfer {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
        }
        
        .method-unknown {
            background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);
        }
        
        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 3rem 0;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 3rem;
            color: #e9ecef;
            margin-bottom: 1rem;
        }
        
        /* Alert messages */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        .alert-success {
            background: rgba(46, 213, 115, 0.1);
            border: 1px solid rgba(46, 213, 115, 0.2);
            color: #2ed573;
        }
        
        .alert-danger {
            background: rgba(255, 71, 87, 0.1);
            border: 1px solid rgba(255, 71, 87, 0.2);
            color: #ff4757;
        }
        
        .alert-info {
            background: rgba(52, 152, 219, 0.1);
            border: 1px solid rgba(52, 152, 219, 0.2);
            color: #3498db;
        }
        
        .alert-warning {
            background: rgba(255, 165, 2, 0.1);
            border: 1px solid rgba(255, 165, 2, 0.2);
            color: #ffa502;
        }
        
        /* Receipt preview */
        .receipt-preview {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .receipt-preview img {
            max-width: 90%;
            max-height: 90%;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        /* Quick actions */
        .quick-actions {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #e9ecef;
        }
        
        .pagination .page-info {
            color: #6c757d;
            font-size: 0.9rem;
            margin-right: 1rem;
        }
        
        /* Loading spinner */
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 2rem;
        }
        
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .payments-table {
                min-width: 1000px;
            }
        }
        
        @media (max-width: 768px) {
            .payment-filters {
                grid-template-columns: 1fr;
            }
            
            .filter-actions {
                grid-column: 1;
            }
            
            .stats-grid-small {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .table-container {
                overflow-x: auto;
            }
            
            .quick-actions {
                flex-direction: column;
            }
        }
        
        @media (max-width: 480px) {
            .stats-grid-small {
                grid-template-columns: 1fr;
            }
            
            .quick-actions {
                flex-direction: column;
            }
        }
        
        /* Error state */
        .error-state {
            text-align: center;
            padding: 3rem 1rem;
            background: #fff5f5;
            border-radius: 8px;
            border: 1px solid #fed7d7;
        }
        
        .error-state i {
            font-size: 3rem;
            color: #fc8181;
            margin-bottom: 1rem;
        }
        
        /* Filter feedback */
        .filter-feedback {
            background: #e8f4fd;
            border: 1px solid #b6d4fe;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filter-feedback .active-filters {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .filter-tag {
            background: #3498db;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .filter-tag .remove {
            cursor: pointer;
            font-weight: bold;
        }
        
        /* User info */
        .user-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }
        
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #667eea;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        /* Quick filters styling */
        .quick-filters-container {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e9ecef;
        }
        
        .quick-filter-btn {
            margin: 0.25rem;
        }
        
        /* Search bar improvements */
        .search-container {
            position: relative;
            flex: 1;
        }
        
        .search-container i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        
        .search-container input {
            padding-left: 2.5rem;
            width: 100%;
        }
        
        /* Tooltips */
        .tooltip {
            position: relative;
            display: inline-block;
        }
        
        .tooltip .tooltip-text {
            visibility: hidden;
            width: 200px;
            background-color: #333;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 5px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            margin-left: -100px;
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 0.8rem;
        }
        
        .tooltip:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }
    </style>
</head>
<body>
    <?php include 'admin-sidebar.php'; ?>

    <div class="main-content">
        <div class="top-bar">
            <div class="search-container">
                <i class="fas fa-search"></i>
                <form method="GET" action="" style="display: inline-block; width: 100%;">
                    <input type="text" name="search" placeholder="Search payments by ID, name, or notes..." 
                           value="<?php echo htmlspecialchars($search); ?>"
                           id="searchInput">
                </form>
            </div>
            <div class="top-bar-actions">
                <button class="btn-primary" onclick="window.location.href='admin-add-payment.php'">
                    <i class="fas fa-plus"></i> Record Payment
                </button>
                <?php if($pendingCount > 0): ?>
                <button class="btn-warning" onclick="window.location.href='admin-payments.php?status=pending'" 
                        style="background: #ffa502; color: white;">
                    <i class="fas fa-clock"></i> Pending (<?php echo $pendingCount; ?>)
                </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="welcome-banner">
                <div class="welcome-content">
                    <h1>Payment Management</h1>
                    <p>Monitor and manage all payments</p>
                </div>
                <div class="welcome-stats">
                    <div class="stat">
                        <h3>$<?php echo number_format($totalRevenue, 2); ?></h3>
                        <p>Total Revenue</p>
                    </div>
                    <div class="stat">
                        <h3><?php echo $paymentCount; ?></h3>
                        <p>Total Payments</p>
                    </div>
                </div>
            </div>

            <!-- Notifications -->
            <?php if(isset($admin_message)): ?>
            <div class="alert alert-<?php echo $admin_message_type; ?>">
                <i class="fas <?php echo $admin_message_type == 'success' ? 'fa-check-circle' : ($admin_message_type == 'danger' ? 'fa-exclamation-circle' : 'fa-info-circle'); ?>"></i>
                <?php echo htmlspecialchars($admin_message); ?>
            </div>
            <?php endif; ?>
            
            <?php if($hasError): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($errorMessage); ?>
                <small style="margin-left: auto;">Please check if the payments table exists in the database.</small>
            </div>
            <?php endif; ?>

            <!-- Quick Stats -->
            <div class="stats-grid-small">
                <div class="stat-card-small revenue">
                    <h3>$<?php echo number_format($monthlyRevenue, 2); ?></h3>
                    <p>This Month</p>
                </div>
                <div class="stat-card-small pending">
                    <h3><?php echo $pendingPayments; ?></h3>
                    <p>Pending</p>
                </div>
                <div class="stat-card-small completed">
                    <h3><?php echo $completedPayments; ?></h3>
                    <p>Completed</p>
                </div>
                <div class="stat-card-small methods">
                    <h3><?php echo is_array($paymentMethods) ? count($paymentMethods) : 0; ?></h3>
                    <p>Payment Methods</p>
                </div>
            </div>

            <!-- Active Filters Display -->
            <?php if($status || $payment_method || $month != date('Y-m') || $search): ?>
            <div class="filter-feedback">
                <div>
                    <strong>Active Filters:</strong>
                    <div class="active-filters" style="margin-top: 0.5rem;">
                        <?php if($status): ?>
                        <span class="filter-tag">
                            Status: <?php echo ucfirst($status); ?>
                            <span class="remove" onclick="removeFilter('status')">×</span>
                        </span>
                        <?php endif; ?>
                        <?php if($payment_method): ?>
                        <span class="filter-tag">
                            Method: <?php echo ucwords(str_replace('_', ' ', $payment_method)); ?>
                            <span class="remove" onclick="removeFilter('payment_method')">×</span>
                        </span>
                        <?php endif; ?>
                        <?php if($month && $month != date('Y-m')): ?>
                        <span class="filter-tag">
                            Month: <?php echo date('F Y', strtotime($month . '-01')); ?>
                            <span class="remove" onclick="removeFilter('month')">×</span>
                        </span>
                        <?php endif; ?>
                        <?php if($search): ?>
                        <span class="filter-tag">
                            Search: "<?php echo htmlspecialchars($search); ?>"
                            <span class="remove" onclick="removeFilter('search')">×</span>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <button class="btn-sm" onclick="clearAllFilters()">
                    <i class="fas fa-times"></i> Clear All
                </button>
            </div>
            <?php endif; ?>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <button class="btn-success" onclick="markMultipleAsPaid()">
                    <i class="fas fa-check-double"></i> Mark Selected as Paid
                </button>
                <button class="btn-secondary" onclick="exportPayments()">
                    <i class="fas fa-file-export"></i> Export to Excel
                </button>
                <button class="btn-info" onclick="showPaymentSummary()">
                    <i class="fas fa-chart-bar"></i> View Summary
                </button>
                <button class="btn-warning" onclick="refreshPage()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>

            <!-- Filters -->
            <form method="GET" class="payment-filters" id="filterForm">
                <div>
                    <label>Status</label>
                    <select name="status" id="statusFilter">
                        <option value="">All Status</option>
                        <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="failed" <?php echo $status === 'failed' ? 'selected' : ''; ?>>Failed</option>
                        <option value="refunded" <?php echo $status === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                    </select>
                </div>
                <div>
                    <label>Payment Method</label>
                    <select name="payment_method" id="paymentMethodFilter">
                        <option value="">All Methods</option>
                        <option value="credit_card" <?php echo $payment_method === 'credit_card' ? 'selected' : ''; ?>>Credit Card</option>
                        <option value="gcash" <?php echo $payment_method === 'gcash' ? 'selected' : ''; ?>>GCash</option>
                        <option value="paymaya" <?php echo $payment_method === 'paymaya' ? 'selected' : ''; ?>>PayMaya</option>
                        <option value="bank_transfer" <?php echo $payment_method === 'bank_transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                        <option value="cash" <?php echo $payment_method === 'cash' ? 'selected' : ''; ?>>Cash</option>
                    </select>
                </div>
                <div>
                    <label>Month</label>
                    <input type="month" name="month" value="<?php echo htmlspecialchars($month); ?>" id="monthFilter">
                </div>
                <div>
                    <label>Search</label>
                    <input type="text" name="search" placeholder="Transaction ID, name, notes..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn-primary" id="applyFiltersBtn">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                    <button type="button" class="btn-secondary" onclick="clearFilters()">
                        <i class="fas fa-times"></i> Clear
                    </button>
                </div>
            </form>

            <!-- Quick Filter Buttons -->
            <div class="quick-filters-container">
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <button type="button" class="btn-sm quick-filter-btn" onclick="applyQuickFilter('status', 'pending')">
                        Pending (<?php echo $pendingPayments; ?>)
                    </button>
                    <button type="button" class="btn-sm quick-filter-btn" onclick="applyQuickFilter('status', 'completed')">
                        Completed (<?php echo $completedPayments; ?>)
                    </button>
                    <button type="button" class="btn-sm quick-filter-btn" onclick="applyQuickFilter('payment_method', 'cash')">
                        Cash Payments
                    </button>
                    <button type="button" class="btn-sm quick-filter-btn" onclick="applyQuickFilter('payment_method', 'gcash')">
                        GCash Payments
                    </button>
                    <button type="button" class="btn-sm quick-filter-btn" onclick="applyQuickFilter('payment_method', 'credit_card')">
                        Credit Card
                    </button>
                    <button type="button" class="btn-sm quick-filter-btn" onclick="applyQuickFilter('payment_method', 'paymaya')">
                        PayMaya
                    </button>
                    <button type="button" class="btn-sm quick-filter-btn" onclick="applyQuickFilter('payment_method', 'bank_transfer')">
                        Bank Transfer
                    </button>
                    <button type="button" class="btn-sm quick-filter-btn" onclick="applyQuickFilter('month', '<?php echo date('Y-m'); ?>')">
                        This Month
                    </button>
                </div>
            </div>

            <!-- Loading Spinner -->
            <div class="loading-spinner" id="loadingSpinner">
                <div class="spinner"></div>
                <p>Processing payment...</p>
            </div>

            <!-- Payments Table -->
            <div class="content-card">
                <div class="card-header">
                    <h3>Payment History 
                        <span style="font-size: 0.9rem; color: #6c757d; font-weight: normal;">
                            (Showing <?php echo count($payments); ?> of <?php echo $paymentCount; ?> records)
                        </span>
                    </h3>
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <?php if(is_array($payments) && count($payments) > 0): ?>
                        <div class="form-check" style="margin-right: 1rem;">
                            <input type="checkbox" id="selectAll" class="form-check-input">
                            <label for="selectAll" class="form-check-label">Select All</label>
                        </div>
                        <?php endif; ?>
                        <a href="admin-export-payments.php?<?php echo http_build_query($_GET); ?>" class="btn-sm">
                            <i class="fas fa-download"></i> Export CSV
                        </a>
                        <button class="btn-sm" onclick="printTable()">
                            <i class="fas fa-print"></i> Print
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if($hasError): ?>
                        <div class="error-state">
                            <i class="fas fa-database"></i>
                            <h3 style="color: #495057; margin: 1rem 0 0.5rem;">Database Error</h3>
                            <p style="margin-bottom: 1rem;">Unable to load payments. Please check:</p>
                            <ol style="text-align: left; display: inline-block; margin: 0 auto 2rem;">
                                <li>Database connection</li>
                                <li>Payments table exists</li>
                                <li>Table structure is correct</li>
                            </ol>
                            <button class="btn-primary" onclick="window.location.href='admin-payments.php'">
                                <i class="fas fa-redo"></i> Try Again
                            </button>
                        </div>
                    <?php elseif(is_array($payments) && count($payments) > 0): ?>
                        <div class="table-container">
                            <table class="payments-table" id="paymentsTable">
                                <thead>
                                    <tr>
                                        <th style="width: 50px;">
                                            <input type="checkbox" id="selectAllHeader">
                                        </th>
                                        <th>Transaction ID</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Date</th>
                                        <th>Method</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($payments as $payment): 
                                        // Get payment data with null checks
                                        $paymentId = isset($payment['id']) ? (int)$payment['id'] : 0;
                                        $transactionId = isset($payment['transaction_id']) ? htmlspecialchars($payment['transaction_id']) : 'N/A';
                                        $amount = isset($payment['amount']) ? (float)$payment['amount'] : 0.00;
                                        $paymentDate = isset($payment['payment_date']) ? $payment['payment_date'] : date('Y-m-d H:i:s');
                                        $paymentMethod = isset($payment['payment_method']) ? $payment['payment_method'] : 'unknown';
                                        $status = isset($payment['status']) ? $payment['status'] : 'pending';
                                        $receiptImage = isset($payment['receipt_image']) ? $payment['receipt_image'] : null;
                                        $notes = isset($payment['notes']) ? htmlspecialchars(substr($payment['notes'], 0, 50)) : '';
                                        $fullName = isset($payment['full_name']) ? htmlspecialchars($payment['full_name']) : 'N/A';
                                        $email = isset($payment['email']) ? htmlspecialchars($payment['email']) : '';
                                        
                                        // Format payment method display
                                        $methodDisplay = ucwords(str_replace('_', ' ', $paymentMethod));
                                        if($paymentMethod == 'gcash') $methodDisplay = 'GCash';
                                        if($paymentMethod == 'paymaya') $methodDisplay = 'PayMaya';
                                        
                                        // Determine icon
                                        $methodIcon = 'mobile-alt'; // default
                                        if($paymentMethod === 'credit_card') $methodIcon = 'credit-card';
                                        if($paymentMethod === 'cash') $methodIcon = 'money-bill';
                                        if($paymentMethod === 'bank_transfer') $methodIcon = 'university';
                                        
                                        // Method class for styling
                                        $methodClass = 'method-' . str_replace(' ', '_', $paymentMethod);
                                        
                                        // Format dates
                                        $displayDate = date('M j, Y', strtotime($paymentDate));
                                        $displayTime = date('g:i A', strtotime($paymentDate));
                                        
                                        // Get user initials for avatar
                                        $initials = '?';
                                        if($fullName !== 'N/A') {
                                            $nameParts = explode(' ', $fullName);
                                            $initials = '';
                                            foreach($nameParts as $part) {
                                                if(strlen($part) > 0) {
                                                    $initials .= strtoupper($part[0]);
                                                }
                                                if(strlen($initials) >= 2) break;
                                            }
                                            if(strlen($initials) === 0) $initials = '?';
                                        }
                                    ?>
                                        <tr data-transaction-id="<?php echo htmlspecialchars(strtolower($transactionId)); ?>"
                                            data-payment-status="<?php echo htmlspecialchars($status); ?>"
                                            data-payment-method="<?php echo htmlspecialchars($paymentMethod); ?>"
                                            data-payment-id="<?php echo $paymentId; ?>"
                                            data-payment-notes="<?php echo htmlspecialchars(strtolower($notes)); ?>"
                                            id="payment-row-<?php echo $paymentId; ?>">
                                            <td>
                                                <?php if($paymentId > 0): ?>
                                                <input type="checkbox" class="payment-checkbox" value="<?php echo $paymentId; ?>">
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="tooltip">
                                                    <strong style="color: #667eea; cursor: pointer;" 
                                                            onclick="copyToClipboard('<?php echo $transactionId; ?>')">
                                                        <?php echo $transactionId; ?>
                                                    </strong>
                                                    <span class="tooltip-text">Click to copy Transaction ID</span>
                                                </div>
                                                <?php if($notes): ?>
                                                <br><small title="<?php echo htmlspecialchars($payment['notes'] ?? ''); ?>">
                                                    <i class="fas fa-sticky-note"></i> <?php echo $notes; ?>...
                                                </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="user-info">
                                                    <div class="user-avatar" title="<?php echo $fullName; ?>">
                                                        <?php echo $initials; ?>
                                                    </div>
                                                    <div>
                                                        <div style="font-weight: 600;"><?php echo $fullName; ?></div>
                                                        <?php if($email): ?>
                                                        <small style="color: #6c757d; font-size: 0.8rem;"><?php echo $email; ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="payment-amount">
                                                <div style="font-weight: 700; color: #2f3542;">
                                                    $<?php echo number_format($amount, 2); ?>
                                                </div>
                                                <?php if(isset($payment['subscription_period'])): ?>
                                                <small style="color: #6c757d; font-size: 0.8rem;">
                                                    <?php echo htmlspecialchars($payment['subscription_period']); ?>
                                                </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                                                    <span style="font-weight: 600; color: #2f3542;">
                                                        <?php echo $displayDate; ?>
                                                    </span>
                                                    <span style="color: #6c757d; font-size: 0.85rem;">
                                                        <?php echo $displayTime; ?>
                                                    </span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="payment-method">
                                                    <div class="payment-method-icon <?php echo $methodClass; ?>">
                                                        <i class="fas fa-<?php echo $methodIcon; ?>"></i>
                                                    </div>
                                                    <span><?php echo $methodDisplay; ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="payment-status status-<?php echo $status; ?>">
                                                    <?php echo ucfirst($status); ?>
                                                </span>
                                                <?php if(isset($payment['confirmed_by'])): ?>
                                                <br><small style="font-size: 0.8rem; color: #6c757d;">
                                                    Confirmed by Admin #<?php echo $payment['confirmed_by']; ?>
                                                </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                                    <?php if($paymentId > 0): ?>
                                                    <button class="btn-sm btn-info" onclick="viewPaymentDetails(<?php echo $paymentId; ?>)" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <?php if($status === 'pending'): ?>
                                                        <button class="btn-sm btn-success" onclick="approvePayment(<?php echo $paymentId; ?>)" title="Approve Payment">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <button class="btn-sm btn-danger" onclick="rejectPayment(<?php echo $paymentId; ?>)" title="Reject Payment">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <?php if(!empty($receiptImage)): ?>
                                                        <button class="btn-sm" onclick="viewReceipt('<?php echo htmlspecialchars($receiptImage); ?>')" title="View Receipt">
                                                            <i class="fas fa-receipt"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <?php if($status === 'completed'): ?>
                                                        <button class="btn-sm" onclick="refundPayment(<?php echo $paymentId; ?>)" title="Refund Payment">
                                                            <i class="fas fa-undo"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <button class="btn-sm" onclick="editPayment(<?php echo $paymentId; ?>)" title="Edit Payment">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <div class="pagination">
                            <div class="page-info">
                                Page <?php echo $page; ?> of <?php echo $totalPages; ?>
                            </div>
                            <?php if($page > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="btn-sm">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                            <?php endif; ?>
                            
                            <?php for($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <?php if($i == $page): ?>
                                    <button class="btn-sm btn-primary"><?php echo $i; ?></button>
                                <?php else: ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" class="btn-sm">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if($page < $totalPages): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="btn-sm">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-credit-card"></i>
                            <h3 style="color: #495057; margin: 1rem 0 0.5rem;">
                                <?php if($status || $payment_method || $month != date('Y-m') || $search): ?>
                                    No payments matching the selected filters
                                <?php else: ?>
                                    No payments found
                                <?php endif; ?>
                            </h3>
                            <p style="margin-bottom: 2rem;">
                                <?php if($status || $payment_method || $month != date('Y-m') || $search): ?>
                                    Try changing your filter criteria or <a href="javascript:void(0)" onclick="clearFilters()">clear all filters</a>.
                                <?php else: ?>
                                    No payments have been recorded yet. Start by recording a payment.
                                <?php endif; ?>
                            </p>
                            <?php if(!($status || $payment_method || $month != date('Y-m') || $search)): ?>
                            <button class="btn-primary" onclick="window.location.href='admin-add-payment.php'">
                                <i class="fas fa-plus"></i> Record First Payment
                            </button>
                            <?php endif; ?>
                            <button class="btn-secondary" onclick="checkDatabase()" style="margin-top: 1rem;">
                                <i class="fas fa-database"></i> Check Database
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Payment Methods Summary -->
            <?php if(is_array($paymentMethods) && count($paymentMethods) > 0): ?>
            <div class="content-card" style="margin-top: 2rem;">
                <div class="card-header">
                    <h3>Payment Methods Summary</h3>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                        <?php foreach($paymentMethods as $method): 
                            $methodName = $method['payment_method'];
                            $methodDisplay = ucwords(str_replace('_', ' ', $methodName));
                            if($methodName == 'gcash') $methodDisplay = 'GCash';
                            if($methodName == 'paymaya') $methodDisplay = 'PayMaya';
                        ?>
                            <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div style="font-weight: 600;"><?php echo $methodDisplay; ?></div>
                                    <span class="badge" style="background: #3498db; color: white; padding: 0.25rem 0.5rem; border-radius: 4px;">
                                        <?php echo $method['count']; ?> payments
                                    </span>
                                </div>
                                <div style="margin-top: 0.5rem; font-size: 1.25rem; font-weight: 700; color: #2f3542;">
                                    $<?php echo number_format($method['total_amount'] ?? 0, 2); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Receipt Preview Modal -->
    <div class="receipt-preview" id="receiptPreview">
        <div style="position: relative;">
            <button onclick="closeReceipt()" style="position: absolute; top: -40px; right: 0; background: #ff4757; color: white; border: none; padding: 0.5rem; border-radius: 4px; cursor: pointer;">
                <i class="fas fa-times"></i> Close
            </button>
            <img src="" alt="Receipt" id="receiptImage">
        </div>
    </div>

    <script>
        // Enhanced JavaScript with better error handling
        
        function showLoading() {
            document.getElementById('loadingSpinner').style.display = 'block';
        }
        
        function hideLoading() {
            document.getElementById('loadingSpinner').style.display = 'none';
        }
        
        function approvePayment(paymentId) {
            console.log("Approving payment:", paymentId);
            
            if (!paymentId || paymentId <= 0) {
                alert("Error: Invalid payment ID.");
                return false;
            }
            
            const confirmMsg = `Are you sure you want to approve payment #${paymentId}?\nThis will mark the payment as completed.`;
            
            if (confirm(confirmMsg)) {
                showLoading();
                const url = `admin-process-payment.php?action=approve&id=${paymentId}&t=${Date.now()}`;
                console.log("Redirecting to:", url);
                
                // Show processing message
                const row = document.getElementById(`payment-row-${paymentId}`);
                if (row) {
                    const statusCell = row.querySelector('.payment-status');
                    if (statusCell) {
                        statusCell.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                    }
                }
                
                // Add timestamp to prevent caching
                window.location.href = url;
            }
            
            return false;
        }
        
        function rejectPayment(paymentId) {
            if (!paymentId || paymentId <= 0) {
                alert("Error: Invalid payment ID.");
                return false;
            }
            
            const reason = prompt('Please enter the reason for rejection (optional):');
            if (reason === null) return false; // User cancelled
            
            showLoading();
            const url = `admin-process-payment.php?action=reject&id=${paymentId}${reason ? '&reason=' + encodeURIComponent(reason) : ''}`;
            window.location.href = url;
            return false;
        }
        
        function refundPayment(paymentId) {
            if (!paymentId || paymentId <= 0) {
                alert("Error: Invalid payment ID.");
                return false;
            }
            
            const reason = prompt('Please enter the reason for refund (required):');
            if (reason === null || reason.trim() === '') {
                alert('Refund reason is required.');
                return false;
            }
            
            showLoading();
            const url = `admin-process-payment.php?action=refund&id=${paymentId}&reason=${encodeURIComponent(reason)}`;
            window.location.href = url;
            return false;
        }
        
        function editPayment(paymentId) {
            window.location.href = `admin-edit-payment.php?id=${paymentId}`;
            return false;
        }
        
        function viewPaymentDetails(paymentId) {
            window.open(`admin-payment-view.php?id=${paymentId}`, '_blank');
            return false;
        }
        
        function viewReceipt(imagePath) {
            if (!imagePath) {
                alert('No receipt image available.');
                return;
            }
            
            // Check if image exists
            const img = new Image();
            img.onload = function() {
                document.getElementById('receiptImage').src = imagePath;
                document.getElementById('receiptPreview').style.display = 'flex';
            };
            img.onerror = function() {
                alert('Receipt image not found or cannot be loaded.');
            };
            img.src = imagePath;
        }
        
        function closeReceipt() {
            document.getElementById('receiptPreview').style.display = 'none';
        }
        
        // Close receipt preview with ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeReceipt();
            }
        });
        
        // Close receipt preview when clicking outside
        document.getElementById('receiptPreview').addEventListener('click', function(e) {
            if (e.target === this) {
                closeReceipt();
            }
        });
        
        // Copy to clipboard function
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                // Show success message
                const alert = document.createElement('div');
                alert.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: #2ed573;
                    color: white;
                    padding: 1rem;
                    border-radius: 8px;
                    z-index: 9999;
                    animation: slideInRight 0.3s ease-out;
                `;
                alert.innerHTML = `<i class="fas fa-check"></i> Copied: ${text}`;
                document.body.appendChild(alert);
                
                setTimeout(() => {
                    alert.style.animation = 'slideOutRight 0.3s ease-out';
                    setTimeout(() => alert.remove(), 300);
                }, 2000);
                
                // Add CSS for animation
                if (!document.getElementById('copy-animation-style')) {
                    const style = document.createElement('style');
                    style.id = 'copy-animation-style';
                    style.textContent = `
                        @keyframes slideInRight {
                            from { transform: translateX(100%); opacity: 0; }
                            to { transform: translateX(0); opacity: 1; }
                        }
                        @keyframes slideOutRight {
                            from { transform: translateX(0); opacity: 1; }
                            to { transform: translateX(100%); opacity: 0; }
                        }
                    `;
                    document.head.appendChild(style);
                }
            }).catch(function(err) {
                console.error('Could not copy text: ', err);
                alert('Failed to copy text to clipboard');
            });
        }
        
        // Select all functionality
        document.getElementById('selectAllHeader')?.addEventListener('change', function(e) {
            const checkboxes = document.querySelectorAll('.payment-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = e.target.checked;
            });
        });
        
        function markMultipleAsPaid() {
            const selectedPayments = Array.from(document.querySelectorAll('.payment-checkbox:checked'))
                .map(cb => parseInt(cb.value))
                .filter(id => id > 0);
            
            if (selectedPayments.length === 0) {
                alert('Please select at least one payment to mark as paid.');
                return;
            }
            
            const confirmMsg = `Are you sure you want to mark ${selectedPayments.length} payment(s) as completed?\nThis action cannot be undone.`;
            
            if (confirm(confirmMsg)) {
                showLoading();
                // In a real implementation, you would use AJAX
                // For now, redirect to bulk action page
                const ids = selectedPayments.join(',');
                window.location.href = `admin-bulk-approve.php?ids=${ids}`;
            }
        }
        
        function exportPayments() {
            const params = new URLSearchParams(window.location.search);
            window.location.href = 'admin-export-payments.php?' + params.toString();
        }
        
        function showPaymentSummary() {
            window.location.href = 'admin-payments-summary.php';
        }
        
        function refreshPage() {
            window.location.reload();
        }
        
        function checkDatabase() {
            if (confirm('Check database connection and table structure?')) {
                window.location.href = 'check-payments-table.php';
            }
        }
        
        function printTable() {
            const printContent = document.getElementById('paymentsTable').outerHTML;
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Payment Report - <?php echo date('Y-m-d'); ?></title>
                    <style>
                        body { font-family: Arial, sans-serif; }
                        table { width: 100%; border-collapse: collapse; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #f2f2f2; }
                        .print-header { text-align: center; margin-bottom: 20px; }
                        .print-footer { margin-top: 20px; text-align: center; font-size: 0.8em; color: #666; }
                    </style>
                </head>
                <body>
                    <div class="print-header">
                        <h2>Payment Report</h2>
                        <p>Generated on: <?php echo date('F j, Y, g:i a'); ?></p>
                        <p>Total Records: <?php echo $paymentCount; ?></p>
                    </div>
                    ${printContent}
                    <div class="print-footer">
                        <p>CONQUER Gym - Payment Management System</p>
                    </div>
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        }
        
        // Filter functions
        function clearFilters() {
            window.location.href = 'admin-payments.php';
        }
        
        function clearAllFilters() {
            window.location.href = 'admin-payments.php';
        }
        
        function removeFilter(filterName) {
            const url = new URL(window.location.href);
            url.searchParams.delete(filterName);
            window.location.href = url.toString();
        }
        
        function applyQuickFilter(filterName, filterValue) {
            const url = new URL(window.location.href);
            url.searchParams.set(filterName, filterValue);
            url.searchParams.set('page', '1'); // Reset to first page
            window.location.href = url.toString();
        }
        
        // Auto-submit search on enter
        document.getElementById('searchInput')?.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.form.submit();
            }
        });
        
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
        
        // Show confirmation before leaving page if there are pending changes
        window.addEventListener('beforeunload', function(e) {
            const selectedCount = document.querySelectorAll('.payment-checkbox:checked').length;
            if (selectedCount > 0) {
                e.preventDefault();
                e.returnValue = 'You have selected payments. Are you sure you want to leave?';
            }
        });
        
        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            // Add any initialization code here
            console.log('Payment management page loaded');
        });
    </script>
</body>
</html>