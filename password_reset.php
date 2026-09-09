<?php

include 'db.php';
include 'send_email.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request.'
    ]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$email = trim($data['email'] ?? '');

if (empty($email)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Email address is required.'
    ]);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Email not found.'
    ]);
    exit;
}

$token = bin2hex(random_bytes(32));

$stmt = $pdo->prepare("UPDATE users SET reset_token = ? WHERE id = ?");
$stmt->execute([$token, $user['id']]);

if (sendPasswordResetEmail($email, $token)) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Password reset link sent to your email.'
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to send password reset email.'
    ]);
}
?>