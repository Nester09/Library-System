<?php

include 'db.php';

$data = json_decode(file_get_contents('php://input'), true);
$notificationId = $data['notification_id'];

$stmt = $pdo->prepare("DELETE FROM notification WHERE id = ?");
$stmt->execute([$notificationId]);

echo json_encode(['status' => 'success']);
?>
