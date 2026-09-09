<?php

include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $welcomeMessage = $_POST['welcome_message'] ?? '';
    $privacyPolicy = $_POST['privacy_policy'] ?? '';
    $libraryHistory = $_POST['library_history'] ?? '';

    //// Prepare statement to update library settings
    $stmt = $pdo->prepare("UPDATE library_settings SET welcome_message = ?, privacy_policy = ?, library_history = ? WHERE id = 1");
    
    if ($stmt->execute([$welcomeMessage, $privacyPolicy, $libraryHistory])) {
        echo json_encode(['status' => 'success', 'message' => 'Library settings updated successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update library settings.']);
    }
}
?>