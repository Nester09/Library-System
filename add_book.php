<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    //// Retrieve and sanitize input data
    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $copies = trim($_POST['copies']);

    //// Initialize an array to hold error messages
    $errors = [];

    //// Validate input data
    if (empty($title)) {
        $errors[] = 'Title is required.';
    }
    
    if (empty($author)) {
        $errors[] = 'Author is required.';
    }

    if (empty($copies)) {
        $errors[] = 'Number of copies is required.';
    } elseif (!is_numeric($copies) || $copies <= 0) {
        $errors[] = 'Copies must be a positive number.';
    }

    //// If there are validation errors, return them as a JSON response
    if (!empty($errors)) {
        echo json_encode(['status' => 'error', 'message' => implode(' ', $errors)]);
        exit; //// Exit if validation fails
    }

    //// Check if the book already exists in the database
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM books WHERE title = ? AND author = ?");
    $stmt->execute([$title, $author]);
    
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['status' => 'error', 'message' => 'The book already exists.']);
        exit; //// Exit if the book already exists
    }

    //// Insert book into the database
    $stmt = $pdo->prepare("INSERT INTO books (title, author, available_copies, total_copies) VALUES (?, ?, ?, ?)");
    
    if ($stmt->execute([$title, $author, $copies, $copies])) {
        echo json_encode(['status' => 'success', 'message' => 'Book added successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to add book.']);
    }
}
?>