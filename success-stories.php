<?php
session_start();
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
                <li class="nav-item"><a href="about.php" class="nav-link active">About</a></li>
                <li class="nav-item"><a href="index.html#memberships" class="nav-link">Memberships</a></li>
                <li class="nav-item"><a href="index.html#trainers" class="nav-link">Trainers</a></li>
                <li class="nav-item"><a href="index.html#facilities" class="nav-link">Facilities</a></li>
                <li class="nav-item"><a href="success-stories.php" class="nav-link">Success Stories</a></li>
                <li class="nav-item"><a href="index.html#contact" class="nav-link">Contact</a></li>
            </ul>
            
            <div class="nav-actions">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <!-- Desktop buttons for logged in users -->
                    <button class="btn-login" onclick="window.location.href='dashboard.php'">
                        <i class="fas fa-user-circle"></i>
                        <span>Dashboard</span>
                    </button>
                    <button class="btn-login" onclick="window.location.href='logout.php'">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </button>
                <?php else: ?>
                    <!-- Desktop buttons for guests -->
                    <button class="btn-login" onclick="window.location.href='login.php'">
                        <i class="fas fa-user"></i>
                        <span>Login</span>
                    </button>
                    <button class="btn-primary btn-join" onclick="openMembershipModal()">
                        <span>Join Now</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                <?php endif; ?>
                
                <!-- Sidebar toggle button for mobile -->
                <button class="sidebar-btn-nav" onclick="toggleSidebar()">
                    <i class="fas fa-user-circle"></i>
                </button>
            </div>
        </div>
        
        <button class="nav-toggle">
            <span class="hamburger"></span>
        </button>
    </div>
</nav>
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
        <button class="btn-login" onclick="window.location.href='dashboard.php'">
            <i class="fas fa-user-circle"></i>
            <span>Dashboard</span>
        </button>
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
                    <span class="story-stat-number">100+</span>
                    <span class="story-stat-label">Transformations</span>
                </div>
                <div class="story-stat">
                    <span class="story-stat-number">25kg</span>
                    <span class="story-stat-label">Average Weight Loss</span>
                </div>
                <div class="story-stat">
                    <span class="story-stat-number">6 mo</span>
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
    <section class="featured-story">
        <div class="featured-container">
            <div class="featured-images" data-aos="fade-right">
                <div class="before-after">
                    <div class="before-box">
                        <img src="https://images.unsplash.com/photo-1558618666-fcd25c85cd64?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Before Transformation">
                    </div>
                    <div class="after-box">
                        <img src="https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="After Transformation">
                    </div>
                </div>
            </div>
            
            <div class="featured-content" data-aos="fade-left">
                <h2>From 120kg to 80kg: My Journey to Health</h2>
                
                <div class="story-meta">
                    <div class="meta-item">
                        <i class="fas fa-user"></i>
                        <span>John Doe</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-weight"></i>
                        <span>40 kg lost</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-clock"></i>
                        <span>8 months journey</span>
                    </div>
                </div>
                
                <p>"I never thought I could lose weight and gain confidence. CONQUER Gym changed my life completely. With the help of expert trainers and a supportive community, I transformed not just my body but my entire lifestyle..."</p>
                
                <div class="story-quote">
                    "The CONQUER community changed my life. Never thought I could achieve this!"
                </div>
                
                <div class="trainer-mention">
                    <i class="fas fa-trophy"></i>
                    <span>Guided by Strength & Conditioning trainer</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Stories Grid -->
    <section class="stories-grid-section">
        <div class="section-header" data-aos="fade-up">
            <h2 class="section-title">MORE <span class="highlight">INSPIRING STORIES</span></h2>
            <p class="section-subtitle">Every journey is unique, every success is worth celebrating</p>
        </div>
        
        <div class="stories-grid">
            <div class="story-card" data-aos="fade-up">
                <div class="story-images">
                    <div class="story-before">
                        <img src="https://images.unsplash.com/photo-1558618666-fcd25c85cd64?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Before">
                    </div>
                    <div class="story-after">
                        <img src="https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="After">
                    </div>
                </div>
                <div class="story-content">
                    <h3>Strength Gained, Confidence Restored</h3>
                    <p class="story-duration">6 month journey</p>
                    <p class="story-excerpt">After a serious injury, I thought I'd never be active again. CONQUER's rehabilitation program helped me regain strength and confidence...</p>
                    
                    <div class="story-stats">
                        <span class="stat-badge loss">-15 kg</span>
                        <span class="stat-badge trainer">With Trainer</span>
                    </div>
                    
                    <a href="#" class="read-more">
                        Read Full Story
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
            
            <div class="story-card" data-aos="fade-up">
                <div class="story-images">
                    <div class="story-before">
                        <img src="https://images.unsplash.com/photo-1558618666-fcd25c85cd64?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Before">
                    </div>
                    <div class="story-after">
                        <img src="https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="After">
                    </div>
                </div>
                <div class="story-content">
                    <h3>Yoga Journey to Inner Peace</h3>
                    <p class="story-duration">4 month journey</p>
                    <p class="story-excerpt">Struggling with stress and anxiety, I found solace in CONQUER's yoga classes. The transformation has been mental as much as physical...</p>
                    
                    <div class="story-stats">
                        <span class="stat-badge loss">-10 kg</span>
                        <span class="stat-badge trainer">With Trainer</span>
                    </div>
                    
                    <a href="#" class="read-more">
                        Read Full Story
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
            
            <div class="story-card" data-aos="fade-up">
                <div class="story-images">
                    <div class="story-before">
                        <img src="https://images.unsplash.com/photo-1558618666-fcd25c85cd64?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Before">
                    </div>
                    <div class="story-after">
                        <img src="https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="After">
                    </div>
                </div>
                <div class="story-content">
                    <h3>Building Muscle at 50</h3>
                    <p class="story-duration">12 month journey</p>
                    <p class="story-excerpt">Age is just a number! At 50, I've never felt stronger or more energetic. The trainers created a perfect program for my age and goals...</p>
                    
                    <div class="story-stats">
                        <span class="stat-badge loss">+8kg muscle</span>
                        <span class="stat-badge trainer">With Trainer</span>
                    </div>
                    
                    <a href="#" class="read-more">
                        Read Full Story
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
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

    <!-- Registration Modal -->
    <div class="modal-overlay" id="membershipModal">
        <div class="modal-container">
            <div class="modal-header">
                <h2>Join the Conquer Community</h2>
                <button class="modal-close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="modal-body">
                <form id="registrationForm">
                    <div class="form-step active" id="step1">
                        <h3>Choose Your Plan</h3>
                        <div class="plan-options">
                            <div class="plan-option" onclick="selectPlanOption('warrior')">
                                <h4>Warrior</h4>
                                <div class="price">$29<span>/month</span></div>
                                <p>Basic access</p>
                            </div>
                            <div class="plan-option popular" onclick="selectPlanOption('champion')">
                                <h4>Champion</h4>
                                <div class="price">$49<span>/month</span></div>
                                <p>Most popular</p>
                            </div>
                            <div class="plan-option" onclick="selectPlanOption('legend')">
                                <h4>Legend</h4>
                                <div class="price">$79<span>/month</span></div>
                                <p>Premium experience</p>
                            </div>
                        </div>
                        
                        <button type="button" class="btn-next" onclick="nextStep()">
                            Next: Personal Details
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                    
                    <div class="form-step" id="step2">
                        <h3>Personal Information</h3>
                        <div class="form-group">
                            <input type="text" name="name" placeholder="Full Name" required>
                        </div>
                        <div class="form-group">
                            <input type="email" name="email" placeholder="Email Address" required>
                        </div>
                        <div class="form-group">
                            <input type="tel" name="phone" placeholder="Phone Number">
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" class="btn-back" onclick="prevStep()">
                                <i class="fas fa-arrow-left"></i>
                                Back
                            </button>
                            <button type="button" class="btn-next" onclick="nextStep()">
                                Next: Account Setup
                                <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-step" id="step3">
                        <h3>Account Setup</h3>
                        <div class="form-group">
                            <input type="password" name="password" placeholder="Create Password" required>
                        </div>
                        <div class="form-group">
                            <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                        </div>
                        
                        <div class="form-check">
                            <input type="checkbox" id="terms" name="terms" required>
                            <label for="terms">
                                I agree to the <a href="#">Terms & Conditions</a> and <a href="#">Privacy Policy</a>
                            </label>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" class="btn-back" onclick="prevStep()">
                                <i class="fas fa-arrow-left"></i>
                                Back
                            </button>
                            <button type="submit" class="btn-submit">
                                <i class="fas fa-check-circle"></i>
                                Complete Registration
                            </button>
                        </div>
                    </div>
                </form>
                
                <div class="progress-bar">
                    <div class="progress-step active" data-step="1"></div>
                    <div class="progress-step" data-step="2"></div>
                    <div class="progress-step" data-step="3"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-brand">
                <div class="footer-logo">
                    <i class="fas fa-dumbbell"></i>
                    <span>CONQUER</span>
                </div>
                <p>Redefining fitness limits since 2010</p>
                <div class="footer-social">
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                    <a href="#"><i class="fab fa-tiktok"></i></a>
                </div>
            </div>
            
            <div class="footer-links">
                <div class="link-group">
                    <h4>Membership</h4>
                    <a href="index.html#memberships">Plans & Pricing</a>
                    <a href="#" onclick="openMembershipModal()">Free Trial</a>
                    <a href="index.html#contact">Corporate Plans</a>
                    <a href="index.html#contact">Student Discount</a>
                </div>
                
                <div class="link-group">
                    <h4>Facilities</h4>
                    <a href="index.html#facilities">Equipment</a>
                    <a href="index.html#contact">Classes Schedule</a>
                    <a href="index.html#trainers">Personal Training</a>
                    <a href="index.html#facilities">Recovery Center</a>
                </div>
                
                <div class="link-group">
                    <h4>Company</h4>
                    <a href="about.php">About Us</a>
                    <a href="index.html#contact">Careers</a>
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                </div>
            </div>
            
            <div class="footer-contact">
                <h4>Contact Us</h4>
                <p><i class="fas fa-map-marker-alt"></i> 123 Fitness Street, Cityville</p>
                <p><i class="fas fa-phone"></i> (555) 123-4567</p>
                <p><i class="fas fa-envelope"></i> info@conquergym.com</p>
                <p><i class="fas fa-clock"></i> Open 24/7 for Elite Members</p>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; 2024 Conquer Gym. All rights reserved. | Made with <i class="fas fa-heart"></i> for fitness warriors</p>
        </div>
    </footer>

    <!-- Back to Top -->
    <button class="back-to-top">
        <i class="fas fa-chevron-up"></i>
    </button>

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
        
        // Close modal when clicking outside
        document.addEventListener('click', function(e) {
            const modal = document.getElementById('membershipModal');
            if (e.target === modal) {
                closeModal();
            }
        });
    </script>
</body>
</html>