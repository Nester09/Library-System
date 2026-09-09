<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

include 'db.php';

//// Borrow a book
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'borrow') {
    $bookId = $_POST['book_id'];
    $userId = $_SESSION['user_id'];

    //// Check if the book is available
    $stmt = $pdo->prepare("SELECT available_copies FROM books WHERE id = ?");
    $stmt->execute([$bookId]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($book && $book['available_copies'] > 0) {
        //// Insert into borrowed_books with pending status and current date as borrow date
        $stmt = $pdo->prepare("INSERT INTO borrowed_books (user_id, book_id, status, borrow_date) VALUES (?, ?, 'pending', NOW())");
        if ($stmt->execute([$userId, $bookId])) {
            //// Update available copies
            $stmt = $pdo->prepare("UPDATE books SET available_copies = available_copies - 1 WHERE id = ?");
            $stmt->execute([$bookId]);
            echo json_encode(['status' => 'success', 'message' => 'Book successfully requested. Once confirmed you will be notified by email when the book is issued to your account.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to borrow the book.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No copies available.']);
    }
    exit;
}

//// Return a book
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'return') {
    $bookId = $_POST['book_id'];
    $userId = $_SESSION['user_id'];

    //// Check if the book was borrowed by the user and is confirmed or pending
    $stmt = $pdo->prepare("SELECT * FROM borrowed_books WHERE user_id = ? AND book_id = ? AND (status = 'confirmed' OR status = 'pending')");
    $stmt->execute([$userId, $bookId]);
    $borrowedBook = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($borrowedBook) {
        //// Insert into returned_books with the current date as return date
        $stmt = $pdo->prepare("INSERT INTO returned_books (user_id, book_id, return_date) VALUES (?, ?, NOW())");
        if ($stmt->execute([$userId, $bookId])) {
            //// Delete from borrowed_books
            $stmt = $pdo->prepare("DELETE FROM borrowed_books WHERE id = ?");
            if ($stmt->execute([$borrowedBook['id']])) {
                //// Update available copies
                $stmt = $pdo->prepare("UPDATE books SET available_copies = available_copies + 1 WHERE id = ?");
                if ($stmt->execute([$bookId])) {
                    echo json_encode(['status' => 'success', 'message' => 'Book returned successfully.']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to update available copies.']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to delete borrowed record.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to record return.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'You have not borrowed this book or it is not confirmed.']);
    }
    exit;
}
?>