<?php
session_start();

include 'db.php';

$data = json_decode(file_get_contents('php://input'), true);
$notificationId = $data['notification_id'];

$stmt = $pdo->prepare("UPDATE notification SET is_deleted = 1 WHERE id = ?");
$stmt->execute([$notificationId]);

echo json_encode(['status' => 'success']);
?>
