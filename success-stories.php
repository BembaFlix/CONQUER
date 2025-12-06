<?php
session_start();
require_once 'config/database.php';

try {
    // Fetch featured story
    $featuredStmt = $pdo->query("
        SELECT ss.*, u.full_name, u.username, t.specialty as trainer_specialty
        FROM success_stories ss
        LEFT JOIN users u ON ss.user_id = u.id
        LEFT JOIN trainers tr ON ss.trainer_id = tr.id
        LEFT JOIN users t ON tr.user_id = t.id
        WHERE ss.is_featured = TRUE AND ss.approved = TRUE
        ORDER BY ss.created_at DESC
        LIMIT 1
    ");
    $featuredStory = $featuredStmt->fetch();
    
    // Fetch all stories
    $storiesStmt = $pdo->query("
        SELECT ss.*, u.full_name, u.username
        FROM success_stories ss
        LEFT JOIN users u ON ss.user_id = u.id
        WHERE ss.approved = TRUE
        ORDER BY ss.created_at DESC
        LIMIT 6
    ");
    $stories = $storiesStmt->fetchAll();
    
    // Get stats
    $statsStmt = $pdo->query("
        SELECT 
            COUNT(*) as total_stories,
            AVG(weight_loss) as avg_weight_loss,
            AVG(months_taken) as avg_months
        FROM success_stories 
        WHERE approved = TRUE
    ");
    $stats = $statsStmt->fetch();
    
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Success Stories | CONQUER Gym</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&family=Montserrat:wght@900&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- CSS -->
    <link rel="stylesheet" href="index-style.css">
    <link rel="stylesheet" href="success-stories.css">
</head>
<body>
    <!-- Theme Toggle -->
    <div class="theme-toggle">
        <input type="checkbox" id="theme-switch" class="checkbox">
        <label for="theme-switch" class="switch">
            <i class="fas fa-sun"></i>
            <i class="fas fa-moon"></i>
            <span class="ball"></span>
        </label>
    </div>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.html" class="nav-logo">
                <div class="logo-icon">
                    <i class="fas fa-dumbbell"></i>
                </div>
                <span class="logo-text">CONQUER</span>
            </a>
            
            <div class="nav-menu">
                <ul class="nav-list">
                    <li class="nav-item"><a href="index.html" class="nav-link">Home</a></li>
                    <li class="nav-item"><a href="about.php" class="nav-link">About</a></li>
                    <li class="nav-item"><a href="index.html#memberships" class="nav-link">Memberships</a></li>
                    <li class="nav-item"><a href="index.html#trainers" class="nav-link">Trainers</a></li>
                    <li class="nav-item"><a href="index.html#facilities" class="nav-link">Facilities</a></li>
                    <li class="nav-item"><a href="success-stories.php" class="nav-link active">Success Stories</a></li>
                    <li class="nav-item"><a href="index.html#contact" class="nav-link">Contact</a></li>
                </ul>
                
                <div class="nav-actions">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <button class="btn-login" onclick="window.location.href='logout.php'">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </button>
                    <?php else: ?>
                        <button class="btn-login" onclick="window.location.href='login.php'">
                            <i class="fas fa-user"></i>
                            <span>Login</span>
                        </button>
                    <?php endif; ?>
                    <button class="btn-primary btn-join" onclick="openMembershipModal()">
                        <span>Join Now</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>
            
            <button class="nav-toggle">
                <span class="hamburger"></span>
            </button>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="stories-hero">
        <div class="stories-hero-container">
            <h1 class="stories-hero-title">SUCCESS STORIES</h1>
            <p class="stories-hero-subtitle">
                Real people, real transformations. Be inspired by journeys of determination and triumph.
            </p>
            
            <div class="stats-banner">
                <div class="story-stat">
                    <span class="story-stat-number"><?php echo $stats ? number_format($stats['total_stories']) : '100+'; ?></span>
                    <span class="story-stat-label">Transformations</span>
                </div>
                <div class="story-stat">
                    <span class="story-stat-number"><?php echo $stats ? round($stats['avg_weight_loss'], 1) : '25'; ?>kg</span>
                    <span class="story-stat-label">Average Weight Loss</span>
                </div>
                <div class="story-stat">
                    <span class="story-stat-number"><?php echo $stats ? round($stats['avg_months']) : '6'; ?> mo</span>
                    <span class="story-stat-label">Average Duration</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Filter Section -->
    <section class="stories-filter">
        <div class="filter-container">
            <h2 class="filter-title">Filter by Category</h2>
            <div class="filter-options">
                <button class="filter-btn active" onclick="filterStories('all')">All Stories</button>
                <button class="filter-btn" onclick="filterStories('weight-loss')">Weight Loss</button>
                <button class="filter-btn" onclick="filterStories('muscle-gain')">Muscle Gain</button>
                <button class="filter-btn" onclick="filterStories('fitness')">Fitness</button>
                <button class="filter-btn" onclick="filterStories('recovery')">Recovery</button>
                <button class="filter-btn" onclick="filterStories('over-40')">40+ Transformations</button>
            </div>
        </div>
    </section>

    <!-- Featured Story -->
    <?php if($featuredStory): ?>
    <section class="featured-story">
        <div class="featured-container">
            <div class="featured-images" data-aos="fade-right">
                <div class="before-after">
                    <div class="before-box">
                        <img src="<?php echo $featuredStory['before_image'] ?: 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'; ?>" alt="Before Transformation">
                    </div>
                    <div class="after-box">
                        <img src="<?php echo $featuredStory['after_image'] ?: 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'; ?>" alt="After Transformation">
                    </div>
                </div>
            </div>
            
            <div class="featured-content" data-aos="fade-left">
                <h2><?php echo htmlspecialchars($featuredStory['title']); ?></h2>
                
                <div class="story-meta">
                    <div class="meta-item">
                        <i class="fas fa-user"></i>
                        <span><?php echo htmlspecialchars($featuredStory['full_name'] ?: 'Anonymous Member'); ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-weight"></i>
                        <span><?php echo $featuredStory['weight_loss'] ? $featuredStory['weight_loss'] . ' kg lost' : 'Significant Transformation'; ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-clock"></i>
                        <span><?php echo $featuredStory['months_taken'] ? $featuredStory['months_taken'] . ' months' : 'Ongoing Journey'; ?></span>
                    </div>
                </div>
                
                <p><?php echo nl2br(htmlspecialchars(substr($featuredStory['story_text'], 0, 300) . '...')); ?></p>
                
                <div class="story-quote">
                    "The CONQUER community changed my life. Never thought I could achieve this!"
                </div>
                
                <?php if($featuredStory['trainer_specialty']): ?>
                    <div class="trainer-mention">
                        <i class="fas fa-trophy"></i>
                        <span>Guided by <?php echo htmlspecialchars($featuredStory['trainer_specialty']); ?> trainer</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Stories Grid -->
    <section class="stories-grid-section">
        <div class="section-header" data-aos="fade-up">
            <h2 class="section-title">MORE <span class="highlight">INSPIRING STORIES</span></h2>
            <p class="section-subtitle">Every journey is unique, every success is worth celebrating</p>
        </div>
        
        <div class="stories-grid">
            <?php foreach($stories as $story): ?>
            <div class="story-card" data-aos="fade-up">
                <div class="story-images">
                    <div class="story-before">
                        <img src="<?php echo $story['before_image'] ?: 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'; ?>" alt="Before">
                    </div>
                    <div class="story-after">
                        <img src="<?php echo $story['after_image'] ?: 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'; ?>" alt="After">
                    </div>
                </div>
                <div class="story-content">
                    <h3><?php echo htmlspecialchars($story['title']); ?></h3>
                    <p class="story-duration"><?php echo $story['months_taken'] ? $story['months_taken'] . ' month journey' : 'Ongoing Transformation'; ?></p>
                    <p class="story-excerpt"><?php echo nl2br(htmlspecialchars(substr($story['story_text'], 0, 150) . '...')); ?></p>
                    
                    <div class="story-stats">
                        <?php if($story['weight_loss']): ?>
                        <span class="stat-badge loss">-<?php echo $story['weight_loss']; ?> kg</span>
                        <?php endif; ?>
                        <span class="stat-badge trainer">With Trainer</span>
                    </div>
                    
                    <a href="#" class="read-more">
                        Read Full Story
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if(empty($stories)): ?>
                <p style="grid-column: 1 / -1; text-align: center; padding: 3rem; color: var(--gray);">
                    No success stories available yet. Check back soon!
                </p>
            <?php endif; ?>
        </div>
    </section>

    <!-- Share Your Story -->
    <section class="share-story">
        <div class="share-container">
            <h2>YOUR STORY COULD BE NEXT</h2>
            <p>Share your transformation journey and inspire others</p>
            
            <div class="share-steps">
                <div class="share-step" data-aos="fade-up">
                    <div class="step-number">1</div>
                    <h3>Join CONQUER</h3>
                    <p>Start your fitness journey with us</p>
                </div>
                
                <div class="share-step" data-aos="fade-up" data-aos-delay="100">
                    <div class="step-number">2</div>
                    <h3>Track Progress</h3>
                    <p>Document your transformation milestones</p>
                </div>
                
                <div class="share-step" data-aos="fade-up" data-aos-delay="200">
                    <div class="step-number">3</div>
                    <h3>Share & Inspire</h3>
                    <p>Submit your story to motivate others</p>
                </div>
            </div>
            
            <?php if(isset($_SESSION['user_id'])): ?>
                <button class="btn-share" onclick="window.location.href='submit-story.php'">
                    <i class="fas fa-share-alt"></i>
                    Share Your Story
                </button>
            <?php else: ?>
                <button class="btn-share" onclick="openMembershipModal()">
                    <i class="fas fa-user-plus"></i>
                    Join to Get Started
                </button>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer (Same as index.html) -->
    <?php include 'footer.php'; ?>

    <!-- Scripts -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="main.js"></script>
    
    <script>
        // Initialize AOS
        AOS.init({
            duration: 1000,
            once: true,
            offset: 100
        });
        
        // Filter functionality
        function filterStories(category) {
            const filterBtns = document.querySelectorAll('.filter-btn');
            filterBtns.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            // Here you would normally make an AJAX request or filter client-side
            console.log('Filtering by:', category);
        }
    </script>
</body>
</html>