<?php

include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $token = $_GET['token'] ?? '';

    if (empty($token)) {
        die('Invalid password reset link.');
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="assets/bootstrap/css/v4.5.2/bootstrap.min.css">
        <title>Reset Password</title>
    </head>
    <body>
        <div class="container mt-5">
            <form id="reset-form">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <input type="password" name="new_password" class="form-control" placeholder="New Password" minlength="6" required>
                <button type="submit" class="btn btn-primary mt-2">Reset Password</button>
            </form>
        </div>

        <script>
            document.getElementById('reset-form').addEventListener('submit', function(event) {
                event.preventDefault();

                const formData = new FormData(this);

                fetch('reset_password.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);

                    if (data.status === 'success') {
                        window.location.href = 'index.php';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('There was an error resetting your password. Please try again.');
                });
            });
        </script>
    </body>
    </html>
    <?php
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $token = $_POST['token'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';

    if (empty($token) || empty($newPassword)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'All fields are required.'
        ]);
        exit;
    }

    if (strlen($newPassword) < 6) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Password must be at least 6 characters long.'
        ]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid password reset link.'
        ]);
        exit;
    }

    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL WHERE id = ?");

    if ($stmt->execute([$hashedPassword, $user['id']])) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Password has been reset successfully.'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to reset password.'
        ]);
    }

    exit;
}
?>