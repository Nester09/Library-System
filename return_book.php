<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo 'Please log in to return a book.';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $bookId = isset($_POST['book_id']) ? (int)$_POST['book_id'] : 0;
    $userId = (int)$_SESSION['user_id'];

    if ($bookId > 0) {

        $conn = new mysqli('localhost', 'root', '', 'system');

        if ($conn->connect_error) {
            http_response_code(500);
            die('Connection failed: ' . $conn->connect_error);
        }

        /*
         * Find the borrowing record belonging to the logged-in user.
         */
        $stmt = $conn->prepare("
            SELECT book_id, user_id, borrow_date
            FROM borrowed_books
            WHERE id = ? AND user_id = ?
            LIMIT 1
        ");

        $stmt->bind_param('ii', $bookId, $userId);
        $stmt->execute();

        $result = $stmt->get_result();
        $borrowedBook = $result->fetch_assoc();

        $stmt->close();

        if (!$borrowedBook) {
            echo 'Book return request could not be found.';
            $conn->close();
            exit;
        }

        $actualBookId = (int)$borrowedBook['book_id'];
        $borrowDate = $borrowedBook['borrow_date'];

        /*
         * Start a transaction so all return operations succeed together.
         */
        $conn->begin_transaction();

        try {

            /*
             * 1. Increase the number of available copies.
             */
            $stmt = $conn->prepare("
                UPDATE books
                SET available_copies = available_copies + 1
                WHERE id = ?
            ");

            $stmt->bind_param('i', $actualBookId);

            if (!$stmt->execute()) {
                throw new Exception('Failed to update available copies.');
            }

            $stmt->close();

            /*
             * 2. Record the returned book.
             */
            $stmt = $conn->prepare("
                INSERT INTO returned_books
                (book_id, user_id, borrow_date, return_date)
                VALUES (?, ?, ?, NOW())
            ");

            $stmt->bind_param('iis', $actualBookId, $userId, $borrowDate);

            if (!$stmt->execute()) {
                throw new Exception('Failed to record the returned book.');
            }

            $stmt->close();

            /*
             * 3. Remove the book from the active borrowed records.
             *
             * Notice that there is NO:
             * status = 'returned'
             */
            $stmt = $conn->prepare("
                DELETE FROM borrowed_books
                WHERE id = ? AND user_id = ?
            ");

            $stmt->bind_param('ii', $bookId, $userId);

            if (!$stmt->execute()) {
                throw new Exception('Failed to remove the borrowed record.');
            }

            $stmt->close();

            $conn->commit();

            echo 'Book returned successfully!';

        } catch (Exception $e) {

            $conn->rollback();

            http_response_code(500);
            echo 'Error: ' . $e->getMessage();
        }

        $conn->close();

    } else {
        echo 'Invalid book ID.';
    }
}
?>