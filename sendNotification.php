<?php
session_start();

include 'db.php';

$data = json_decode(file_get_contents('php://input'), true);
$message = $data['message']; 

//// Insert notification for each user
$stmt = $pdo->prepare("INSERT INTO notification (user_id, message) SELECT id, ? FROM users");
$stmt->execute([$message]);

echo json_encode(['status' => 'success']);
?>
