<?php
session_start();

include 'db.php';

$data = json_decode(file_get_contents('php://input'), true);
$notificationId = $data['notification_id'];
$message = $data['message'];

if (empty($notificationId) || empty($message)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid data provided']);
    exit;
}

//// Update the notification message
$stmt = $pdo->prepare("UPDATE notification SET message = ? WHERE id = ?");
$stmt->execute([$message, $notificationId]);

echo json_encode(['status' => 'success']);
?>
