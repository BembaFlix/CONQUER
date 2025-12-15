<?php
session_start();
require_once 'config/database.php';

try {
    $pdo = Database::getInstance()->getConnection();
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

if(!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Get deleted members with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

$limit = 10;
$offset = ($page - 1) * $limit;

// Search and filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$membershipPlan = isset($_GET['plan']) ? $_GET['plan'] : '';

$whereClauses = ["u.user_type = 'member'", "u.deleted_at IS NOT NULL"];
$params = [];

if($search) {
    $whereClauses[] = "(u.full_name LIKE ? OR u.email LIKE ? OR gm.MembershipPlan LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if($membershipPlan) {
    $whereClauses[] = "gm.MembershipPlan = ?";
    $params[] = $membershipPlan;
}

$whereSQL = !empty($whereClauses) ? implode(' AND ', $whereClauses) : '1=1';

try {
    // Count total deleted members
    $countSQL = "SELECT COUNT(*) FROM users u LEFT JOIN gym_members gm ON u.email = gm.Email WHERE $whereSQL";
    $countStmt = $pdo->prepare($countSQL);
    $countStmt->execute($params);
    $totalMembers = $countStmt->fetchColumn();

    // Calculate total pages
    $totalPages = ceil($totalMembers / $limit);
    if ($page > $totalPages && $totalPages > 0) {
        $page = $totalPages;
        $offset = ($page - 1) * $limit;
    }

    // Get deleted members
    $membersSql = "
        SELECT 
            u.*, 
            gm.*,
            u.deleted_at as user_deleted_at,
            gm.deleted_at as member_deleted_at,
            (SELECT COUNT(*) FROM payments p WHERE p.user_id = u.id AND p.status = 'completed') as total_payments,
            (SELECT COALESCE(SUM(p.amount), 0) FROM payments p WHERE p.user_id = u.id AND p.status = 'completed') as total_spent
        FROM users u 
        LEFT JOIN gym_members gm ON u.email = gm.Email 
        WHERE $whereSQL 
        ORDER BY u.deleted_at DESC 
        LIMIT $limit OFFSET $offset";
    
    $stmt = $pdo->prepare($membersSql);
    $stmt->execute($params);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get unique membership plans
    $plans = $pdo->query("SELECT DISTINCT MembershipPlan FROM gym_members WHERE MembershipPlan IS NOT NULL AND MembershipPlan != ''")->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $members = [];
    $totalMembers = 0;
    $totalPages = 0;
    $plans = [];
    error_log("Deleted members query error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recently Deleted Members | Admin Dashboard</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&family=Montserrat:wght@900&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS -->
    <link rel="stylesheet" href="dashboard-style.css">
    <style>
        /* Additional styles for deleted members */
        .members-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .members-table th {
            background: #f8f9fa;
            font-weight: 600;
            padding: 1rem;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
            white-space: nowrap;
        }
        
        .members-table td {
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
            vertical-align: middle;
        }
        
        .members-table tr.deleted-row {
            background-color: #fff5f5;
            border-left: 3px solid #ff6b6b;
        }
        
        .members-table tr.deleted-row:hover {
            background-color: #ffeaea;
        }
        
        /* Deleted badge */
        .deleted-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
            background: rgba(255, 107, 107, 0.2);
            color: #ff6b6b;
        }
        
        .days-remaining-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
            background: rgba(255, 165, 2, 0.2);
            color: #ffa502;
        }
        
        .expired-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
            background: rgba(108, 117, 125, 0.2);
            color: #6c757d;
        }
        
        /* Member status badges */
        .member-status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .status-active {
            background: rgba(46, 213, 115, 0.2);
            color: #2ed573;
        }
        
        .status-inactive {
            background: rgba(255, 71, 87, 0.2);
            color: #ff4757;
        }
        
        /* Button styles */
        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            background: #e9ecef;
            color: #495057;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .btn-sm:hover {
            background: #dee2e6;
            text-decoration: none;
        }
        
        .btn-sm.btn-success {
            background: #2ed573;
            color: white;
        }
        
        .btn-sm.btn-success:hover {
            background: #1dd1a1;
        }
        
        .btn-sm.btn-danger {
            background: #ff4757;
            color: white;
        }
        
        .btn-sm.btn-danger:hover {
            background: #ff2e43;
        }
        
        /* Filters */
        .filters {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            display: flex;
            gap: 1rem;
            align-items: flex-end;
            flex-wrap: wrap;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            min-width: 200px;
        }
        
        .filter-group label {
            font-weight: 600;
            color: #2f3542;
            font-size: 0.9rem;
        }
        
        .filter-group input,
        .filter-group select {
            padding: 0.75rem;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.95rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            width: 100%;
        }
        
        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #ff6b6b;
            box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.1);
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }
        
        .page-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 40px;
            height: 40px;
            padding: 0.5rem;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            color: #495057;
            text-decoration: none;
            transition: all 0.3s;
            font-weight: 500;
            cursor: pointer;
        }
        
        .page-link:hover {
            background: #e9ecef;
            text-decoration: none;
        }
        
        .page-link.active {
            background: #ff6b6b;
            color: white;
            border-color: #ff6b6b;
        }
        
        .page-link.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }
        
        .page-info {
            margin: 0 1rem;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        /* Notification banners */
        .notification-banner {
            background: linear-gradient(135deg, #ffd166 0%, #ffc745 100%);
            color: #2f3542;
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 4px 12px rgba(255, 193, 69, 0.2);
        }
        
        .notification-banner.warning {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
            color: white;
        }
        
        .notification-banner.info {
            background: linear-gradient(135deg, #4ecdc4 0%, #44a08d 100%);
            color: white;
        }
        
        /* Responsive table */
        @media (max-width: 768px) {
            .members-table {
                min-width: 800px;
            }
            
            .table-container {
                overflow-x: auto;
            }
            
            .filters {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-group {
                min-width: auto;
            }
            
            .pagination {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .page-info {
                margin: 0.5rem 0;
            }
        }
    </style>
</head>
<body>
    <?php include 'admin-sidebar.php'; ?>

    <div class="main-content">
        <div class="top-bar">
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search deleted members..." value="<?php echo htmlspecialchars($search); ?>" id="searchInput">
            </div>
            <div class="top-bar-actions">
                <button class="btn-primary" onclick="window.location.href='admin-members.php'">
                    <i class="fas fa-arrow-left"></i> Back to Active Members
                </button>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="welcome-banner">
                <div class="welcome-content">
                    <h1>Recently Deleted Members</h1>
                    <p>Members deleted within the last 30 days - Can be restored</p>
                </div>
                <div class="welcome-stats">
                    <div class="stat">
                        <h3><?php echo $totalMembers; ?></h3>
                        <p>Deleted Members</p>
                    </div>
                    <div class="stat">
                        <h3>30</h3>
                        <p>Days to Restore</p>
                    </div>
                    <div class="stat">
                        <h3><?php echo $totalPages; ?></h3>
                        <p>Total Pages</p>
                    </div>
                </div>
            </div>

            <?php if(isset($_SESSION['success_message'])): ?>
                <div class="notification-banner info">
                    <i class="fas fa-check-circle"></i>
                    <div><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['error_message'])): ?>
                <div class="notification-banner warning">
                    <i class="fas fa-exclamation-circle"></i>
                    <div><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
                </div>
            <?php endif; ?>

            <?php if($totalMembers == 0): ?>
                <div class="notification-banner">
                    <i class="fas fa-info-circle"></i>
                    <div><strong>No recently deleted members found.</strong> Members are only kept in this list for 30 days after deletion.</div>
                </div>
            <?php endif; ?>

            <!-- Filters -->
            <form method="GET" class="filters">
                <input type="hidden" name="page" value="1">
                <div class="filter-group">
                    <label>Search</label>
                    <input type="text" name="search" placeholder="Name, email, or plan" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="filter-group">
                    <label>Membership Plan</label>
                    <select name="plan">
                        <option value="">All Plans</option>
                        <?php foreach($plans as $plan): ?>
                            <option value="<?php echo htmlspecialchars($plan['MembershipPlan']); ?>" <?php echo $membershipPlan == $plan['MembershipPlan'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($plan['MembershipPlan']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-primary">Apply Filters</button>
                <button type="button" class="btn-secondary" onclick="window.location.href='admin-recently-deleted-members.php'">Clear</button>
            </form>

            <!-- Members Table -->
            <div class="content-card">
                <div class="card-header">
                    <h3>Deleted Members (Last 30 Days)</h3>
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <span class="page-info">
                            Showing <?php echo ($totalMembers > 0) ? ($offset + 1) : 0; ?> - <?php echo min($offset + $limit, $totalMembers); ?> of <?php echo $totalMembers; ?> members
                        </span>
                        <span class="btn-secondary btn-sm" style="cursor: default;">
                            <i class="fas fa-clock"></i> Auto-deletes after 30 days
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table class="members-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Plan</th>
                                    <th>Deleted Date</th>
                                    <th>Days Left</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($members) > 0): ?>
                                    <?php foreach($members as $member): 
                                        $deletedAt = $member['user_deleted_at'] ?: $member['member_deleted_at'];
                                        $deletedDate = new DateTime($deletedAt);
                                        $now = new DateTime();
                                        $interval = $deletedDate->diff($now);
                                        $daysAgo = $interval->days;
                                        $daysRemaining = 30 - $daysAgo;
                                    ?>
                                        <tr class="deleted-row">
                                            <td>#<?php echo $member['id']; ?></td>
                                            <td>
                                                <div><?php echo htmlspecialchars($member['full_name']); ?></div>
                                                <small class="deleted-badge">DELETED</small>
                                            </td>
                                            <td><?php echo htmlspecialchars($member['email']); ?></td>
                                            <td><?php echo htmlspecialchars($member['MembershipPlan'] ?? 'N/A'); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($deletedAt)); ?></td>
                                            <td>
                                                <?php if($daysRemaining > 0): ?>
                                                    <span class="days-remaining-badge"><?php echo $daysRemaining; ?> days left</span>
                                                <?php else: ?>
                                                    <span class="expired-badge">EXPIRED</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="member-status status-inactive">
                                                    Deleted
                                                </span>
                                            </td>
                                            <td>
                                                <div style="display: flex; gap: 0.5rem;">
                                                    <?php if($daysRemaining > 0): ?>
                                                        <a href="admin-restore-member.php?id=<?php echo $member['id']; ?>" class="btn-sm btn-success" onclick="return confirmRestore('<?php echo htmlspecialchars($member['full_name']); ?>')">
                                                            <i class="fas fa-undo"></i> Restore
                                                        </a>
                                                    <?php else: ?>
                                                        <button class="btn-sm" disabled style="opacity: 0.5; cursor: not-allowed;">
                                                            <i class="fas fa-ban"></i> Expired
                                                        </button>
                                                    <?php endif; ?>
                                                    <button class="btn-sm btn-danger" onclick="confirmPermanentDelete(<?php echo $member['id']; ?>, '<?php echo htmlspecialchars($member['full_name']); ?>')">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" style="text-align: center; padding: 2rem;">
                                            <p style="color: #6c757d; font-style: italic;">No deleted members found</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if($totalPages > 1): ?>
                        <div class="pagination">
                            <?php
                            // Previous button
                            $prevClass = ($page <= 1) ? 'disabled' : '';
                            $prevPage = ($page > 1) ? $page - 1 : 1;
                            ?>
                            <a href="?page=<?php echo $prevPage; ?>&search=<?php echo urlencode($search); ?>&plan=<?php echo urlencode($membershipPlan); ?>" 
                               class="page-link <?php echo $prevClass; ?>">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                            
                            <?php 
                            // Show page numbers with ellipsis
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            // Always show first page
                            if($startPage > 1): ?>
                                <a href="?page=1&search=<?php echo urlencode($search); ?>&plan=<?php echo urlencode($membershipPlan); ?>" 
                                   class="page-link <?php echo (1 == $page) ? 'active' : ''; ?>">
                                    1
                                </a>
                                <?php if($startPage > 2): ?>
                                    <span class="page-link disabled">...</span>
                                <?php endif;
                            endif;
                            
                            // Show page range
                            for($i = $startPage; $i <= $endPage; $i++): ?>
                                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&plan=<?php echo urlencode($membershipPlan); ?>" 
                                   class="page-link <?php echo ($i == $page) ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor;
                            
                            // Always show last page
                            if($endPage < $totalPages): 
                                if($endPage < $totalPages - 1): ?>
                                    <span class="page-link disabled">...</span>
                                <?php endif; ?>
                                <a href="?page=<?php echo $totalPages; ?>&search=<?php echo urlencode($search); ?>&plan=<?php echo urlencode($membershipPlan); ?>" 
                                   class="page-link <?php echo ($totalPages == $page) ? 'active' : ''; ?>">
                                    <?php echo $totalPages; ?>
                                </a>
                            <?php endif; ?>
                            
                            <?php
                            // Next button
                            $nextClass = ($page >= $totalPages) ? 'disabled' : '';
                            $nextPage = ($page < $totalPages) ? $page + 1 : $totalPages;
                            ?>
                            <a href="?page=<?php echo $nextPage; ?>&search=<?php echo urlencode($search); ?>&plan=<?php echo urlencode($membershipPlan); ?>" 
                               class="page-link <?php echo $nextClass; ?>">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function confirmRestore(memberName) {
            return confirm(`Are you sure you want to restore member "${memberName}"? This will make them active again.`);
        }
        
        function confirmPermanentDelete(memberId, memberName) {
            if(confirm(`WARNING: This will permanently delete member "${memberName}" and cannot be undone. Are you sure?`)) {
                window.location.href = 'admin-permanent-delete-member.php?id=' + memberId;
            }
        }

        // Live search
        document.getElementById('searchInput').addEventListener('keyup', function(e) {
            if(e.key === 'Enter') {
                const search = this.value;
                window.location.href = 'admin-recently-deleted-members.php?page=1&search=' + encodeURIComponent(search);
            }
        });

        // Auto-submit filter form when select changes
        document.querySelector('select[name="plan"]').addEventListener('change', function() {
            this.form.submit();
        });
    </script>
</body>
</html>