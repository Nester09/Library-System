<?php

include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    
    if (empty($username) || empty($email) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Email already exists.']);
        exit;
    }

    //// Check current number of admins
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE is_admin = 1");
    $stmt->execute();
    
    $adminCount = $stmt->fetchColumn();
    
    if ($role === 'admin' && $adminCount >= 2) {
        echo json_encode(['status' => 'error', 'message' => 'Cannot register as admin: maximum number of admins reached.']);
        exit;
    }

    //// Insert new user
    $isAdmin = ($role === 'admin') ? 1 : 0;
    
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, is_admin) VALUES (?, ?, ?, ?)");
    
    if ($stmt->execute([$username, $email, $password, $isAdmin])) {
        echo json_encode(['status' => 'success', 'message' => 'Registration successful.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Registration failed.']);
    }
}
?>