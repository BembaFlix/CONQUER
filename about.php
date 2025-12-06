<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us | CONQUER Gym</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/icons/dumbbell.svg">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&family=Montserrat:wght@900&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- CSS -->
    <link rel="stylesheet" href="about-style.css">
    <link rel="stylesheet" href="index-style.css">
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
                    <li class="nav-item"><a href="about.php" class="nav-link active">About</a></li>
                    <li class="nav-item"><a href="index.html#memberships" class="nav-link">Memberships</a></li>
                    <li class="nav-item"><a href="index.html#trainers" class="nav-link">Trainers</a></li>
                    <li class="nav-item"><a href="index.html#facilities" class="nav-link">Facilities</a></li>
                    <li class="nav-item"><a href="success-stories.php" class="nav-link">Success Stories</a></li>
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
    <section class="about-hero">
        <div class="about-hero-container">
            <h1 class="about-hero-title">OUR STORY</h1>
            <p class="about-hero-subtitle">
                From a single garage gym to a fitness revolution spanning multiple locations. 
                Our journey is about empowering individuals to conquer their limits.
            </p>
            
            <div class="about-hero-stats">
                <div class="about-stat">
                    <span class="about-stat-number" data-count="14">0</span>
                    <span class="about-stat-label">Years of Excellence</span>
                </div>
                <div class="about-stat">
                    <span class="about-stat-number" data-count="5">0</span>
                    <span class="about-stat-label">Locations</span>
                </div>
                <div class="about-stat">
                    <span class="about-stat-number" data-count="10000">0</span>
                    <span class="about-stat-label">Lives Transformed</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Mission Section -->
    <section class="our-mission">
        <div class="mission-container">
            <div class="mission-content">
                <h2>OUR <span class="mission-highlight">MISSION</span></h2>
                <p>
                    At CONQUER, we believe fitness is more than just physical transformation. 
                    It's about mental strength, community support, and achieving what you once thought impossible.
                </p>
                <p>
                    Founded in 2010 by former athlete Mark Johnson, our gym was born from a simple idea: 
                    create a space where everyone feels empowered to push beyond their limits, regardless of their starting point.
                </p>
                
                <div class="mission-values">
                    <div class="value-item" data-aos="fade-up">
                        <div class="value-icon">
                            <i class="fas fa-heart-pulse"></i>
                        </div>
                        <h4>Health First</h4>
                        <p>Safe, effective workouts tailored to individual needs</p>
                    </div>
                    
                    <div class="value-item" data-aos="fade-up" data-aos-delay="100">
                        <div class="value-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h4>Community</h4>
                        <p>Supportive environment that fosters growth</p>
                    </div>
                    
                    <div class="value-item" data-aos="fade-up" data-aos-delay="200">
                        <div class="value-icon">
                            <i class="fas fa-gem"></i>
                        </div>
                        <h4>Excellence</h4>
                        <p>Top-tier equipment and certified professionals</p>
                    </div>
                    
                    <div class="value-item" data-aos="fade-up" data-aos-delay="300">
                        <div class="value-icon">
                            <i class="fas fa-infinity"></i>
                        </div>
                        <h4>Innovation</h4>
                        <p>Constantly evolving with fitness trends and science</p>
                    </div>
                </div>
            </div>
            
            <div class="mission-image" data-aos="fade-left">
                <img src="https://images.unsplash.com/photo-1534367507877-0edd93bd013b?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80" alt="Our Founder">
            </div>
        </div>
    </section>

    <!-- Timeline Section -->
    <section class="our-journey">
        <div class="section-header" data-aos="fade-up">
            <h2 class="section-title">OUR <span class="highlight">JOURNEY</span></h2>
            <p class="section-subtitle">Milestones in our fitness revolution</p>
        </div>
        
        <div class="timeline">
            <div class="timeline-item" data-aos="fade-right">
                <span class="timeline-year">2010</span>
                <div class="timeline-content">
                    <h3>The Beginning</h3>
                    <p>Mark Johnson opens the first CONQUER Gym in a converted warehouse with 5 trainers and 50 founding members.</p>
                </div>
                <div class="timeline-dot"></div>
            </div>
            
            <div class="timeline-item" data-aos="fade-left">
                <span class="timeline-year">2014</span>
                <div class="timeline-content">
                    <h3>First Expansion</h3>
                    <p>Opened second location downtown. Introduced 24/7 access for premium members.</p>
                </div>
                <div class="timeline-dot"></div>
            </div>
            
            <div class="timeline-item" data-aos="fade-right">
                <span class="timeline-year">2017</span>
                <div class="timeline-content">
                    <h3>Award Recognition</h3>
                    <p>Voted "Best Gym in the City" for three consecutive years. Launched CONQUER Kids program.</p>
                </div>
                <div class="timeline-dot"></div>
            </div>
            
            <div class="timeline-item" data-aos="fade-left">
                <span class="timeline-year">2020</span>
                <div class="timeline-content">
                    <h3>Digital Transformation</h3>
                    <p>Launched virtual training platform during pandemic. Expanded to three more locations.</p>
                </div>
                <div class="timeline-dot"></div>
            </div>
            
            <div class="timeline-item" data-aos="fade-right">
                <span class="timeline-year">2024</span>
                <div class="timeline-content">
                    <h3>Present Day</h3>
                    <p>5 locations, 50+ trainers, 5000+ members. Introduced AI-powered workout plans.</p>
                </div>
                <div class="timeline-dot"></div>
            </div>
        </div>
    </section>

    <!-- Leadership Team -->
    <section class="leadership-team">
        <div class="section-header" data-aos="fade-up">
            <h2 class="section-title">LEADERSHIP <span class="highlight">TEAM</span></h2>
            <p class="section-subtitle">The visionaries behind CONQUER's success</p>
        </div>
        
        <div class="team-container">
            <div class="team-card" data-aos="fade-up">
                <div class="team-image">
                    <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Mark Johnson">
                </div>
                <div class="team-info">
                    <h3>Mark Johnson</h3>
                    <p class="team-position">Founder & CEO</p>
                    <p class="team-bio">Former professional athlete with 20+ years in fitness industry</p>
                    <div class="team-social">
                        <a href="#"><i class="fab fa-linkedin"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
            
            <div class="team-card" data-aos="fade-up" data-aos-delay="100">
                <div class="team-image">
                    <img src="https://images.unsplash.com/photo-1494790108755-2616b612b786?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Sarah Chen">
                </div>
                <div class="team-info">
                    <h3>Sarah Chen</h3>
                    <p class="team-position">Head of Training</p>
                    <p class="team-bio">NASM Master Trainer with expertise in rehabilitation</p>
                    <div class="team-social">
                        <a href="#"><i class="fab fa-linkedin"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
            
            <div class="team-card" data-aos="fade-up" data-aos-delay="200">
                <div class="team-image">
                    <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Michael Rodriguez">
                </div>
                <div class="team-info">
                    <h3>Michael Rodriguez</h3>
                    <p class="team-position">Operations Director</p>
                    <p class="team-bio">MBA graduate focused on sustainable fitness business</p>
                    <div class="team-social">
                        <a href="#"><i class="fab fa-linkedin"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="about-cta">
        <div class="cta-container">
            <h2>READY TO CONQUER?</h2>
            <p>Join thousands who have transformed their lives with us</p>
            <div class="cta-buttons">
                <button class="btn-primary" onclick="openMembershipModal()">
                    <span>Start Free Trial</span>
                    <i class="fas fa-fire"></i>
                </button>
                <button class="btn-secondary" onclick="window.location.href='index.html#contact'">
                    <i class="fas fa-phone"></i>
                    <span>Contact Us</span>
                </button>
            </div>
        </div>
    </section>

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
        
        // Animate counter numbers
        function animateCounter(element, target) {
            let current = 0;
            const increment = target / 100;
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    element.textContent = target;
                    clearInterval(timer);
                } else {
                    element.textContent = Math.floor(current);
                }
            }, 20);
        }
        
        // Initialize counters when in viewport
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const counters = entry.target.querySelectorAll('[data-count]');
                    counters.forEach(counter => {
                        const target = parseInt(counter.getAttribute('data-count'));
                        animateCounter(counter, target);
                    });
                }
            });
        }, { threshold: 0.5 });
        
        // Observe hero stats
        const heroStats = document.querySelector('.about-hero-stats');
        if (heroStats) observer.observe(heroStats);
        
        // Modal functions (copied from main.js)
        function openMembershipModal() {
            document.getElementById('membershipModal').style.display = 'flex';
        }
        
        function closeModal() {
            document.getElementById('membershipModal').style.display = 'none';
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