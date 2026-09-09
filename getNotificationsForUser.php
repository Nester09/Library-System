<?php
session_start();

include 'db.php';

$userId = $_SESSION['user_id']; 

$query = "SELECT * FROM notification WHERE user_id = ? AND is_deleted = 0 ORDER BY sent_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute([$userId]);

$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['notifications' => $notifications]);
?>
