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

// Initialize all variables with default values
$payments = [];
$totalRevenue = 0;
$monthlyRevenue = 0;
$pendingPayments = 0;
$completedPayments = 0;
$failedPayments = 0;
$pendingAmount = 0;
$avgPayment = 0;
$paymentMethods = [];
$hasError = false;
$errorMessage = '';
$tableExists = false;
$paymentCount = 0;
$stats = [];
$recentPayments = [];
$monthlyTrend = [];

// Get filter parameters with proper initialization
$status = isset($_GET['status']) ? $_GET['status'] : '';
$payment_method = isset($_GET['payment_method']) ? $_GET['payment_method'] : '';
$month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20; // Items per page
$offset = ($page - 1) * $limit;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'payment_date';
$order = isset($_GET['order']) && strtoupper($_GET['order']) === 'ASC' ? 'ASC' : 'DESC';

try {
    $pdo = Database::getInstance()->getConnection();
    
    // Check if payments table exists
    $tableExists = $pdo->query("SHOW TABLES LIKE 'payments'")->rowCount() > 0;
    
    if(!$tableExists) {
        $hasError = true;
        $errorMessage = "Payments table not found. Please run the setup script.";
    } else {
        // Check if users table exists
        $usersTableExists = $pdo->query("SHOW TABLES LIKE 'users'")->rowCount() > 0;
        
        if(!$usersTableExists) {
            $hasError = true;
            $errorMessage = "Users table not found. Please run the setup script.";
        } else {
            // Check if phone column exists in users table
            $columns = $pdo->query("SHOW COLUMNS FROM users LIKE 'phone'")->rowCount() > 0;
            $phoneColumnExists = $columns > 0;
            
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
                $whereClauses[] = "(p.transaction_id LIKE ? OR p.notes LIKE ? OR u.full_name LIKE ? OR u.email LIKE ? OR p.id = ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
                // Try to parse as ID if search is numeric
                if(is_numeric($search)) {
                    $params[] = $search;
                } else {
                    $params[] = 0; // Non-numeric search won't match ID
                }
            }

            $whereSQL = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';
            
            // Valid sort columns for security
            $validSortColumns = ['payment_date', 'amount', 'status', 'full_name', 'transaction_id'];
            $sortColumn = in_array($sort, $validSortColumns) ? $sort : 'payment_date';
            
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
            
            // Ensure page is within bounds
            if($page > $totalPages && $totalPages > 0) {
                $page = $totalPages;
                $offset = ($page - 1) * $limit;
            }
            
            // Get all payments with filters and pagination
            try {
                // Build SELECT query based on available columns
                $phoneColumn = $phoneColumnExists ? ', u.phone' : '';
                
                $sql = "SELECT p.*, u.full_name, u.email $phoneColumn 
                        FROM payments p 
                        LEFT JOIN users u ON p.user_id = u.id 
                        $whereSQL 
                        ORDER BY p.$sortColumn $order 
                        LIMIT ? OFFSET ?";
                
                // Add limit and offset to params
                $queryParams = $params;
                $queryParams[] = $limit;
                $queryParams[] = $offset;
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($queryParams);
                $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Get recent payments for quick overview (last 24 hours)
                $recentStmt = $pdo->prepare("
                    SELECT p.*, u.full_name 
                    FROM payments p 
                    LEFT JOIN users u ON p.user_id = u.id 
                    WHERE p.payment_date >= DATE_SUB(NOW(), INTERVAL 1 DAY)
                    ORDER BY p.payment_date DESC 
                    LIMIT 5
                ");
                $recentStmt->execute();
                $recentPayments = $recentStmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Calculate comprehensive stats
                $statsSql = "SELECT 
                            COUNT(*) as total_payments,
                            COALESCE(SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END), 0) as total_revenue,
                            COALESCE(SUM(CASE WHEN status = 'completed' AND DATE_FORMAT(payment_date, '%Y-%m') = ? THEN amount ELSE 0 END), 0) as monthly_revenue,
                            COALESCE(SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END), 0) as pending_amount,
                            COALESCE(SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END), 0) as completed_amount,
                            COALESCE(SUM(CASE WHEN status = 'failed' THEN amount ELSE 0 END), 0) as failed_amount,
                            COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) as pending_count,
                            COALESCE(SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END), 0) as completed_count,
                            COALESCE(SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END), 0) as failed_count,
                            COALESCE(AVG(CASE WHEN status = 'completed' THEN amount ELSE NULL END), 0) as avg_payment
                            FROM payments";
                
                $currentMonth = date('Y-m');
                $statsStmt = $pdo->prepare($statsSql);
                $statsStmt->execute([$currentMonth]);
                $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
                
                // Assign stats with null checks
                $totalRevenue = (float)($stats['total_revenue'] ?? 0);
                $monthlyRevenue = (float)($stats['monthly_revenue'] ?? 0);
                $pendingPayments = (int)($stats['pending_count'] ?? 0);
                $completedPayments = (int)($stats['completed_count'] ?? 0);
                $failedPayments = (int)($stats['failed_count'] ?? 0);
                $pendingAmount = (float)($stats['pending_amount'] ?? 0);
                $avgPayment = (float)($stats['avg_payment'] ?? 0);
                
                // Get payment methods statistics with percentages
                $paymentMethodsData = $pdo->query("
                    SELECT payment_method, 
                           COUNT(*) as count,
                           COALESCE(SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END), 0) as total_amount
                    FROM payments 
                    GROUP BY payment_method
                    ORDER BY count DESC
                ")->fetchAll(PDO::FETCH_ASSOC);
                $paymentMethods = $paymentMethodsData ?: [];
                
                // Get monthly revenue trend (handle potential errors)
                try {
                    $monthlyTrend = $pdo->query("
                        SELECT 
                            DATE_FORMAT(payment_date, '%Y-%m') as month,
                            COALESCE(SUM(amount), 0) as revenue,
                            COUNT(*) as payments
                        FROM payments 
                        WHERE status = 'completed'
                        GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
                        ORDER BY month DESC
                        LIMIT 6
                    ")->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e) {
                    $monthlyTrend = [];
                    error_log("Monthly trend query error: " . $e->getMessage());
                }
                
            } catch (Exception $e) {
                $hasError = true;
                $errorMessage = "Error reading payments: " . $e->getMessage();
                error_log("Payment query error: " . $e->getMessage());
            }
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

// Generate pagination URLs
function getPaginationURL($page) {
    $params = $_GET;
    $params['page'] = $page;
    return '?' . http_build_query($params);
}

// Generate sort URL
function getSortURL($column) {
    $params = $_GET;
    if(isset($params['sort']) && $params['sort'] == $column) {
        $params['order'] = ($params['order'] ?? 'DESC') == 'DESC' ? 'ASC' : 'DESC';
    } else {
        $params['sort'] = $column;
        $params['order'] = 'DESC';
    }
    return '?' . http_build_query($params);
}

// Get sort indicator
function getSortIndicator($column) {
    global $sort, $order;
    if($sort == $column) {
        return $order == 'ASC' ? '↑' : '↓';
    }
    return '';
}
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
        :root {
            --primary-color: #667eea;
            --success-color: #2ed573;
            --warning-color: #ffa502;
            --danger-color: #ff4757;
            --info-color: #3498db;
            --secondary-color: #6c757d;
            --light-bg: #f8f9fa;
            --border-color: #e9ecef;
            --card-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        /* Main Layout Improvements */
        .dashboard-content {
            padding: 1.5rem;
            max-width: 100%;
            overflow-x: hidden;
        }
        
        /* Enhanced Stats Grid */
        .stats-grid-enhanced {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin: 1.5rem 0;
        }
        
        .stat-card-enhanced {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card-enhanced::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
        }
        
        .stat-card-enhanced.total-revenue::before { background: linear-gradient(to bottom, #667eea, #764ba2); }
        .stat-card-enhanced.monthly-revenue::before { background: linear-gradient(to bottom, #2ed573, #1dd1a1); }
        .stat-card-enhanced.pending::before { background: linear-gradient(to bottom, #ffa502, #ff7f00); }
        .stat-card-enhanced.completed::before { background: linear-gradient(to bottom, #3498db, #2980b9); }
        .stat-card-enhanced.average::before { background: linear-gradient(to bottom, #9b59b6, #8e44ad); }
        .stat-card-enhanced.failed::before { background: linear-gradient(to bottom, #ff4757, #ff3742); }
        
        .stat-card-enhanced:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .stat-card-enhanced h3 {
            margin: 0;
            color: #2f3542;
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            line-height: 1;
        }
        
        .stat-card-enhanced p {
            margin: 0;
            color: var(--secondary-color);
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .stat-card-enhanced .stat-trend {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            background: rgba(46, 213, 115, 0.1);
            color: var(--success-color);
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .stat-card-enhanced .stat-trend.negative {
            background: rgba(255, 71, 87, 0.1);
            color: var(--danger-color);
        }
        
        /* Filters Card */
        .filters-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            box-shadow: var(--card-shadow);
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .filter-group label {
            font-weight: 600;
            color: #2f3542;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .filter-group select,
        .filter-group input {
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.95rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            width: 100%;
        }
        
        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .filter-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
        }
        
        /* Enhanced Table */
        .table-responsive {
            overflow-x: auto;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            background: white;
        }
        
        .payments-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1200px;
        }
        
        .payments-table th {
            background: var(--light-bg);
            font-weight: 600;
            padding: 1rem;
            text-align: left;
            color: #495057;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--border-color);
            cursor: pointer;
            user-select: none;
            white-space: nowrap;
        }
        
        .payments-table th:hover {
            background: #e9ecef;
        }
        
        .payments-table th.sortable {
            position: relative;
            padding-right: 1.5rem;
        }
        
        .payments-table th.sortable::after {
            content: '↕';
            position: absolute;
            right: 0.5rem;
            opacity: 0.3;
        }
        
        .payments-table th.sortable.active::after {
            content: attr(data-order);
            opacity: 1;
        }
        
        .payments-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
            color: #495057;
        }
        
        .payments-table tr {
            transition: all 0.3s;
        }
        
        .payments-table tr:hover {
            background: rgba(102, 126, 234, 0.05);
        }
        
        /* Payment Status Badges - Enhanced */
        .payment-status {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 0.75rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            min-width: 100px;
            justify-content: center;
        }
        
        .status-completed {
            background: linear-gradient(135deg, rgba(46, 213, 115, 0.15), rgba(46, 213, 115, 0.05));
            color: var(--success-color);
            border: 1px solid rgba(46, 213, 115, 0.2);
        }
        
        .status-pending {
            background: linear-gradient(135deg, rgba(255, 165, 2, 0.15), rgba(255, 165, 2, 0.05));
            color: var(--warning-color);
            border: 1px solid rgba(255, 165, 2, 0.2);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.8; }
            100% { opacity: 1; }
        }
        
        .status-failed {
            background: linear-gradient(135deg, rgba(255, 71, 87, 0.15), rgba(255, 71, 87, 0.05));
            color: var(--danger-color);
            border: 1px solid rgba(255, 71, 87, 0.2);
        }
        
        .status-refunded {
            background: linear-gradient(135deg, rgba(108, 92, 231, 0.15), rgba(108, 92, 231, 0.05));
            color: #6c5ce7;
            border: 1px solid rgba(108, 92, 231, 0.2);
        }
        
        /* Payment Method Display */
        .payment-method-display {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .payment-method-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.9rem;
            flex-shrink: 0;
        }
        
        .method-credit_card { background: linear-gradient(135deg, #667eea, #764ba2); }
        .method-gcash { background: linear-gradient(135deg, #0066a1, #00a3e0); }
        .method-paymaya { background: linear-gradient(135deg, #00a859, #00c853); }
        .method-cash { background: linear-gradient(135deg, #2ed573, #1dd1a1); }
        .method-bank_transfer { background: linear-gradient(135deg, #ff6b6b, #ee5a52); }
        .method-paypal { background: linear-gradient(135deg, #003087, #009cde); }
        .method-other { background: linear-gradient(135deg, #95a5a6, #7f8c8d); }
        
        /* User Avatar */
        .user-avatar-small {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), #764ba2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.9rem;
        }
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 0.25rem;
            flex-wrap: wrap;
        }
        
        .btn-action {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            border: none;
            background: var(--light-bg);
            color: var(--secondary-color);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .btn-action.view { background: rgba(52, 152, 219, 0.1); color: var(--info-color); }
        .btn-action.approve { background: rgba(46, 213, 115, 0.1); color: var(--success-color); }
        .btn-action.reject { background: rgba(255, 71, 87, 0.1); color: var(--danger-color); }
        .btn-action.receipt { background: rgba(155, 89, 182, 0.1); color: #9b59b6; }
        .btn-action.edit { background: rgba(255, 165, 2, 0.1); color: var(--warning-color); }
        
        /* Pagination Enhanced */
        .pagination-enhanced {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            background: white;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            margin-top: 1.5rem;
        }
        
        .pagination-info {
            color: var(--secondary-color);
            font-size: 0.9rem;
        }
        
        .pagination-controls {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        
        .pagination-btn {
            padding: 0.5rem 1rem;
            border: 1px solid var(--border-color);
            background: white;
            color: var(--secondary-color);
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .pagination-btn:hover:not(:disabled) {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .pagination-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .page-numbers {
            display: flex;
            gap: 0.25rem;
        }
        
        .page-number {
            min-width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .page-number:hover:not(.active) {
            background: var(--light-bg);
        }
        
        .page-number.active {
            background: var(--primary-color);
            color: white;
        }
        
        /* Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 3px solid var(--light-bg);
            border-top: 3px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Toast Notifications */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9998;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .toast {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            color: white;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideInRight 0.3s ease-out;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .toast.success { background: linear-gradient(135deg, var(--success-color), #25c464); }
        .toast.error { background: linear-gradient(135deg, var(--danger-color), #ff3742); }
        .toast.warning { background: linear-gradient(135deg, var(--warning-color), #ff7f00); }
        .toast.info { background: linear-gradient(135deg, var(--info-color), #2980b9); }
        
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .dashboard-content {
                padding: 1rem;
            }
            
            .stats-grid-enhanced {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
            
            .filters-grid {
                grid-template-columns: 1fr;
            }
            
            .pagination-enhanced {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .table-responsive {
                border-radius: 8px;
            }
        }
        
        @media (max-width: 480px) {
            .stats-grid-enhanced {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
                width: 100%;
            }
            
            .btn-action {
                width: 100%;
            }
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: var(--light-bg);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
        
        /* Error and Warning Styling */
        .database-error {
            background: #fff5f5;
            border: 1px solid #fed7d7;
            color: #e53e3e;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }
        
        .database-error i {
            color: #e53e3e;
            font-size: 1.25rem;
            margin-top: 0.25rem;
        }
        
        .database-error .error-content {
            flex: 1;
        }
        
        .database-error .error-content h4 {
            margin: 0 0 0.5rem 0;
            color: #e53e3e;
        }
        
        .database-error .error-content p {
            margin: 0 0 0.5rem 0;
            font-size: 0.9rem;
        }
        
        .database-error .error-content code {
            background: #fed7d7;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.85rem;
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
                    <input type="text" name="search" placeholder="Search payments by ID, name, email, or notes..." 
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
                        style="background: #ffa502; color: white; position: relative;">
                    <i class="fas fa-clock"></i> Pending 
                    <span class="badge" style="position: absolute; top: -8px; right: -8px; background: #ff4757; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem;">
                        <?php echo $pendingCount; ?>
                    </span>
                </button>
                <?php endif; ?>
                <button class="btn-secondary" onclick="exportToExcel()">
                    <i class="fas fa-file-export"></i> Export
                </button>
            </div>
        </div>

        <div class="dashboard-content">
            <!-- Welcome Banner -->
            <div class="welcome-banner">
                <div class="welcome-content">
                    <h1>Payment Management</h1>
                    <p>Monitor and manage all payment transactions</p>
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
            <div class="database-error">
                <i class="fas fa-exclamation-triangle"></i>
                <div class="error-content">
                    <h4>Database Error</h4>
                    <p><?php echo htmlspecialchars($errorMessage); ?></p>
                    <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                        <button class="btn-primary" onclick="window.location.href='admin-setup.php'">
                            <i class="fas fa-database"></i> Run Setup
                        </button>
                        <button class="btn-secondary" onclick="window.location.href='admin-payments.php'">
                            <i class="fas fa-redo"></i> Refresh
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Database Schema Info -->
            <?php if(!$hasError && (!$tableExists || !$usersTableExists)): ?>
            <div class="database-error">
                <i class="fas fa-database"></i>
                <div class="error-content">
                    <h4>Database Setup Required</h4>
                    <p>Some database tables are missing. Please run the setup script.</p>
                    <div style="margin-top: 0.5rem;">
                        <code>Tables found: <?php 
                            $tables = [];
                            if($tableExists) $tables[] = 'payments';
                            if($usersTableExists) $tables[] = 'users';
                            echo count($tables) > 0 ? implode(', ', $tables) : 'None';
                        ?></code>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Enhanced Stats Grid -->
            <?php if($tableExists && $usersTableExists): ?>
            <div class="stats-grid-enhanced">
                <div class="stat-card-enhanced total-revenue">
                    <h3>$<?php echo number_format($totalRevenue, 2); ?></h3>
                    <p>
                        <i class="fas fa-chart-line"></i>
                        Total Revenue
                    </p>
                </div>
                
                <div class="stat-card-enhanced monthly-revenue">
                    <h3>$<?php echo number_format($monthlyRevenue, 2); ?></h3>
                    <p>
                        <i class="fas fa-calendar-alt"></i>
                        This Month
                    </p>
                </div>
                
                <div class="stat-card-enhanced pending">
                    <h3><?php echo $pendingPayments; ?></h3>
                    <p>
                        <i class="fas fa-clock"></i>
                        Pending Payments
                        <span class="stat-trend">$<?php echo number_format($pendingAmount, 2); ?></span>
                    </p>
                </div>
                
                <div class="stat-card-enhanced completed">
                    <h3><?php echo $completedPayments; ?></h3>
                    <p>
                        <i class="fas fa-check-circle"></i>
                        Completed
                    </p>
                </div>
                
                <div class="stat-card-enhanced average">
                    <h3>$<?php echo number_format($avgPayment, 2); ?></h3>
                    <p>
                        <i class="fas fa-dollar-sign"></i>
                        Average Payment
                    </p>
                </div>
                
                <div class="stat-card-enhanced failed">
                    <h3><?php echo $failedPayments; ?></h3>
                    <p>
                        <i class="fas fa-times-circle"></i>
                        Failed Payments
                    </p>
                </div>
            </div>

            <!-- Filters Card -->
            <div class="filters-card">
                <h3 style="margin: 0 0 1rem 0; color: #2f3542; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-filter"></i> Filters
                </h3>
                
                <form method="GET" id="filterForm">
                    <div class="filters-grid">
                        <div class="filter-group">
                            <label><i class="fas fa-tag"></i> Status</label>
                            <select name="status" id="statusFilter">
                                <option value="">All Status</option>
                                <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="failed" <?php echo $status === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                <option value="refunded" <?php echo $status === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label><i class="fas fa-credit-card"></i> Payment Method</label>
                            <select name="payment_method" id="paymentMethodFilter">
                                <option value="">All Methods</option>
                                <option value="credit_card" <?php echo $payment_method === 'credit_card' ? 'selected' : ''; ?>>Credit Card</option>
                                <option value="gcash" <?php echo $payment_method === 'gcash' ? 'selected' : ''; ?>>GCash</option>
                                <option value="paymaya" <?php echo $payment_method === 'paymaya' ? 'selected' : ''; ?>>PayMaya</option>
                                <option value="bank_transfer" <?php echo $payment_method === 'bank_transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                                <option value="cash" <?php echo $payment_method === 'cash' ? 'selected' : ''; ?>>Cash</option>
                                <option value="paypal" <?php echo $payment_method === 'paypal' ? 'selected' : ''; ?>>PayPal</option>
                                <option value="other" <?php echo $payment_method === 'other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label><i class="fas fa-calendar"></i> Month</label>
                            <input type="month" name="month" value="<?php echo htmlspecialchars($month); ?>" id="monthFilter">
                        </div>
                        
                        <div class="filter-group">
                            <label><i class="fas fa-search"></i> Search</label>
                            <input type="text" name="search" placeholder="ID, name, email, notes..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    
                    <div class="filter-actions">
                        <button type="submit" class="btn-primary" id="applyFiltersBtn">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                        <button type="button" class="btn-secondary" onclick="clearFilters()">
                            <i class="fas fa-times"></i> Clear Filters
                        </button>
                        <?php if($status || $payment_method || $month != date('Y-m') || $search): ?>
                        <div style="margin-left: auto; display: flex; gap: 0.5rem; align-items: center;">
                            <span style="font-size: 0.85rem; color: var(--secondary-color);">
                                Filtered results: <?php echo $paymentCount; ?> payments
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Payments Table -->
            <div class="table-responsive">
                <div style="padding: 1.5rem 1.5rem 0 1.5rem; display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="margin: 0; color: #2f3542;">
                        Payment Records
                        <small style="font-size: 0.9rem; color: var(--secondary-color); font-weight: normal;">
                            (<?php echo $paymentCount; ?> total)
                        </small>
                    </h3>
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <button class="btn-sm" onclick="selectAllPayments()" id="selectAllBtn">
                            <i class="fas fa-check-square"></i> Select All
                        </button>
                        <button class="btn-sm btn-success" onclick="approveSelected()" id="approveSelectedBtn" disabled>
                            <i class="fas fa-check-double"></i> Approve Selected
                        </button>
                        <button class="btn-sm" onclick="printTable()">
                            <i class="fas fa-print"></i> Print
                        </button>
                    </div>
                </div>
                
                <?php if(is_array($payments) && count($payments) > 0): ?>
                <table class="payments-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">
                                <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this)">
                            </th>
                            <th class="sortable <?php echo $sort == 'transaction_id' ? 'active' : ''; ?>" 
                                onclick="sortTable('transaction_id')"
                                data-order="<?php echo getSortIndicator('transaction_id'); ?>">
                                Transaction ID
                            </th>
                            <th class="sortable <?php echo $sort == 'full_name' ? 'active' : ''; ?>" 
                                onclick="sortTable('full_name')"
                                data-order="<?php echo getSortIndicator('full_name'); ?>">
                                Customer
                            </th>
                            <th class="sortable <?php echo $sort == 'amount' ? 'active' : ''; ?>" 
                                onclick="sortTable('amount')"
                                data-order="<?php echo getSortIndicator('amount'); ?>">
                                Amount
                            </th>
                            <th class="sortable <?php echo $sort == 'payment_date' ? 'active' : ''; ?>" 
                                onclick="sortTable('payment_date')"
                                data-order="<?php echo getSortIndicator('payment_date'); ?>">
                                Date & Time
                            </th>
                            <th>Method</th>
                            <th class="sortable <?php echo $sort == 'status' ? 'active' : ''; ?>" 
                                onclick="sortTable('status')"
                                data-order="<?php echo getSortIndicator('status'); ?>">
                                Status
                            </th>
                            <th style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($payments as $payment): 
                            $paymentId = isset($payment['id']) ? (int)$payment['id'] : 0;
                            $transactionId = isset($payment['transaction_id']) ? htmlspecialchars($payment['transaction_id']) : 'N/A';
                            $amount = isset($payment['amount']) ? (float)$payment['amount'] : 0.00;
                            $paymentDate = isset($payment['payment_date']) ? $payment['payment_date'] : date('Y-m-d H:i:s');
                            $paymentMethod = isset($payment['payment_method']) ? $payment['payment_method'] : 'other';
                            $status = isset($payment['status']) ? $payment['status'] : 'pending';
                            $receiptImage = isset($payment['receipt_image']) ? $payment['receipt_image'] : null;
                            $notes = isset($payment['notes']) ? htmlspecialchars($payment['notes']) : '';
                            $fullName = isset($payment['full_name']) ? htmlspecialchars($payment['full_name']) : 'N/A';
                            $email = isset($payment['email']) ? htmlspecialchars($payment['email']) : '';
                            $phone = isset($payment['phone']) ? htmlspecialchars($payment['phone']) : '';
                            
                            // Get user initials
                            $initials = '?';
                            if($fullName !== 'N/A') {
                                $nameParts = explode(' ', $fullName);
                                $initials = '';
                                foreach($nameParts as $part) {
                                    if(!empty($part)) {
                                        $initials .= strtoupper($part[0]);
                                    }
                                    if(strlen($initials) >= 2) break;
                                }
                            }
                            
                            // Format dates
                            $displayDate = date('M j, Y', strtotime($paymentDate));
                            $displayTime = date('g:i A', strtotime($paymentDate));
                        ?>
                        <tr id="payment-row-<?php echo $paymentId; ?>">
                            <td>
                                <input type="checkbox" class="payment-checkbox" value="<?php echo $paymentId; ?>"
                                       onchange="updateSelection()">
                            </td>
                            <td>
                                <div style="font-weight: 600; color: var(--primary-color);">
                                    <?php echo $transactionId; ?>
                                </div>
                                <?php if(!empty($payment['subscription_id'])): ?>
                                <small style="color: var(--secondary-color); font-size: 0.8rem;">
                                    Sub ID: <?php echo $payment['subscription_id']; ?>
                                </small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div class="user-avatar-small">
                                        <?php echo $initials; ?>
                                    </div>
                                    <div>
                                        <div style="font-weight: 600;"><?php echo $fullName; ?></div>
                                        <?php if($email): ?>
                                        <small style="color: var(--secondary-color); font-size: 0.8rem; display: block;">
                                            <?php echo $email; ?>
                                        </small>
                                        <?php endif; ?>
                                        <?php if($phone && $phoneColumnExists): ?>
                                        <small style="color: var(--secondary-color); font-size: 0.8rem; display: block;">
                                            <?php echo $phone; ?>
                                        </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 700; font-size: 1.1rem; color: #2f3542;">
                                    $<?php echo number_format($amount, 2); ?>
                                </div>
                                <?php if(isset($payment['subscription_period'])): ?>
                                <small style="color: var(--secondary-color); font-size: 0.8rem;">
                                    <?php echo htmlspecialchars($payment['subscription_period']); ?>
                                </small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-weight: 600;"><?php echo $displayDate; ?></div>
                                <div style="color: var(--secondary-color); font-size: 0.85rem;">
                                    <?php echo $displayTime; ?>
                                </div>
                            </td>
                            <td>
                                <div class="payment-method-display">
                                    <div class="payment-method-icon method-<?php echo $paymentMethod; ?>">
                                        <i class="fas fa-<?php 
                                            echo $paymentMethod == 'credit_card' ? 'credit-card' : 
                                                   ($paymentMethod == 'gcash' ? 'mobile-alt' : 
                                                   ($paymentMethod == 'paymaya' ? 'mobile-alt' : 
                                                   ($paymentMethod == 'bank_transfer' ? 'university' : 
                                                   ($paymentMethod == 'cash' ? 'money-bill' : 
                                                   ($paymentMethod == 'paypal' ? 'paypal' : 'credit-card'))))); ?>"></i>
                                    </div>
                                    <span style="text-transform: capitalize;">
                                        <?php echo str_replace('_', ' ', $paymentMethod); ?>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <span class="payment-status status-<?php echo $status; ?>">
                                    <i class="fas fa-<?php 
                                        echo $status == 'completed' ? 'check' : 
                                               ($status == 'pending' ? 'clock' : 
                                               ($status == 'failed' ? 'times' : 'undo')); ?>"></i>
                                    <?php echo ucfirst($status); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-action view" onclick="viewPayment(<?php echo $paymentId; ?>)" 
                                            title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    
                                    <?php if($status === 'pending'): ?>
                                    <button class="btn-action approve" onclick="approvePayment(<?php echo $paymentId; ?>)" 
                                            title="Approve Payment">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button class="btn-action reject" onclick="rejectPayment(<?php echo $paymentId; ?>)" 
                                            title="Reject Payment">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <?php if(!empty($receiptImage)): ?>
                                    <button class="btn-action receipt" onclick="viewReceipt('<?php echo htmlspecialchars($receiptImage); ?>')" 
                                            title="View Receipt">
                                        <i class="fas fa-receipt"></i>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <button class="btn-action edit" onclick="editPayment(<?php echo $paymentId; ?>)" 
                                            title="Edit Payment">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div style="padding: 3rem; text-align: center;">
                    <i class="fas fa-credit-card" style="font-size: 3rem; color: #e9ecef; margin-bottom: 1rem;"></i>
                    <h3 style="color: #495057; margin-bottom: 0.5rem;">No Payments Found</h3>
                    <p style="color: var(--secondary-color); margin-bottom: 2rem;">
                        <?php if($status || $payment_method || $month != date('Y-m') || $search): ?>
                            No payments match your filter criteria.
                        <?php else: ?>
                            No payments have been recorded yet.
                        <?php endif; ?>
                    </p>
                    <button class="btn-primary" onclick="window.location.href='admin-add-payment.php'">
                        <i class="fas fa-plus"></i> Record First Payment
                    </button>
                </div>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if($paymentCount > 0): ?>
            <div class="pagination-enhanced">
                <div class="pagination-info">
                    Showing <?php echo (($page - 1) * $limit) + 1; ?> to 
                    <?php echo min($page * $limit, $paymentCount); ?> of 
                    <?php echo $paymentCount; ?> entries
                </div>
                
                <div class="pagination-controls">
                    <button class="pagination-btn" onclick="changePage(1)" <?php echo $page == 1 ? 'disabled' : ''; ?>>
                        <i class="fas fa-angle-double-left"></i> First
                    </button>
                    
                    <button class="pagination-btn" onclick="changePage(<?php echo $page - 1; ?>)" <?php echo $page == 1 ? 'disabled' : ''; ?>>
                        <i class="fas fa-angle-left"></i> Previous
                    </button>
                    
                    <div class="page-numbers">
                        <?php 
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        
                        for($i = $startPage; $i <= $endPage; $i++): 
                        ?>
                        <div class="page-number <?php echo $i == $page ? 'active' : ''; ?>" 
                             onclick="changePage(<?php echo $i; ?>)">
                            <?php echo $i; ?>
                        </div>
                        <?php endfor; ?>
                    </div>
                    
                    <button class="pagination-btn" onclick="changePage(<?php echo $page + 1; ?>)" <?php echo $page >= $totalPages ? 'disabled' : ''; ?>>
                        Next <i class="fas fa-angle-right"></i>
                    </button>
                    
                    <button class="pagination-btn" onclick="changePage(<?php echo $totalPages; ?>)" <?php echo $page == $totalPages ? 'disabled' : ''; ?>>
                        Last <i class="fas fa-angle-double-right"></i>
                    </button>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <script>
        // Toast Notification System
        function showToast(message, type = 'info') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'}"></i>
                <span>${message}</span>
            `;
            
            container.appendChild(toast);
            
            setTimeout(() => {
                toast.style.animation = 'slideOutRight 0.3s ease-out';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // Loading Overlay
        function showLoading() {
            document.getElementById('loadingOverlay').style.display = 'flex';
        }

        function hideLoading() {
            document.getElementById('loadingOverlay').style.display = 'none';
        }

        // Payment Actions
        async function approvePayment(paymentId) {
            if (!confirm(`Approve payment #${paymentId}?`)) return;
            
            try {
                showLoading();
                const response = await fetch(`admin-process-payment.php?action=approve&id=${paymentId}`);
                const data = await response.json();
                
                if (data.success) {
                    showToast('Payment approved successfully!', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.message || 'Error approving payment', 'error');
                }
            } catch (error) {
                showToast('Network error. Please try again.', 'error');
            } finally {
                hideLoading();
            }
        }

        async function rejectPayment(paymentId) {
            const reason = prompt('Reason for rejection (optional):');
            if (reason === null) return;
            
            try {
                showLoading();
                const response = await fetch(`admin-process-payment.php?action=reject&id=${paymentId}&reason=${encodeURIComponent(reason)}`);
                const data = await response.json();
                
                if (data.success) {
                    showToast('Payment rejected successfully!', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.message || 'Error rejecting payment', 'error');
                }
            } catch (error) {
                showToast('Network error. Please try again.', 'error');
            } finally {
                hideLoading();
            }
        }

        function viewPayment(paymentId) {
            window.open(`admin-payment-view.php?id=${paymentId}`, '_blank');
        }

        function editPayment(paymentId) {
            window.location.href = `admin-edit-payment.php?id=${paymentId}`;
        }

        // View Receipt
        function viewReceipt(imagePath) {
            window.open(imagePath, '_blank');
        }

        // Selection Management
        let allSelected = false;
        
        function toggleSelectAll(checkbox) {
            const checkboxes = document.querySelectorAll('.payment-checkbox');
            checkboxes.forEach(cb => {
                cb.checked = checkbox.checked;
            });
            allSelected = checkbox.checked;
            updateSelection();
        }

        function selectAllPayments() {
            const checkbox = document.getElementById('selectAllCheckbox');
            checkbox.checked = !allSelected;
            toggleSelectAll(checkbox);
        }

        function updateSelection() {
            const selected = document.querySelectorAll('.payment-checkbox:checked');
            const approveBtn = document.getElementById('approveSelectedBtn');
            if (approveBtn) {
                approveBtn.disabled = selected.length === 0;
                approveBtn.innerHTML = `<i class="fas fa-check-double"></i> Approve (${selected.length})`;
            }
        }

        async function approveSelected() {
            const selected = Array.from(document.querySelectorAll('.payment-checkbox:checked'))
                .map(cb => parseInt(cb.value))
                .filter(id => id > 0);
            
            if (selected.length === 0) return;
            
            if (!confirm(`Approve ${selected.length} selected payment(s)?`)) return;
            
            try {
                showLoading();
                const response = await fetch('admin-bulk-approve.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ ids: selected })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showToast(`${data.approved} payment(s) approved successfully!`, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast(data.message || 'Error approving payments', 'error');
                }
            } catch (error) {
                showToast('Network error. Please try again.', 'error');
            } finally {
                hideLoading();
            }
        }

        // Sorting
        function sortTable(column) {
            const url = new URL(window.location.href);
            const currentSort = url.searchParams.get('sort');
            const currentOrder = url.searchParams.get('order');
            
            if (currentSort === column) {
                url.searchParams.set('order', currentOrder === 'ASC' ? 'DESC' : 'ASC');
            } else {
                url.searchParams.set('sort', column);
                url.searchParams.set('order', 'DESC');
            }
            
            url.searchParams.set('page', '1');
            window.location.href = url.toString();
        }

        // Pagination
        function changePage(page) {
            const url = new URL(window.location.href);
            url.searchParams.set('page', page);
            window.location.href = url.toString();
        }

        // Filters
        function clearFilters() {
            window.location.href = 'admin-payments.php';
        }

        // Export
        function exportToExcel() {
            const params = new URLSearchParams(window.location.search);
            window.open('admin-export-payments.php?' + params.toString(), '_blank');
        }

        // Print
        function printTable() {
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Payment Report - ${new Date().toLocaleDateString()}</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        h1 { color: #333; }
                        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #f5f5f5; }
                        .status-completed { color: green; }
                        .status-pending { color: orange; }
                        .status-failed { color: red; }
                    </style>
                </head>
                <body>
                    <h1>Payment Report</h1>
                    <p>Generated: ${new Date().toLocaleString()}</p>
                    ${document.querySelector('.payments-table')?.outerHTML || '<p>No data to print</p>'}
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        }

        // Search with debounce
        let searchTimeout;
        document.getElementById('searchInput')?.addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                if (this.value.length >= 2 || this.value.length === 0) {
                    this.form.submit();
                }
            }, 500);
        });

        // Auto-refresh for pending payments
        <?php if($status === 'pending' || $pendingCount > 0): ?>
        setTimeout(() => {
            location.reload();
        }, 30000); // Refresh every 30 seconds if there are pending payments
        <?php endif; ?>

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl + F to focus search
            if (e.ctrlKey && e.key === 'f') {
                e.preventDefault();
                document.getElementById('searchInput')?.focus();
            }
            // Ctrl + N for new payment
            if (e.ctrlKey && e.key === 'n') {
                e.preventDefault();
                window.location.href = 'admin-add-payment.php';
            }
        });

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            updateSelection();
        });
    </script>
</body>
</html>