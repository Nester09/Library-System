<?php

session_start();

include 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'You need to be logged in to delete your account.'
    ]);
    exit;
}

$userId = (int) $_SESSION['user_id'];

try {
    
    //// Start transaction to ensure all operations succeed or fail together
    $pdo->beginTransaction();

    //// Lock and fetch the user to ensure the account still exists
    $stmt = $pdo->prepare("
        SELECT id 
        FROM users 
        WHERE id = ? AND is_admin = 0
        FOR UPDATE
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('User account not found.');
    }

    //// Get all active borrowed books (pending or confirmed)
    //// These are books whose available copies were already reduced
    $stmt = $pdo->prepare("
        SELECT id, book_id
        FROM borrowed_books
        WHERE user_id = ?
        FOR UPDATE
    ");
    $stmt->execute([$userId]);
    $borrowedBooks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    //// Return every active borrowed/requested book to inventory
    foreach ($borrowedBooks as $borrowedBook) {

        $bookId = (int) $borrowedBook['book_id'];

        //// Restore the available copy without exceeding total copies
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

    //// Delete all active borrowing records
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
    //// This is also protected by ON DELETE CASCADE,
    //// but deleting explicitly keeps the process clear
    $stmt = $pdo->prepare("
        DELETE FROM notification
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);

    //// Delete the user
    //// Reviews will automatically be deleted because of ON DELETE CASCADE
    $stmt = $pdo->prepare("
        DELETE FROM users
        WHERE id = ? AND is_admin = 0
    ");
    $stmt->execute([$userId]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('Failed to delete account.');
    }

    //// Commit all changes
    $pdo->commit();

    //// Clear session
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();

    echo json_encode([
        'success' => true,
        'message' => 'Account deleted successfully.'
    ]);

} catch (Exception $e) {

    //// Roll back everything if any operation fails
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete account. Please try again.'
    ]);
}

exit;

?>