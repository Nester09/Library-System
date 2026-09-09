<?php

include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $reviewId = $_POST['review_id'];
    $response = $_POST['response'];

    $stmt = $pdo->prepare("UPDATE reviews SET response = ? WHERE id = ?");
    if ($stmt->execute([$response, $reviewId])) {
        echo json_encode(['status' => 'success', 'message' => 'Response submitted successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to submit response.']);
    }
}
?>