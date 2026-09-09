<?php 

include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $reviewId = $_POST['review_id'];

    $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
    $stmt->execute([$reviewId]);
    echo json_encode(['status' => 'success', 'message' => 'Review deleted successfully.']);
}
?>
