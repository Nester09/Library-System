<?php

session_start();

include 'db.php';

header('Content-Type: application/json');

//// Ensure an admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized access.'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method.'
    ]);
    exit;
}

if (!isset($_POST['user_id']) || !is_numeric($_POST['user_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid user ID.'
    ]);
    exit;
}

$userId = (int) $_POST['user_id'];

try {

    //// Start transaction
    $pdo->beginTransaction();

    //// Verify the target user exists and is not an admin
    $stmt = $pdo->prepare("
        SELECT id, username, is_admin
        FROM users
        WHERE id = ?
        FOR UPDATE
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('User not found.');
    }

    //// This file is only for deleting normal users
    if ((int) $user['is_admin'] === 1) {
        throw new Exception('Admin accounts cannot be deleted using this action.');
    }

    //// Get all active borrowed/requested books
    $stmt = $pdo->prepare("
        SELECT id, book_id
        FROM borrowed_books
        WHERE user_id = ?
        FOR UPDATE
    ");
    $stmt->execute([$userId]);
    $borrowedBooks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    //// Restore copies for every active borrowed/requested book
    foreach ($borrowedBooks as $borrowedBook) {

        $bookId = (int) $borrowedBook['book_id'];

        //// Ensure available copies never exceed total copies
        $stmt = $pdo->prepare("
            UPDATE books
            SET available_copies = CASE
                WHEN available_copies < total_copies
                THEN available_copies + 1
                ELSE total_copies
            END
            WHERE id = ?
        ");
        $stmt->execute([$bookId]);
    }

    //// Delete active borrow records
    $stmt = $pdo->prepare("
        DELETE FROM borrowed_books
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);

    //// Delete returned book history
    $stmt = $pdo->prepare("
        DELETE FROM returned_books
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);

    //// Delete notifications
    $stmt = $pdo->prepare("
        DELETE FROM notification
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);

    //// Delete the user
    //// Reviews are automatically deleted by ON DELETE CASCADE
    $stmt = $pdo->prepare("
        DELETE FROM users
        WHERE id = ? AND is_admin = 0
    ");
    $stmt->execute([$userId]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('Failed to delete user.');
    }

    //// Commit transaction
    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'User and all associated records were deleted successfully.'
    ]);

} catch (Exception $e) {

    //// Roll back everything if something fails
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

exit;

?>