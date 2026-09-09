<?php

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $bookId = (int) $_POST['id'];

    try {
        $stmt = $pdo->prepare("DELETE FROM returned_books WHERE id = :id");
        $stmt->bindParam(':id', $bookId, PDO::PARAM_INT);

        $stmt->execute();

        //// Check if the row was deleted
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true]);
        } else {
            //// If no rows were affected, return failure
            echo json_encode(['success' => false]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false]);
}
?>
