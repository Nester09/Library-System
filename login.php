<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    
    if (empty($email) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        if ($user['lockout_time'] && new DateTime() < new DateTime($user['lockout_time'])) {
            echo json_encode(['status' => 'error', 'message' => 'Account is locked. Try again later.']);
            exit;
        }

        //// Check password
        if (password_verify($password, $user['password'])) {
            session_start();
            if ($user['is_admin']) {
                $_SESSION['admin_id'] = $user['id'];
                echo json_encode(['status' => 'success', 'message' => 'Admin login successful.']);
            } else {
                $_SESSION['user_id'] = $user['id'];
                echo json_encode(['status' => 'success', 'message' => 'User login successful.']);
            }

            //// Reset login attempts
            $stmt = $pdo->prepare("UPDATE users SET login_attempts = 0, lockout_time = NULL WHERE id = ?");
            $stmt->execute([$user['id']]);
        } else {
            //// Increment login attempts
            $stmt = $pdo->prepare("UPDATE users SET login_attempts = login_attempts + 1 WHERE id = ?");
            $stmt->execute([$user['id']]);

            //// Lock account after 3 failed attempts
            if ($user['login_attempts'] + 1 >= 3) {
                $lockoutTime = (new DateTime())->add(new DateInterval('PT15M'))->format('Y-m-d H:i:s');
                $stmt = $pdo->prepare("UPDATE users SET lockout_time = ? WHERE id = ?");
                $stmt->execute([$lockoutTime, $user['id']]);
                echo json_encode(['status' => 'error', 'message' => 'Account locked due to multiple failed login attempts.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Invalid password.']); 
            }
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Email not found.']); 
    }
}
?>