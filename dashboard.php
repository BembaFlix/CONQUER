<?php
session_start();
require_once 'config/database.php';

if(!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get user info
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    // Get gym member info
    $memberStmt = $pdo->prepare("SELECT * FROM gym_members WHERE Email = ?");
    $memberStmt->execute([$user['email']]);
    $member = $memberStmt->fetch();
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | CONQUER Gym</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&family=Montserrat:wght@900&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }
        
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .dashboard-header {
            background: linear-gradient(135deg, #ff4757 0%, #ff6b81 100%);
            color: white;
            padding: 3rem 2rem;
            border-radius: 20px;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .welcome-message h1 {
            font-family: 'Montserrat', sans-serif;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        
        .welcome-message p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .dashboard-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
        
        .card-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .card-header i {
            font-size: 1.5rem;
            color: #ff4757;
            background: rgba(255, 71, 87, 0.1);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .card-header h3 {
            font-size: 1.2rem;
            color: #333;
        }
        
        .user-info p {
            margin-bottom: 0.5rem;
            color: #666;
        }
        
        .user-info strong {
            color: #333;
        }
        
        .quick-actions {
            list-style: none;
        }
        
        .quick-actions li {
            margin-bottom: 0.75rem;
        }
        
        .quick-actions a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: #333;
            text-decoration: none;
            padding: 0.75rem;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .quick-actions a:hover {
            background: #f8f9fa;
            color: #ff4757;
            transform: translateX(5px);
        }
        
        .quick-actions i {
            width: 24px;
            text-align: center;
        }
        
        .btn-logout {
            background: #ff4757;
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 2rem auto;
            transition: all 0.3s ease;
        }
        
        .btn-logout:hover {
            background: #ff2e43;
            transform: translateY(-2px);
        }
        
        .dashboard-footer {
            text-align: center;
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid #ddd;
            color: #666;
        }
        
        .dashboard-footer a {
            color: #ff4757;
            text-decoration: none;
        }
        
        @media (max-width: 768px) {
            .dashboard-container {
                padding: 1rem;
            }
            
            .dashboard-header {
                padding: 2rem 1rem;
            }
            
            .welcome-message h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <div class="welcome-message">
                <h1>Welcome, <?php echo htmlspecialchars($user['full_name']); ?>!</h1>
                <p>Your fitness journey starts now at CONQUER Gym</p>
            </div>
        </div>
        
        <div class="dashboard-grid">
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-user"></i>
                    <h3>Your Profile</h3>
                </div>
                <div class="user-info">
                    <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                    <p><strong>Full Name:</strong> <?php echo htmlspecialchars($user['full_name']); ?></p>
                    <p><strong>Member Since:</strong> <?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
                </div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-dumbbell"></i>
                    <h3>Membership Details</h3>
                </div>
                <div class="user-info">
                    <?php if($member): ?>
                        <p><strong>Plan:</strong> <?php echo htmlspecialchars($member['MembershipPlan']); ?></p>
                        <p><strong>Status:</strong> <span style="color: #2ed573; font-weight: 600;"><?php echo htmlspecialchars($member['MembershipStatus']); ?></span></p>
                        <p><strong>Joined:</strong> <?php echo date('F j, Y', strtotime($member['JoinDate'])); ?></p>
                    <?php else: ?>
                        <p>Membership details loading...</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-bolt"></i>
                    <h3>Quick Actions</h3>
                </div>
                <ul class="quick-actions">
                    <li><a href="index.html#memberships"><i class="fas fa-eye"></i> View Membership Plans</a></li>
                    <li><a href="index.html#trainers"><i class="fas fa-users"></i> Browse Trainers</a></li>
                    <li><a href="success-stories.php"><i class="fas fa-star"></i> View Success Stories</a></li>
                    <li><a href="index.html#facilities"><i class="fas fa-building"></i> Explore Facilities</a></li>
                    <li><a href="index.html#contact"><i class="fas fa-phone"></i> Contact Support</a></li>
                </ul>
            </div>
        </div>
        
        <div style="text-align: center;">
            <button class="btn-logout" onclick="window.location.href='logout.php'">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </button>
        </div>
        
        <div class="dashboard-footer">
            <p>Need help? <a href="index.html#contact">Contact our support team</a></p>
            <p>&copy; 2024 CONQUER Gym. All rights reserved.</p>
        </div>
    </div>
</body>
</html>