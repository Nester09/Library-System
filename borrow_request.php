<?php 
include 'db.php';

//// Confirm a borrowed book
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'confirm') {
    $borrowId = $_POST['borrow_id'];

    //// Update status to confirmed
    $stmt = $pdo->prepare("UPDATE borrowed_books SET status = 'confirmed' WHERE id = ?");
    
    if ($stmt->execute([$borrowId])) {
        echo json_encode(['status' => 'success', 'message' => 'Borrow request confirmed.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to confirm borrow request.']);
    }
}

//// Deny a borrowed book
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'deny') {
    $borrowId = $_POST['borrow_id'];

    //// Start a transaction
    $pdo->beginTransaction();
    
    try {
        //// Fetch the book ID associated with the borrow request
        $stmt = $pdo->prepare("SELECT book_id FROM borrowed_books WHERE id = ?");
        $stmt->execute([$borrowId]);
        $bookId = $stmt->fetchColumn();

        if ($bookId) {
            //// Delete the entry from borrowed_books
            $stmtDelete = $pdo->prepare("DELETE FROM borrowed_books WHERE id = ?");
            if ($stmtDelete->execute([$borrowId])) {
                //// Update the available copies in the books table
                $stmtUpdateCopies = $pdo->prepare("UPDATE books SET available_copies = available_copies + 1 WHERE id = ?");
                if ($stmtUpdateCopies->execute([$bookId])) {
                    //// Commit the transaction
                    $pdo->commit();
                    echo json_encode(['status' => 'success', 'message' => 'Borrow request denied and book returned to inventory.']);
                } else {
                    throw new Exception('Failed to update available copies.');
                }
            } else {
                throw new Exception('Failed to delete borrow request.');
            }
        } else {
            throw new Exception('Borrow request not found.');
        }
    } catch (Exception $e) {
        //// Rollback the transaction on error
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
?>