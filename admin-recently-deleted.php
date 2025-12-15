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

// Fetch recently deleted trainers (within last 30 days)
$deletedTrainers = [];
try {
    $query = "
        SELECT 
            t.*, 
            u.full_name, 
            u.email, 
            u.username,
            u.is_active,
            u.created_at,
            t.deleted_at,
            DATEDIFF(NOW(), t.deleted_at) as days_ago,
            COUNT(c.id) as total_classes
        FROM trainers t
        JOIN users u ON t.user_id = u.id
        LEFT JOIN classes c ON t.id = c.trainer_id
        WHERE u.user_type = 'trainer' 
        AND t.deleted_at IS NOT NULL
        AND t.deleted_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY t.id
        ORDER BY t.deleted_at DESC
    ";
    
    $deletedTrainers = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    error_log("Fetch deleted trainers error: " . $e->getMessage());
    $deletedTrainers = [];
}

$totalDeleted = count($deletedTrainers);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recently Deleted Trainers | Admin Dashboard</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&family=Montserrat:wght@900&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS -->
    <link rel="stylesheet" href="dashboard-style.css">
    <style>
        /* Additional styles for deleted trainers */
        .trainer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
            margin-top: 1rem;
            padding-bottom: 2rem;
        }
        
        .trainer-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid #e8e8e8;
            display: flex;
            flex-direction: column;
            height: 100%;
            min-height: 520px;
            position: relative;
        }
        
        .trainer-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.15);
        }
        
        /* Deleted card style */
        .trainer-card.deleted {
            opacity: 0.9;
            border-left: 5px solid #ff6b6b;
        }
        
        .trainer-card.deleted .trainer-header {
            background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);
        }
        
        .deleted-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            z-index: 3;
            background: rgba(255, 107, 107, 0.95);
            color: white;
        }
        
        .days-remaining {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            z-index: 3;
            background: rgba(255, 165, 2, 0.95);
            color: white;
        }
        
        .trainer-header {
            padding: 2rem 1.5rem 1.5rem;
            text-align: center;
            flex-shrink: 0;
            position: relative;
            overflow: hidden;
            min-height: 220px;
        }
        
        .trainer-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(0,0,0,0.1) 0%, rgba(0,0,0,0.05) 100%);
            z-index: 1;
        }
        
        .trainer-header > * {
            position: relative;
            z-index: 2;
        }
        
        .trainer-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin: 0 auto 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            border: 4px solid white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            background: white;
        }
        
        .trainer-header h3 {
            font-size: 1.6rem;
            margin-bottom: 0.75rem;
            font-weight: 700;
            color: #2f3542;
        }
        
        .specialization-tag {
            display: inline-block;
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            font-size: 0.95rem;
            font-weight: 700;
            margin-bottom: 1rem;
            background: rgba(255, 255, 255, 0.95);
            color: inherit;
            border: 2px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .trainer-body {
            padding: 2rem 1.5rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            overflow: visible;
        }
        
        .trainer-info {
            margin-bottom: 1.5rem;
            flex-grow: 1;
        }
        
        .trainer-info p {
            margin-bottom: 1rem;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            color: #495057;
            font-size: 0.95rem;
            line-height: 1.6;
        }
        
        .trainer-info i {
            width: 20px;
            margin-top: 3px;
            flex-shrink: 0;
            text-align: center;
            color: #7f8c8d;
        }
        
        .trainer-stats {
            display: flex;
            justify-content: space-between;
            margin: 1.5rem 0;
            padding: 1.5rem 0;
            border-top: 1px solid #e9ecef;
            border-bottom: 1px solid #e9ecef;
            flex-shrink: 0;
        }
        
        .stat-item {
            text-align: center;
            flex: 1;
        }
        
        .stat-value {
            font-weight: 800;
            font-size: 1.8rem;
            color: #2f3542;
            margin-bottom: 0.25rem;
        }
        
        .stat-item small {
            color: #6c757d;
            font-size: 0.85rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Button styles */
        .btn-sm {
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            color: #495057;
            transition: all 0.3s;
            text-decoration: none;
            font-family: inherit;
            font-weight: 600;
            border: 1px solid #dee2e6;
            flex: 1;
        }
        
        .btn-sm:hover {
            background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
            transform: translateY(-2px);
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .btn-sm.btn-success {
            background: linear-gradient(135deg, #2ed573 0%, #1dd1a1 100%);
            color: white;
            border-color: #2ed573;
        }
        
        .btn-sm.btn-success:hover {
            background: linear-gradient(135deg, #1dd1a1 0%, #1abc9c 100%);
            border-color: #1dd1a1;
        }
        
        .btn-sm.btn-danger {
            background: linear-gradient(135deg, #ff4757 0%, #ff2e43 100%);
            color: white;
            border-color: #ff4757;
        }
        
        .btn-sm.btn-danger:hover {
            background: linear-gradient(135deg, #ff2e43 0%, #ff1e2e 100%);
            border-color: #ff2e43;
        }
        
        .trainer-actions {
            display: flex;
            gap: 1rem;
            margin-top: auto;
            flex-shrink: 0;
        }
        
        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            grid-column: 1 / -1;
        }
        
        .empty-state i {
            color: #e9ecef;
            margin-bottom: 1.5rem;
            font-size: 4rem;
        }
        
        .empty-state h3 {
            color: #495057;
            margin-bottom: 1rem;
            font-size: 1.8rem;
        }
        
        .empty-state p {
            color: #6c757d;
            margin-bottom: 2rem;
            font-size: 1.1rem;
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
        
        /* Dashboard content fixes */
        .dashboard-content {
            overflow-y: auto;
            height: calc(100vh - 120px);
            padding-bottom: 2rem;
        }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .trainer-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .trainer-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .trainer-card {
                max-width: 450px;
                margin: 0 auto;
                min-height: 500px;
            }
        }
        
        @media (max-width: 480px) {
            .trainer-actions {
                flex-direction: column;
            }
            
            .trainer-stats {
                flex-direction: column;
                gap: 1rem;
            }
            
            .stat-item {
                flex: none;
            }
            
            .trainer-card {
                min-height: 520px;
            }
        }
        
        /* Search bar */
        .search-bar {
            position: relative;
            flex: 1;
            max-width: 500px;
        }
        
        .search-bar i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }
        
        #searchInput {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
            background: white;
        }
        
        #searchInput:focus {
            outline: none;
            border-color: #ff6b6b;
            box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.1);
        }
    </style>
</head>
<body>
    <?php include 'admin-sidebar.php'; ?>

    <div class="main-content">
        <div class="top-bar">
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search deleted trainers..." id="searchInput">
            </div>
            <div class="top-bar-actions">
                <button class="btn-primary" onclick="window.location.href='admin-trainers.php'">
                    <i class="fas fa-arrow-left"></i> Back to Active Trainers
                </button>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="welcome-banner">
                <div class="welcome-content">
                    <h1>Recently Deleted Trainers</h1>
                    <p>Trainers deleted within the last 30 days - Can be restored</p>
                </div>
                <div class="welcome-stats">
                    <div class="stat">
                        <h3><?php echo $totalDeleted; ?></h3>
                        <p>Deleted Trainers</p>
                    </div>
                    <div class="stat">
                        <h3>30</h3>
                        <p>Days to Restore</p>
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

            <?php if($totalDeleted == 0): ?>
                <div class="notification-banner">
                    <i class="fas fa-info-circle"></i>
                    <div><strong>No recently deleted trainers found.</strong> Trainers are only kept in this list for 30 days after deletion.</div>
                </div>
            <?php endif; ?>

            <div class="content-card">
                <div class="card-header">
                    <h3>Deleted Trainers (Last 30 Days)</h3>
                    <span class="btn-secondary" style="padding: 0.75rem 1.25rem; font-size: 0.95rem;">
                        <i class="fas fa-clock"></i> Auto-deletes after 30 days
                    </span>
                </div>
                <div class="card-body">
                    <?php if($totalDeleted > 0): ?>
                        <div class="trainer-grid" id="trainerGrid">
                            <?php foreach($deletedTrainers as $index => $trainer): 
                                $rating = floatval($trainer['rating'] ?? 0);
                                $fullStars = floor($rating);
                                $hasHalfStar = ($rating - $fullStars) >= 0.3;
                                $totalClasses = $trainer['total_classes'] ?? 0;
                                $experienceYears = $trainer['years_experience'] ?? 0;
                                $deletedAt = new DateTime($trainer['deleted_at']);
                                $now = new DateTime();
                                $interval = $deletedAt->diff($now);
                                $daysAgo = $interval->days;
                                $daysRemaining = 30 - $daysAgo;
                            ?>
                                <div class="trainer-card deleted" data-trainer-id="<?php echo $trainer['id']; ?>">
                                    <div class="deleted-badge">
                                        DELETED
                                    </div>
                                    <div class="days-remaining">
                                        <?php echo $daysRemaining > 0 ? $daysRemaining . ' days left' : 'EXPIRED'; ?>
                                    </div>
                                    
                                    <div class="trainer-header">
                                        <div class="trainer-avatar">
                                            <i class="fas fa-user-tie"></i>
                                        </div>
                                        <h3><?php echo htmlspecialchars($trainer['full_name'] ?? 'Unknown Trainer'); ?></h3>
                                        <span class="specialization-tag"><?php echo htmlspecialchars($trainer['specialty'] ?? 'Not Specified'); ?></span>
                                        
                                        <!-- Rating display -->
                                        <?php if($rating > 0): ?>
                                        <div class="rating-container">
                                            <div class="rating-value"><?php echo number_format($rating, 1); ?>/5.0</div>
                                            <div class="rating-stars">
                                                <?php for($i = 1; $i <= 5; $i++): ?>
                                                    <?php if($i <= $fullStars): ?>
                                                        <i class="fas fa-star"></i>
                                                    <?php elseif($i == $fullStars + 1 && $hasHalfStar): ?>
                                                        <i class="fas fa-star-half-alt"></i>
                                                    <?php else: ?>
                                                        <i class="far fa-star"></i>
                                                    <?php endif; ?>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="trainer-body">
                                        <div class="trainer-info">
                                            <p><i class="fas fa-envelope"></i> <span><?php echo htmlspecialchars($trainer['email'] ?? 'No email'); ?></span></p>
                                            <p><i class="fas fa-certificate"></i> <span><strong>Certifications:</strong> <?php echo htmlspecialchars($trainer['certification'] ?? 'Not Certified'); ?></span></p>
                                            <p><i class="fas fa-history"></i> <span><strong>Experience:</strong> <?php echo $experienceYears; ?> years</span></p>
                                            <p><i class="fas fa-calendar-times"></i> <span><strong>Deleted:</strong> <?php echo date('M d, Y', strtotime($trainer['deleted_at'])); ?> (<?php echo $daysAgo; ?> days ago)</span></p>
                                            <?php if(!empty($trainer['bio'])): ?>
                                                <p><i class="fas fa-info-circle"></i> <span><?php echo htmlspecialchars(substr($trainer['bio'], 0, 100)); ?><?php echo strlen($trainer['bio']) > 100 ? '...' : ''; ?></span></p>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="trainer-stats">
                                            <div class="stat-item">
                                                <div class="stat-value"><?php echo $experienceYears; ?></div>
                                                <small>Years</small>
                                            </div>
                                            <div class="stat-item">
                                                <div class="stat-value"><?php echo $totalClasses; ?></div>
                                                <small>Classes</small>
                                            </div>
                                            <div class="stat-item">
                                                <div class="stat-value"><?php echo number_format($rating, 1); ?></div>
                                                <small>Rating</small>
                                            </div>
                                        </div>
                                        
                                        <div class="trainer-actions">
                                            <?php if($daysRemaining > 0): ?>
                                                <a href="admin-restore-trainer.php?id=<?php echo $trainer['id']; ?>" class="btn-sm btn-success" onclick="return confirmRestore('<?php echo htmlspecialchars($trainer['full_name']); ?>')">
                                                    <i class="fas fa-undo"></i> Restore
                                                </a>
                                            <?php else: ?>
                                                <button class="btn-sm" disabled style="opacity: 0.5; cursor: not-allowed;">
                                                    <i class="fas fa-ban"></i> Expired
                                                </button>
                                            <?php endif; ?>
                                            <button class="btn-sm btn-danger" onclick="confirmPermanentDelete(<?php echo $trainer['id']; ?>, '<?php echo htmlspecialchars($trainer['full_name']); ?>')">
                                                <i class="fas fa-trash"></i> Delete Permanently
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-trash-restore fa-4x"></i>
                            <h3>No Recently Deleted Trainers</h3>
                            <p>Deleted trainers will appear here for 30 days before being permanently removed.</p>
                            <button class="btn-primary" onclick="window.location.href='admin-trainers.php'">
                                <i class="fas fa-arrow-left"></i> Back to Active Trainers
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function confirmRestore(trainerName) {
            return confirm(`Are you sure you want to restore trainer "${trainerName}"? This will make them active again.`);
        }
        
        function confirmPermanentDelete(trainerId, trainerName) {
            if(confirm(`WARNING: This will permanently delete trainer "${trainerName}" and cannot be undone. Are you sure?`)) {
                window.location.href = 'admin-permanent-delete.php?id=' + trainerId;
            }
        }
        
        // Live search functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase().trim();
            const trainerCards = document.querySelectorAll('.trainer-card');
            const trainerGrid = document.getElementById('trainerGrid');
            let visibleCount = 0;
            
            trainerCards.forEach(card => {
                const trainerName = card.querySelector('h3').textContent.toLowerCase();
                const specialization = card.querySelector('.specialization-tag').textContent.toLowerCase();
                const email = card.querySelector('.trainer-info p:nth-child(1) span').textContent.toLowerCase();
                const certifications = card.querySelector('.trainer-info p:nth-child(2) span').textContent.toLowerCase();
                
                if (trainerName.includes(searchTerm) || 
                    specialization.includes(searchTerm) || 
                    email.includes(searchTerm) ||
                    certifications.includes(searchTerm)) {
                    card.style.display = 'flex';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Show no results message if needed
            const cardBody = document.querySelector('.card-body');
            let noResultsMsg = cardBody.querySelector('.no-results');
            
            if (visibleCount === 0 && searchTerm.length > 0) {
                if (!noResultsMsg) {
                    noResultsMsg = document.createElement('div');
                    noResultsMsg.className = 'empty-state no-results';
                    noResultsMsg.innerHTML = `
                        <i class="fas fa-search fa-3x"></i>
                        <h3>No Matching Deleted Trainers</h3>
                        <p>No deleted trainers found matching "<strong>${searchTerm}</strong>"</p>
                    `;
                    trainerGrid.parentNode.insertBefore(noResultsMsg, trainerGrid.nextSibling);
                }
            } else if (noResultsMsg) {
                noResultsMsg.remove();
            }
        });
        
        // Auto-focus search on page load
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            if(searchInput) {
                setTimeout(() => {
                    searchInput.focus();
                }, 300);
            }
        });
    </script>
</body>
</html>