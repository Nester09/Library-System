<?php
include 'db.php';

$currentDate = new DateTime();

//// Query to find confirmed borrowed books that were borrowed more than 10 days ago
$stmt = $pdo->prepare("
    SELECT bb.*, b.title AS book_title, u.username AS user_name 
    FROM borrowed_books bb
    JOIN books b ON bb.book_id = b.id
    JOIN users u ON bb.user_id = u.id
    WHERE bb.status = 'confirmed' 
    AND DATE_ADD(bb.borrow_date, INTERVAL 10 DAY) < NOW()
");
$stmt->execute();
$booksToReturn = $stmt->fetchAll(PDO::FETCH_ASSOC);

//// Check if there are any books to return
if (empty($booksToReturn)) {
    echo "No books are overdue for return at this time.\n";
} else {
    foreach ($booksToReturn as $borrowedBook) {
        $bookId = $borrowedBook['book_id'];
        $userId = $borrowedBook['user_id'];
        $bookTitle = $borrowedBook['book_title'];
        $userName = $borrowedBook['user_name'];

        //// Insert into returned_books with the current date as return date
        $returnStmt = $pdo->prepare("INSERT INTO returned_books (user_id, book_id, return_date) VALUES (?, ?, NOW())");
        if ($returnStmt->execute([$userId, $bookId])) {
            //// Delete from borrowed_books
            $deleteStmt = $pdo->prepare("DELETE FROM borrowed_books WHERE id = ?");
            if ($deleteStmt->execute([$borrowedBook['id']])) {
                //// Update available copies
                $updateStmt = $pdo->prepare("UPDATE books SET available_copies = available_copies + 1 WHERE id = ?");
                if ($updateStmt->execute([$bookId])) {
                    echo "Book ID: $bookId ('$bookTitle') has been returned automatically for User ID: $userId ('$userName').\n";
                } else {
                    echo "Failed to update available copies for Book ID: $bookId ('$bookTitle').\n";
                }
            } else {
                echo "Failed to delete borrowed record for Book ID: $bookId ('$bookTitle').\n";
            }
        } else {
            echo "Failed to record return for Book ID: $bookId ('$bookTitle').\n";
        }
    }
}
?>