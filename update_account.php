<?php
session_start();

require 'db.php'; 

$userId = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

//// Decode the incoming JSON request
$data = json_decode(file_get_contents('php://input'), true);

$currentPassword = $data['currentPassword'];
$newPassword = $data['newPassword'];
$username = $data['username'];
$email = $data['email'];

//// Verify the current password
if (password_verify($currentPassword, $user['password'])) {
    if (!empty($newPassword)) {
        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$username, $email, $newPasswordHash, $userId]);
    } else {
        $sql = "UPDATE users SET username = ?, email = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$username, $email, $userId]);
    }

    echo json_encode(['success' => true, 'message' => 'Account updated successfully!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
}
?>
