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

// Initialize variables
$stories = [];
$pendingCount = 0;
$approvedCount = 0;
$totalStories = 0;
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 12; // Stories per page
$offset = ($page - 1) * $limit;

try {
    // Build query with filters
    $whereClauses = [];
    $params = [];
    
    if($status === 'pending') {
        $whereClauses[] = "COALESCE(ss.approved, 0) = 0";
    } elseif($status === 'approved') {
        $whereClauses[] = "COALESCE(ss.approved, 0) = 1";
    }
    
    if(!empty($searchTerm)) {
        $whereClauses[] = "(ss.title LIKE :search OR ss.story_text LIKE :search OR u.full_name LIKE :search OR u.email LIKE :search)";
        $params[':search'] = "%$searchTerm%";
    }
    
    $whereClause = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';
    
    // Get stories with pagination
    $sql = "
        SELECT 
            ss.id,
            ss.title,
            ss.story_text,
            COALESCE(ss.weight_loss, 0) as weight_loss,
            COALESCE(ss.duration_months, 6) as duration_months,
            COALESCE(ss.approved, 1) as approved,
            ss.before_image,
            ss.after_image,
            ss.created_at,
            u.full_name,
            u.email,
            u.profile_image
        FROM success_stories ss
        JOIN users u ON ss.user_id = u.id
        $whereClause
        ORDER BY 
            CASE WHEN ss.approved = 0 THEN 0 ELSE 1 END,
            ss.created_at DESC
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $pdo->prepare($sql);
    
    foreach($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $stories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total counts for pagination
    $countSql = "
        SELECT COUNT(*) as total,
               SUM(CASE WHEN COALESCE(approved, 0) = 0 THEN 1 ELSE 0 END) as pending,
               SUM(CASE WHEN COALESCE(approved, 0) = 1 THEN 1 ELSE 0 END) as approved
        FROM success_stories ss
        JOIN users u ON ss.user_id = u.id
        $whereClause
    ";
    
    $countStmt = $pdo->prepare($countSql);
    foreach($params as $key => $value) {
        if($key !== ':limit' && $key !== ':offset') {
            $countStmt->bindValue($key, $value);
        }
    }
    $countStmt->execute();
    $counts = $countStmt->fetch(PDO::FETCH_ASSOC);
    
    $totalStories = $counts['total'] ?? 0;
    $pendingCount = $counts['pending'] ?? 0;
    $approvedCount = $counts['approved'] ?? 0;
    
    // Calculate total pages
    $totalPages = ceil($totalStories / $limit);
    
} catch (PDOException $e) {
    error_log("Stories error: " . $e->getMessage());
    $stories = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Success Stories | Admin Dashboard</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&family=Montserrat:wght@900&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS -->
    <link rel="stylesheet" href="dashboard-style.css">
    <style>
        /* Additional styles for stories management */
        .story-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }
        
        .story-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(255, 71, 87, 0.1);
            display: flex;
            flex-direction: column;
            position: relative;
        }
        
        .story-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 24px rgba(255, 71, 87, 0.15);
            border-color: rgba(255, 71, 87, 0.3);
        }
        
        .story-card.pending {
            border-left: 4px solid #FFA502;
            background: linear-gradient(to right, rgba(255, 165, 2, 0.02), rgba(255, 165, 2, 0.05));
        }
        
        .story-card.approved {
            border-left: 4px solid #2ED573;
            background: linear-gradient(to right, rgba(46, 213, 115, 0.02), rgba(46, 213, 115, 0.05));
        }
        
        .story-header {
            padding: 1.5rem 1.5rem 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            position: relative;
        }
        
        .story-avatar {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            overflow: hidden;
            flex-shrink: 0;
            border: 3px solid white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
        }
        
        .story-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .story-avatar .avatar-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
            font-weight: 600;
        }
        
        .story-header-content {
            flex: 1;
            min-width: 0;
        }
        
        .story-header h4 {
            font-size: 1.1rem;
            margin-bottom: 0.25rem;
            color: #2f3542;
            font-weight: 700;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .story-header small {
            font-size: 0.85rem;
            color: #6c757d;
            display: block;
        }
        
        .story-date {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            font-size: 0.75rem;
            color: #8a94a6;
            font-weight: 500;
        }
        
        .story-body {
            padding: 0 1.5rem 1.5rem;
            flex-grow: 1;
        }
        
        .story-meta {
            display: flex;
            gap: 1rem;
            margin: 0.75rem 0 1rem;
            padding: 0.75rem 0;
            border-top: 1px solid rgba(233, 236, 239, 0.5);
            border-bottom: 1px solid rgba(233, 236, 239, 0.5);
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: #495057;
            font-weight: 500;
        }
        
        .meta-item i {
            width: 16px;
            text-align: center;
        }
        
        .weight-loss-icon { color: #FF4757; }
        .duration-icon { color: #667eea; }
        
        .story-body h4 {
            font-size: 1.25rem;
            margin-bottom: 0.75rem;
            color: #2f3542;
            font-weight: 700;
            line-height: 1.4;
        }
        
        .story-content {
            margin: 1rem 0;
            line-height: 1.6;
            color: #495057;
            font-size: 0.95rem;
            max-height: 4.8em; /* 3 lines */
            overflow: hidden;
            position: relative;
        }
        
        .story-content::after {
            content: '';
            position: absolute;
            bottom: 0;
            right: 0;
            width: 30%;
            height: 1.6em;
            background: linear-gradient(to right, rgba(255, 255, 255, 0), rgba(255, 255, 255, 0.95) 50%);
        }
        
        .story-images {
            display: flex;
            gap: 1rem;
            margin: 1.5rem 0;
            flex-wrap: wrap;
        }
        
        .story-img {
            flex: 1;
            min-width: 120px;
            height: 120px;
            border-radius: 10px;
            object-fit: cover;
            border: 2px solid #dee2e6;
            transition: all 0.3s;
            cursor: pointer;
            background: #f8f9fa;
        }
        
        .story-img:hover {
            transform: scale(1.05);
            border-color: #667eea;
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.2);
        }
        
        .story-actions {
            display: flex;
            gap: 0.5rem;
            padding: 1rem 1.5rem;
            background: rgba(248, 249, 250, 0.8);
            border-top: 1px solid rgba(233, 236, 239, 0.5);
            backdrop-filter: blur(10px);
        }
        
        /* Story status badges */
        .story-status {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            text-align: center;
            white-space: nowrap;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-pending { 
            background: rgba(255, 165, 2, 0.12);
            color: #FFA502;
            border: 1px solid rgba(255, 165, 2, 0.3);
        }
        
        .status-approved { 
            background: rgba(46, 213, 115, 0.12);
            color: #2ED573;
            border: 1px solid rgba(46, 213, 115, 0.3);
        }
        
        /* Button styles */
        .btn-action {
            padding: 0.6rem 1rem;
            font-size: 0.9rem;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s;
            text-decoration: none;
            font-family: inherit;
            font-weight: 600;
            border: 1px solid transparent;
            flex: 1;
            background: white;
            color: #495057;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
            text-decoration: none;
        }
        
        .btn-action.btn-view {
            border-color: #667eea;
            color: #667eea;
        }
        
        .btn-action.btn-view:hover {
            background: #667eea;
            color: white;
        }
        
        .btn-action.btn-approve {
            border-color: #2ED573;
            color: #2ED573;
        }
        
        .btn-action.btn-approve:hover {
            background: #2ED573;
            color: white;
        }
        
        .btn-action.btn-reject {
            border-color: #FF4757;
            color: #FF4757;
        }
        
        .btn-action.btn-reject:hover {
            background: #FF4757;
            color: white;
        }
        
        /* Status tabs */
        .status-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            align-items: center;
            flex-wrap: wrap;
            background: white;
            padding: 0.75rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .status-tab {
            padding: 0.75rem 1.5rem;
            border: none;
            background: #f8f9fa;
            border-radius: 8px;
            font-family: inherit;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s;
            color: #495057;
            border: 2px solid transparent;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .status-tab:hover {
            background: #e9ecef;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .status-tab.active {
            background: #FF4757;
            color: white;
            border-color: #FF4757;
            box-shadow: 0 6px 20px rgba(255, 71, 87, 0.25);
        }
        
        .status-tab.active i {
            color: white;
        }
        
        .status-tab .count {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.1rem 0.5rem;
            border-radius: 12px;
            font-size: 0.85rem;
            margin-left: 0.25rem;
        }
        
        /* Search and filter bar */
        .filter-bar {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .search-box {
            flex: 1;
            min-width: 300px;
            position: relative;
        }
        
        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #8a94a6;
        }
        
        .search-box input {
            width: 100%;
            padding: 0.85rem 1rem 0.85rem 2.75rem;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s;
            font-family: 'Poppins', sans-serif;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: #FF4757;
            box-shadow: 0 0 0 3px rgba(255, 71, 87, 0.1);
        }
        
        .clear-search {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #8a94a6;
            cursor: pointer;
            padding: 0.25rem;
            display: none;
        }
        
        .clear-search:hover {
            color: #FF4757;
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e9ecef;
        }
        
        .page-link {
            padding: 0.5rem 1rem;
            border: 1px solid #dee2e6;
            background: white;
            color: #495057;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .page-link:hover {
            background: #f8f9fa;
            border-color: #FF4757;
            color: #FF4757;
        }
        
        .page-link.active {
            background: #FF4757;
            color: white;
            border-color: #FF4757;
        }
        
        .page-link.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6c757d;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            margin: 2rem 0;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            color: #e9ecef;
        }
        
        .empty-state h3 {
            font-size: 1.5rem;
            color: #495057;
            margin-bottom: 0.5rem;
        }
        
        /* Quick stats */
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }
        
        .stat-icon {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .stat-icon.pending { background: rgba(255, 165, 2, 0.1); color: #FFA502; }
        .stat-icon.approved { background: rgba(46, 213, 115, 0.1); color: #2ED573; }
        .stat-icon.total { background: rgba(102, 126, 234, 0.1); color: #667eea; }
        
        .stat-info h3 {
            font-size: 1.75rem;
            margin-bottom: 0.25rem;
            color: #2f3542;
        }
        
        .stat-info p {
            font-size: 0.9rem;
            color: #6c757d;
            margin: 0;
        }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .story-grid {
                grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            }
        }
        
        @media (max-width: 768px) {
            .story-grid {
                grid-template-columns: 1fr;
            }
            
            .filter-bar {
                flex-direction: column;
            }
            
            .search-box {
                min-width: 100%;
            }
            
            .status-tabs {
                overflow-x: auto;
                flex-wrap: nowrap;
                justify-content: flex-start;
                padding-bottom: 0.5rem;
            }
            
            .status-tab {
                white-space: nowrap;
            }
            
            .quick-stats {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 480px) {
            .story-header {
                flex-direction: column;
                text-align: center;
                padding-bottom: 1rem;
            }
            
            .story-date {
                position: static;
                margin-top: 0.5rem;
            }
            
            .story-actions {
                flex-direction: column;
            }
            
            .story-meta {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .story-images {
                flex-direction: column;
            }
            
            .story-img {
                width: 100%;
                max-width: none;
                height: 200px;
            }
        }
        
        /* Weight loss formatting */
        .weight-loss {
            font-weight: 700;
            color: #FF4757;
        }
        
        .duration {
            font-weight: 700;
            color: #667eea;
        }
        
        /* Bulk actions */
        .bulk-actions {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
            align-items: center;
            flex-wrap: wrap;
            padding: 1rem;
            background: rgba(248, 249, 250, 0.8);
            border-radius: 10px;
            backdrop-filter: blur(10px);
        }
        
        .select-all {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: #495057;
            font-weight: 500;
        }
        
        /* Story checkbox */
        .story-checkbox {
            position: absolute;
            top: 1rem;
            left: 1rem;
            z-index: 10;
            width: 20px;
            height: 20px;
            cursor: pointer;
            border: 2px solid #dee2e6;
            border-radius: 4px;
            background: white;
        }
        
        .story-checkbox:checked {
            background: #FF4757;
            border-color: #FF4757;
        }
        
        .story-checkbox:checked::after {
            content: '✓';
            color: white;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 12px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <?php include 'admin-sidebar.php'; ?>

    <div class="main-content">
        <div class="top-bar">
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <form method="GET" action="" style="display: inline;">
                    <input type="text" placeholder="Search stories..." name="search" value="<?php echo htmlspecialchars($searchTerm); ?>">
                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($status); ?>">
                </form>
            </div>
            <div class="top-bar-actions">
                <?php if($pendingCount > 0): ?>
                    <div style="position: relative;">
                        <button class="btn-notification" onclick="window.location.href='?status=pending'">
                            <i class="fas fa-bell"></i>
                        </button>
                        <span class="notification-badge"><?php echo $pendingCount; ?></span>
                    </div>
                <?php endif; ?>
                <button class="btn-primary" onclick="window.location.href='add-success-story.php'">
                    <i class="fas fa-plus"></i> Add Story
                </button>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="welcome-banner">
                <div class="welcome-content">
                    <h1><i class="fas fa-trophy" style="margin-right: 10px; color: #FFD700;"></i> Success Stories</h1>
                    <p>Manage and approve member success stories</p>
                </div>
                <div class="welcome-stats">
                    <div class="stat">
                        <h3><?php echo $totalStories; ?></h3>
                        <p>Total Stories</p>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="quick-stats">
                <div class="stat-card">
                    <div class="stat-icon pending">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $pendingCount; ?></h3>
                        <p>Pending Review</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon approved">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $approvedCount; ?></h3>
                        <p>Approved Stories</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon total">
                        <i class="fas fa-newspaper"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $totalStories; ?></h3>
                        <p>Total Stories</p>
                    </div>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="filter-bar">
                <form method="GET" action="" class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search stories by name, title, content..." 
                           name="search" 
                           value="<?php echo htmlspecialchars($searchTerm); ?>"
                           id="searchInput">
                    <?php if(!empty($searchTerm)): ?>
                        <button type="button" class="clear-search" onclick="clearSearch()">
                            <i class="fas fa-times"></i>
                        </button>
                    <?php endif; ?>
                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($status); ?>">
                </form>
            </div>

            <!-- Status Tabs -->
            <div class="status-tabs">
                <button class="status-tab <?php echo $status === 'pending' ? 'active' : ''; ?>" 
                        onclick="window.location.href='?status=pending<?php echo !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?>'">
                    <i class="fas fa-clock"></i> Pending
                    <span class="count"><?php echo $pendingCount; ?></span>
                </button>
                <button class="status-tab <?php echo $status === 'approved' ? 'active' : ''; ?>" 
                        onclick="window.location.href='?status=approved<?php echo !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?>'">
                    <i class="fas fa-check-circle"></i> Approved
                    <span class="count"><?php echo $approvedCount; ?></span>
                </button>
                <button class="status-tab <?php echo $status === 'all' ? 'active' : ''; ?>" 
                        onclick="window.location.href='?status=all<?php echo !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?>'">
                    <i class="fas fa-list"></i> All Stories
                    <span class="count"><?php echo $totalStories; ?></span>
                </button>
            </div>

            <!-- Stories Grid -->
            <div class="content-card">
                <div class="card-header">
                    <h3><i class="fas fa-filter"></i> <?php echo ucfirst($status); ?> Stories</h3>
                    <div>
                        <span style="font-size: 0.9rem; color: #6c757d; font-weight: 500;">
                            Showing <?php echo count($stories); ?> of <?php echo $totalStories; ?> story<?php echo $totalStories !== 1 ? 's' : ''; ?>
                        </span>
                        <?php if(!empty($searchTerm)): ?>
                            <span style="font-size: 0.9rem; color: #FF4757; margin-left: 1rem;">
                                <i class="fas fa-search"></i> Searching: "<?php echo htmlspecialchars($searchTerm); ?>"
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <?php if(count($stories) > 0): ?>
                        <div class="story-grid" id="storyGrid">
                            <?php foreach($stories as $story): 
                                // Safely get all values with defaults
                                $weightLoss = isset($story['weight_loss']) ? floatval($story['weight_loss']) : 0;
                                $durationMonths = isset($story['duration_months']) ? intval($story['duration_months']) : 6;
                                $isApproved = isset($story['approved']) ? boolval($story['approved']) : true;
                                $fullName = isset($story['full_name']) ? htmlspecialchars($story['full_name']) : 'Unknown Member';
                                $email = isset($story['email']) ? htmlspecialchars($story['email']) : 'unknown@email.com';
                                $title = isset($story['title']) ? htmlspecialchars($story['title']) : 'Success Story';
                                $storyText = isset($story['story_text']) ? htmlspecialchars($story['story_text']) : '';
                                $createdAt = isset($story['created_at']) ? date('M d, Y', strtotime($story['created_at'])) : 'Unknown date';
                                $profileImage = isset($story['profile_image']) && !empty($story['profile_image']) ? $story['profile_image'] : null;
                                $initials = !empty($fullName) ? strtoupper(substr($fullName, 0, 2)) : 'UN';
                            ?>
                                <div class="story-card <?php echo $isApproved ? 'approved' : 'pending'; ?>" 
                                     data-story-id="<?php echo $story['id'] ?? ''; ?>">
                                    
                                    <div class="story-header">
                                        <div class="story-avatar">
                                            <?php if($profileImage): ?>
                                                <img src="uploads/<?php echo htmlspecialchars($profileImage); ?>" 
                                                     alt="<?php echo $fullName; ?>"
                                                     onerror="this.parentElement.innerHTML='<div class=\"avatar-placeholder\"><?php echo $initials; ?></div>'">
                                            <?php else: ?>
                                                <div class="avatar-placeholder"><?php echo $initials; ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="story-header-content">
                                            <h4><?php echo $fullName; ?></h4>
                                            <small><?php echo $email; ?></small>
                                            <span class="story-status status-<?php echo $isApproved ? 'approved' : 'pending'; ?>">
                                                <i class="fas fa-<?php echo $isApproved ? 'check-circle' : 'clock'; ?>"></i>
                                                <?php echo $isApproved ? 'Approved' : 'Pending'; ?>
                                            </span>
                                        </div>
                                        <div class="story-date">
                                            <i class="far fa-calendar"></i> <?php echo $createdAt; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="story-body">
                                        <div class="story-meta">
                                            <?php if($weightLoss > 0): ?>
                                                <div class="meta-item">
                                                    <i class="fas fa-weight scale weight-loss-icon"></i>
                                                    <span class="weight-loss"><?php echo number_format($weightLoss, 1); ?> lbs</span>
                                                    <span style="color: #8a94a6;">lost</span>
                                                </div>
                                            <?php endif; ?>
                                            <?php if($durationMonths > 0): ?>
                                                <div class="meta-item">
                                                    <i class="fas fa-clock duration-icon"></i>
                                                    <span class="duration"><?php echo $durationMonths; ?> month<?php echo $durationMonths !== 1 ? 's' : ''; ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <h4><?php echo $title; ?></h4>
                                        
                                        <div class="story-content">
                                            <?php 
                                                if(!empty($storyText)) {
                                                    echo nl2br(substr($storyText, 0, 250));
                                                    if(strlen($storyText) > 250) {
                                                        echo '...';
                                                    }
                                                } else {
                                                    echo 'No story text provided.';
                                                }
                                            ?>
                                        </div>
                                        
                                        <?php if(isset($story['before_image']) || isset($story['after_image'])): ?>
                                            <div class="story-images">
                                                <?php if(!empty($story['before_image'])): ?>
                                                    <div class="image-container" style="flex: 1; text-align: center;">
                                                        <div style="font-size: 0.8rem; color: #FF4757; margin-bottom: 0.5rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">Before</div>
                                                        <img src="uploads/<?php echo htmlspecialchars($story['before_image']); ?>" 
                                                             alt="Before" 
                                                             class="story-img"
                                                             onerror="this.src='https://via.placeholder.com/300x200/667eea/ffffff?text=Before'">
                                                    </div>
                                                <?php endif; ?>
                                                <?php if(!empty($story['after_image'])): ?>
                                                    <div class="image-container" style="flex: 1; text-align: center;">
                                                        <div style="font-size: 0.8rem; color: #2ED573; margin-bottom: 0.5rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">After</div>
                                                        <img src="uploads/<?php echo htmlspecialchars($story['after_image']); ?>" 
                                                             alt="After" 
                                                             class="story-img"
                                                             onerror="this.src='https://via.placeholder.com/300x200/2ed573/ffffff?text=After'">
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="story-actions">
                                        <button class="btn-action btn-view" onclick="window.location.href='admin-story-view.php?id=<?php echo $story['id'] ?? ''; ?>'">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <?php if(!$isApproved): ?>
                                            <button class="btn-action btn-approve" onclick="approveStory(<?php echo $story['id'] ?? ''; ?>)">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                        <?php else: ?>
                                            <button class="btn-action btn-reject" onclick="unapproveStory(<?php echo $story['id'] ?? ''; ?>)">
                                                <i class="fas fa-times"></i> Unapprove
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn-action btn-reject" onclick="deleteStory(<?php echo $story['id'] ?? ''; ?>)">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if($totalPages > 1): ?>
                            <div class="pagination">
                                <?php if($page > 1): ?>
                                    <a href="?status=<?php echo $status; ?>&search=<?php echo urlencode($searchTerm); ?>&page=1" class="page-link">
                                        <i class="fas fa-angle-double-left"></i>
                                    </a>
                                    <a href="?status=<?php echo $status; ?>&search=<?php echo urlencode($searchTerm); ?>&page=<?php echo $page-1; ?>" class="page-link">
                                        <i class="fas fa-angle-left"></i>
                                    </a>
                                <?php endif; ?>
                                
                                <?php 
                                $start = max(1, $page - 2);
                                $end = min($totalPages, $page + 2);
                                
                                if($start > 1) echo '<span style="color: #6c757d;">...</span>';
                                
                                for($i = $start; $i <= $end; $i++): ?>
                                    <a href="?status=<?php echo $status; ?>&search=<?php echo urlencode($searchTerm); ?>&page=<?php echo $i; ?>" 
                                       class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; 
                                
                                if($end < $totalPages) echo '<span style="color: #6c757d;">...</span>';
                                ?>
                                
                                <?php if($page < $totalPages): ?>
                                    <a href="?status=<?php echo $status; ?>&search=<?php echo urlencode($searchTerm); ?>&page=<?php echo $page+1; ?>" class="page-link">
                                        <i class="fas fa-angle-right"></i>
                                    </a>
                                    <a href="?status=<?php echo $status; ?>&search=<?php echo urlencode($searchTerm); ?>&page=<?php echo $totalPages; ?>" class="page-link">
                                        <i class="fas fa-angle-double-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-newspaper fa-4x"></i>
                            <h3>No Stories Found</h3>
                            <p style="margin-bottom: 1.5rem;">No <?php echo $status; ?> stories to display.</p>
                            <?php if(!empty($searchTerm)): ?>
                                <button class="btn-action" onclick="window.location.href='?status=all'">
                                    <i class="fas fa-times"></i> Clear Search
                                </button>
                            <?php else: ?>
                                <button class="btn-action" onclick="window.location.href='add-success-story.php'">
                                    <i class="fas fa-plus"></i> Add First Story
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function approveStory(storyId) {
            if(confirm('Are you sure you want to approve this success story? It will be visible to all members.')) {
                window.location.href = 'admin-approve-story.php?id=' + storyId;
            }
        }
        
        function unapproveStory(storyId) {
            if(confirm('Are you sure you want to unapprove this story? It will no longer be visible to members.')) {
                window.location.href = 'admin-unapprove-story.php?id=' + storyId;
            }
        }
        
        function deleteStory(storyId) {
            if(confirm('Are you sure you want to delete this success story? This action cannot be undone.')) {
                window.location.href = 'admin-delete-story.php?id=' + storyId;
            }
        }
        
        function clearSearch() {
            window.location.href = '?status=<?php echo $status; ?>';
        }
        
        // Debounced search
        let searchTimeout;
        document.getElementById('searchInput')?.addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.form.submit();
            }, 500);
        });
        
        // Highlight search terms in results
        document.addEventListener('DOMContentLoaded', function() {
            const searchTerm = "<?php echo addslashes($searchTerm); ?>";
            if(searchTerm.trim()) {
                const regex = new RegExp(`(${searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
                const storyCards = document.querySelectorAll('.story-card');
                
                storyCards.forEach(card => {
                    const elements = card.querySelectorAll('.story-header-content, .story-body h4, .story-content');
                    elements.forEach(el => {
                        if(el.textContent.match(regex)) {
                            el.innerHTML = el.innerHTML.replace(regex, '<mark style="background: rgba(255, 71, 87, 0.2); padding: 0.1rem 0.25rem; border-radius: 3px; color: #FF4757; font-weight: 600;">$1</mark>');
                        }
                    });
                });
            }
        });
    </script>
</body>
</html>