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
    
    // Permanently delete the gym member record
    $deleteGymMember = "DELETE FROM gym_members WHERE Email = :email";
    $stmt = $pdo->prepare($deleteGymMember);
    $stmt->execute([':email' => $email]);
    
    // Permanently delete the user
    $deleteUser = "DELETE FROM users WHERE id = :id";
    $stmt = $pdo->prepare($deleteUser);
    $stmt->execute([':id' => $memberId]);
    
    // Also delete related records if needed (optional)
    // Delete payments
    $deletePayments = "DELETE FROM payments WHERE user_id = :user_id";
    $stmt = $pdo->prepare($deletePayments);
    $stmt->execute([':user_id' => $memberId]);
    
    // Delete bookings
    $deleteBookings = "DELETE FROM bookings WHERE user_id = :user_id";
    $stmt = $pdo->prepare($deleteBookings);
    $stmt->execute([':user_id' => $memberId]);
    
    $pdo->commit();
    
    $_SESSION['success_message'] = "Member has been permanently deleted.";
    
} catch(Exception $e) {
    if(isset($pdo)) {
        $pdo->rollBack();
    }
    $_SESSION['error_message'] = "Error permanently deleting member: " . $e->getMessage();
}

header('Location: admin-recently-deleted-members.php');
exit();
?>