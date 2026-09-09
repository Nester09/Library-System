<?php

include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $bookId = $_POST['book_id'];
    $title = $_POST['title'];
    $author = $_POST['author'];
    $copies = $_POST['copies'];

    $stmt = $pdo->prepare("UPDATE books SET title = ?, author = ?, total_copies = ?, available_copies = ? WHERE id = ?");
    if ($stmt->execute([$title, $author, $copies, $copies, $bookId])) {
        echo json_encode(['status' => 'success', 'message' => 'Book details updated successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update book details.']);
    }
}
?>