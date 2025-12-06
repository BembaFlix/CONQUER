<?php
session_start();
require_once 'config/database.php';

if(isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if(empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, username, email, password_hash, full_name, user_type FROM users WHERE email = ? AND is_active = TRUE");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['user_type'] = $user['user_type'];
                
                // Update last login
                $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $updateStmt->execute([$user['id']]);
                
                header('Location: dashboard.php');
                exit();
            } else {
                $error = 'Invalid email or password';
            }
        } catch(PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | CONQUER Gym</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&family=Montserrat:wght@900&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS -->
    <link rel="stylesheet" href="login-styles.css">
    <link rel="stylesheet" href="index-style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <div class="login-brand">
                <div class="logo-icon">
                    <i class="fas fa-dumbbell"></i>
                </div>
                <h1>CONQUER</h1>
                <p>Welcome back to your fitness journey</p>
            </div>
            
            <div class="login-features">
                <div class="feature">
                    <i class="fas fa-chart-line"></i>
                    <div>
                        <h3>Track Progress</h3>
                        <p>Monitor your fitness journey with detailed analytics</p>
                    </div>
                </div>
                <div class="feature">
                    <i class="fas fa-calendar-alt"></i>
                    <div>
                        <h3>Book Classes</h3>
                        <p>Reserve spots in your favorite group sessions</p>
                    </div>
                </div>
                <div class="feature">
                    <i class="fas fa-users"></i>
                    <div>
                        <h3>Join Community</h3>
                        <p>Connect with fellow fitness enthusiasts</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="login-right">
            <div class="login-form-container">
                <div class="form-header">
                    <h2>Member Login</h2>
                    <p>Enter your credentials to access your account</p>
                </div>
                
                <?php if($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" class="login-form">
                    <div class="input-group">
                        <label for="email">
                            <i class="fas fa-envelope"></i>
                            <span>Email Address</span>
                        </label>
                        <input type="email" id="email" name="email" required placeholder="you@example.com">
                    </div>
                    
                    <div class="input-group">
                        <label for="password">
                            <i class="fas fa-lock"></i>
                            <span>Password</span>
                        </label>
                        <input type="password" id="password" name="password" required placeholder="••••••••">
                        <button type="button" class="toggle-password" onclick="togglePassword()">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    
                    <div class="form-options">
                        <label class="checkbox">
                            <input type="checkbox" name="remember">
                            <span>Remember me</span>
                        </label>
                        <a href="forgot-password.php" class="forgot-link">Forgot password?</a>
                    </div>
                    
                    <button type="submit" class="btn-login">
                        <i class="fas fa-sign-in-alt"></i>
                        Sign In
                    </button>
                </form>
                
                <div class="form-footer">
    <p>Don't have an account? <a href="register.php">Sign up now</a></p>
    <div class="divider">
        <span>or continue with</span>
    </div>
    <div class="social-login">
        <button type="button" class="social-btn google" onclick="socialLogin('google')">
            <i class="fab fa-google"></i>
            Google
        </button>
        <button type="button" class="social-btn facebook" onclick="socialLogin('facebook')">
            <i class="fab fa-facebook"></i>
            Facebook
        </button>
    </div>
    <a href="index.html" class="back-home">
        <i class="fas fa-arrow-left"></i>
        Back to Home
    </a>
</div>  
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleBtn = document.querySelector('.toggle-password i');
            
            if(passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleBtn.classList.remove('fa-eye');
                toggleBtn.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleBtn.classList.remove('fa-eye-slash');
                toggleBtn.classList.add('fa-eye');
            }
        }
        
        // Simple form validation
        document.querySelector('.login-form').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            if(!email || !password) {
                e.preventDefault();
                alert('Please fill in all fields');
                return false;
            }
            
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if(!emailRegex.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address');
                return false;
            }
        });
    </script>
</body>
</html>