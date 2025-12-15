<?php
session_start();
require_once 'config/database.php';

if(!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login.php');
    exit();
}

if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: admin-recently-deleted-members.php');
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
    
    // Restore the user
    $updateUser = "UPDATE users SET deleted_at = NULL WHERE id = :id";
    $stmt = $pdo->prepare($updateUser);
    $stmt->execute([':id' => $memberId]);
    
    // Restore the gym member record
    $updateGymMember = "UPDATE gym_members SET deleted_at = NULL WHERE Email = :email";
    $stmt = $pdo->prepare($updateGymMember);
    $stmt->execute([':email' => $email]);
    
    $pdo->commit();
    
    $_SESSION['success_message'] = "Member has been successfully restored!";
    
} catch(Exception $e) {
    if(isset($pdo)) {
        $pdo->rollBack();
    }
    $_SESSION['error_message'] = "Error restoring member: " . $e->getMessage();
}

header('Location: admin-recently-deleted-members.php');
exit();
?>