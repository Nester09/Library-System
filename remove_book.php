<?php

include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $bookId = $_POST['book_id'];

    $stmt = $pdo->prepare("DELETE FROM books WHERE id = ?");
    if ($stmt->execute([$bookId])) {
        echo json_encode(['status' => 'success', 'message' => 'Book removed successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to remove book.']);
    }
}
?>