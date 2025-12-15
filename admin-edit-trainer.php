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

$trainerId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$success = $error = '';

// Fetch trainer details
if($trainerId > 0) {
    try {
        $query = "
            SELECT t.*, u.full_name, u.email, u.username, u.is_active
            FROM trainers t
            JOIN users u ON t.user_id = u.id
            WHERE t.id = :id AND u.user_type = 'trainer'
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([':id' => $trainerId]);
        $trainer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if(!$trainer) {
            header('Location: manage-trainers.php');
            exit();
        }
    } catch(PDOException $e) {
        error_log("Fetch trainer error: " . $e->getMessage());
        $error = "Failed to load trainer details.";
    }
} else {
    header('Location: manage-trainers.php');
    exit();
}

// Handle form submission
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // Update user table
        $userQuery = "
            UPDATE users 
            SET full_name = :full_name, 
                email = :email,
                is_active = :is_active
            WHERE id = (SELECT user_id FROM trainers WHERE id = :trainer_id)
        ";
        
        $userStmt = $pdo->prepare($userQuery);
        $userStmt->execute([
            ':full_name' => $_POST['full_name'],
            ':email' => $_POST['email'],
            ':is_active' => isset($_POST['is_active']) ? 1 : 0,
            ':trainer_id' => $trainerId
        ]);
        
        // Update trainers table
        $trainerQuery = "
            UPDATE trainers 
            SET specialty = :specialty,
                certification = :certification,
                education = :education,
                years_experience = :years_experience,
                hourly_rate = :hourly_rate,
                bio = :bio
            WHERE id = :id
        ";
        
        $trainerStmt = $pdo->prepare($trainerQuery);
        $trainerStmt->execute([
            ':specialty' => $_POST['specialty'],
            ':certification' => $_POST['certification'],
            ':education' => $_POST['education'],
            ':years_experience' => $_POST['years_experience'],
            ':hourly_rate' => $_POST['hourly_rate'],
            ':bio' => $_POST['bio'],
            ':id' => $trainerId
        ]);
        
        $pdo->commit();
        $success = "Trainer updated successfully!";
        
        // Refresh trainer data
        $stmt->execute([':id' => $trainerId]);
        $trainer = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch(PDOException $e) {
        $pdo->rollBack();
        error_log("Update trainer error: " . $e->getMessage());
        $error = "Failed to update trainer: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Trainer | Admin Dashboard</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&family=Montserrat:wght@900&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS -->
    <link rel="stylesheet" href="dashboard-style.css">
    <style>
        .edit-form-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .form-card {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        
        .form-header {
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .form-header h1 {
            color: #2f3542;
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
        }
        
        .form-header p {
            color: #6c757d;
            font-size: 1.1rem;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #495057;
            font-size: 0.95rem;
        }
        
        .form-control {
            width: 100%;
            padding: 0.85rem 1rem;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #ff6b6b;
            box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.1);
        }
        
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
        }
        
        .checkbox-group label {
            margin-bottom: 0;
        }
        
        .full-width {
            grid-column: 1 / -1;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #dee2e6;
        }
        
        .btn-cancel {
            padding: 0.85rem 2rem;
            background: #e9ecef;
            color: #495057;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-cancel:hover {
            background: #dee2e6;
        }
        
        .btn-update {
            padding: 0.85rem 2rem;
            background: #ff6b6b;
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-update:hover {
            background: #ff5252;
        }
        
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert i {
            font-size: 1.2rem;
        }
        
        .back-button {
            margin-bottom: 2rem;
        }
        
        .back-button .btn-secondary {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <?php include 'admin-sidebar.php'; ?>

    <div class="main-content">
        <div class="edit-form-container">
            <div class="back-button">
                <a href="admin-trainer-view.php?id=<?php echo $trainerId; ?>" class="btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Profile
                </a>
            </div>
            
            <?php if($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <div class="form-card">
                <div class="form-header">
                    <h1>Edit Trainer: <?php echo htmlspecialchars($trainer['full_name']); ?></h1>
                    <p>Update trainer information and professional details</p>
                </div>
                
                <form method="POST" action="">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="full_name">Full Name *</label>
                            <input type="text" id="full_name" name="full_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($trainer['full_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address *</label>
                            <input type="email" id="email" name="email" class="form-control" 
                                   value="<?php echo htmlspecialchars($trainer['email']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="specialty">Specialization *</label>
                            <input type="text" id="specialty" name="specialty" class="form-control" 
                                   value="<?php echo htmlspecialchars($trainer['specialty']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="years_experience">Years of Experience *</label>
                            <input type="number" id="years_experience" name="years_experience" class="form-control" 
                                   value="<?php echo htmlspecialchars($trainer['years_experience']); ?>" min="0" max="50" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="hourly_rate">Hourly Rate ($)</label>
                            <input type="number" id="hourly_rate" name="hourly_rate" class="form-control" 
                                   value="<?php echo htmlspecialchars($trainer['hourly_rate']); ?>" min="0" step="0.01">
                        </div>
                        
                        <div class="form-group">
                            <label for="certification">Certifications</label>
                            <textarea id="certification" name="certification" class="form-control"><?php echo htmlspecialchars($trainer['certification'] ?? ''); ?></textarea>
                            <small style="color: #6c757d; font-size: 0.85rem;">Separate multiple certifications with commas or new lines</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="education">Education</label>
                            <textarea id="education" name="education" class="form-control"><?php echo htmlspecialchars($trainer['education'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="bio">Biography</label>
                            <textarea id="bio" name="bio" class="form-control"><?php echo htmlspecialchars($trainer['bio'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="is_active" name="is_active" 
                                       <?php echo ($trainer['is_active'] == 1) ? 'checked' : ''; ?>>
                                <label for="is_active">Active Trainer</label>
                            </div>
                            <small style="color: #6c757d; font-size: 0.85rem;">Inactive trainers won't appear in booking options</small>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <a href="admin-trainer-view.php?id=<?php echo $trainerId; ?>" class="btn-cancel">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <button type="submit" class="btn-update">
                            <i class="fas fa-save"></i> Update Trainer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>