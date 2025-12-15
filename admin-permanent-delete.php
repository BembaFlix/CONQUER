<?php
session_start();
require_once 'config/database.php';

if(!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login.php');
    exit();
}

if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: admin-recently-deleted.php');
    exit();
}

$trainerId = intval($_GET['id']);

try {
    $pdo = Database::getInstance()->getConnection();
    
    // Start transaction
    $pdo->beginTransaction();
    
    // First, get the user_id for this trainer
    $query = "SELECT user_id FROM trainers WHERE id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':id' => $trainerId]);
    $trainer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$trainer) {
        throw new Exception("Trainer not found");
    }
    
    $userId = $trainer['user_id'];
    
    // Permanently delete the trainer
    $deleteTrainer = "DELETE FROM trainers WHERE id = :id";
    $stmt = $pdo->prepare($deleteTrainer);
    $stmt->execute([':id' => $trainerId]);
    
    // Permanently delete the user account as well
    $deleteUser = "DELETE FROM users WHERE id = :id";
    $stmt = $pdo->prepare($deleteUser);
    $stmt->execute([':id' => $userId]);
    
    $pdo->commit();
    
    $_SESSION['success_message'] = "Trainer has been permanently deleted.";
    
} catch(Exception $e) {
    if(isset($pdo)) {
        $pdo->rollBack();
    }
    $_SESSION['error_message'] = "Error permanently deleting trainer: " . $e->getMessage();
}

header('Location: admin-recently-deleted.php');
exit();
?>