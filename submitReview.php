<?php

session_start(); 

$host = 'localhost'; 
$db = 'system'; 
$user = 'root'; 
$pass = ''; 

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id']; 
} else {
    $_SESSION['message'] = 'User ID is not set in session.'; 
    header('Location: dashboard.php'); 
    exit;
}

$book_id = isset($_POST['book_id']) ? intval($_POST['book_id']) : 0;
$review_text = isset($_POST['review_text']) ? trim($_POST['review_text']) : '';
$rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;

//// Validate input
if ($book_id <= 0) {
    $_SESSION['message'] = 'Invalid book selection.'; 
    header('Location: dashboard.php'); 
    exit;
}
if (empty($review_text)) {
    $_SESSION['message'] = 'Review text cannot be empty.'; 
    header('Location: dashboard.php'); 
    exit;
}
if ($rating < 1 || $rating > 5) {
    $_SESSION['message'] = 'Rating must be between 1 and 5.'; 
    header('Location: dashboard.php'); 
    exit;
}

//// Prepare and bind statement to prevent SQL injection
$stmt = $conn->prepare("INSERT INTO reviews (book_id, review_text, rating, user_id) VALUES (?, ?, ?, ?)");
$stmt->bind_param("isii", $book_id, $review_text, $rating, $user_id); 

//// Execute statement and check for success
if ($stmt->execute()) {
    $_SESSION['message'] = 'Review submitted successfully!'; 
} else {
    $_SESSION['message'] = 'Failed to submit review: ' . $stmt->error; 
}

//// Close connections
$stmt->close();
$conn->close();

header('Location: dashboard.php'); 
exit;
?>