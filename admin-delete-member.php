<?php
session_start();
require_once 'config/database.php';

if(!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login.php');
    exit();
}

if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: admin-members.php');
    exit();
}

$memberId = intval($_GET['id']);

try {
    $pdo = Database::getInstance()->getConnection();
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Get member email
    $query = "SELECT email FROM users WHERE id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':id' => $memberId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$user) {
        throw new Exception("Member not found");
    }
    
    $email = $user['email'];
    $currentTime = date('Y-m-d H:i:s');
    
    // Soft delete the user
    $updateUser = "UPDATE users SET deleted_at = :deleted_at WHERE id = :id";
    $stmt = $pdo->prepare($updateUser);
    $stmt->execute([':deleted_at' => $currentTime, ':id' => $memberId]);
    
    // Soft delete the gym member record
    $updateGymMember = "UPDATE gym_members SET deleted_at = :deleted_at WHERE Email = :email";
    $stmt = $pdo->prepare($updateGymMember);
    $stmt->execute([':deleted_at' => $currentTime, ':email' => $email]);
    
    $pdo->commit();
    
    $_SESSION['success_message'] = "Member has been moved to recently deleted. You can restore within 30 days.";
    
} catch(Exception $e) {
    if(isset($pdo)) {
        $pdo->rollBack();
    }
    $_SESSION['error_message'] = "Error deleting member: " . $e->getMessage();
}

header('Location: admin-members.php');
exit();
?>