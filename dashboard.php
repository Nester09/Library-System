<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

include 'db.php';

//// Fetch user data
$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

//// Fetch available books including available copies
$stmt = $pdo->query("SELECT title, author, id, available_copies FROM books");
$availableBooks = $stmt->fetchAll(PDO::FETCH_ASSOC);

//// Fetch user's borrowed books
$userId = $_SESSION['user_id'];

//// First, delete any borrowed books that are not in the books table
$stmt = $pdo->prepare("
    DELETE FROM borrowed_books 
    WHERE user_id = ? AND book_id NOT IN (SELECT id FROM books)
");
$stmt->execute([$userId]);

//// Then, fetch the updated list of borrowed books with due dates
$stmt = $pdo->prepare("
    SELECT bb.*, b.title, b.author, 
           DATE_ADD(bb.borrow_date, INTERVAL 10 DAY) AS due_date 
    FROM borrowed_books bb
    JOIN books b ON bb.book_id = b.id
    WHERE bb.user_id = ? AND bb.status = 'confirmed'
");
$stmt->execute([$userId]);
$borrowedBooks = $stmt->fetchAll(PDO::FETCH_ASSOC);

//// Fetch user's returned books with book details
$stmt = $pdo->prepare("
    SELECT rb.*, b.title, b.author 
    FROM returned_books rb
    JOIN books b ON rb.book_id = b.id
    WHERE rb.user_id = ?
");
$stmt->execute([$userId]);
$returnedBooks = $stmt->fetchAll(PDO::FETCH_ASSOC);

//// Handle book search
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'search') {
    $searchTerm = $_POST['search_term'];

    //// Search for books based on the search term
    $stmt = $pdo->prepare("SELECT * FROM books WHERE title LIKE ? OR author LIKE ?");
    $stmt->execute(['%' . $searchTerm . '%', '%' . $searchTerm . '%']);
    $searchResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

    //// Return the search results as JSON
    echo json_encode($searchResults);
    exit;
}

//// Fetch all books for review submission
$stmt = $pdo->query("SELECT * FROM books");
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);

//// Handle review submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userId = $_SESSION['user_id'];
    $bookId = $_POST['book_id'];
    $reviewText = $_POST['review_text'];
    $rating = $_POST['rating'];

    //// Insert review into the database
    $stmt = $pdo->prepare("INSERT INTO reviews (user_id, book_id, review_text, rating) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$userId, $bookId, $reviewText, $rating])) {
        echo json_encode(['status' => 'success', 'message' => 'Review submitted successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to submit review.']);
    }
    exit;
}

//// Fetch reviews and calculate average rating for each book
$bookRatings = [];
$stmt = $pdo->query("SELECT book_id, AVG(rating) as average_rating FROM reviews GROUP BY book_id");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $bookRatings[$row['book_id']] = $row['average_rating'];
}    

//// Fetch all admins
$stmt = $pdo->query("SELECT * FROM users WHERE is_admin = 1");
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/bootstrap/css/v4.5.2/bootstrap.min.css">
    <link rel="stylesheet" href="assets/font-awesome/css/all.min.css">
    <link rel="stylesheet" href="assets/bootstrap/css/v5.3.0-alpha1/bootstrap.min.css">
    <link rel="stylesheet" href="assets/bootstrap-icons-1.5.0/bootstrap-icons.css">
    <link rel="stylesheet" href="dashboard_styles.css">
    <title>👤User Dashboard</title>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light sticky-top">
        <a class="navbar-brand" href="dashboard.php" onclick="window.location.reload()" >Hello 👋 <?php echo htmlspecialchars($user['username']); ?>, Welcome!</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link" href="#" id="bell-icon" data-toggle="modal" data-target="#notificationModal">
                        <i class="bi bi-bell"></i> <span class="badge badge-danger" id="bell-badge">0</span> Notifications
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-toggle="modal" data-target="#settingsModal">
                        <i class="bi bi-gear"></i> Account Settings
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-toggle="modal" data-target="#adminsModal">
                        <i class="bi bi-shield-lock"></i> Admins
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container mt-5">
        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" data-toggle="tab" href="#home"><i class="bi bi-house"></i> Home</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#available-books"><i class="bi bi-book"></i> Available Books</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#search-books"><i class="bi bi-search"></i> Search Books</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#borrowed-books"><i class="bi bi-file-earmark-check"></i> Borrowed Books</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#returned-books"><i class="bi bi-file-earmark-arrow-up"></i> Returned Books</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#submit-review"><i class="bi bi-pencil-square"></i> Submit Review</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#reviews"><i class="bi bi-star"></i> Reviews</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#library-history"><i class="bi bi-clock-history"></i> Library History</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#privacy-policy"><i class="bi bi-shield-lock"></i> Privacy Policy</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#help-center"><i class="bi bi-question-circle"></i> Help Center</a>
            </li>
        </ul>

        <div class="tab-content mt-3">
            <!-- Home Section -->
            <div class="tab-pane fade show active" id="home" role="tabpanel">
                <h2>Welcome to our Library System</h2>
                <div id="home-content">
                    <!--<p>Your go-to platform for borrowing and reviewing books. (This Content is Editable by the admins)</p>-->
                </div>
            </div>

            <!-- Notification Modal -->
            <div class="modal fade" id="notificationModal" tabindex="-1" role="dialog" aria-labelledby="notificationModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="notificationModalLabel">Your Notifications</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <ul class="list-group" id="notifications-list">
                                <!-- Notifications to be populated here -->
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Available Books Section -->
            <div class="tab-pane fade" id="available-books" role="tabpanel">
                <h2>Available Books</h2>
                <ul class="list-group">
                    <?php foreach ($availableBooks as $book): ?>
                        <li class="list-group-item">
                            <?php echo htmlspecialchars($book['title']) . ' by ' . htmlspecialchars($book['author']); ?>
                            <span class="badge badge-secondary mr-2"><?php echo htmlspecialchars($book['available_copies']); ?> copies available</span>
                            <button class="btn btn-info btn-sm float-right" onclick="openReviewModal(<?php echo $book['id']; ?>, '<?php echo htmlspecialchars($book['title']); ?>')"><i class="fas fa-star"></i> Review</button>
                            <button class="btn btn-success btn-sm float-right mr-2" onclick="borrowBook(<?php echo $book['id']; ?>)"><i class="fas fa-book-open"></i> Borrow</button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Review Modal -->
            <div class="modal fade" id="reviewModal" tabindex="-1" role="dialog" aria-labelledby="reviewModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="reviewModalLabel">Review Book</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form id="review-form">
                                <input type="hidden" name="book_id" id="modal-book-id">
                                <div class="form-group">
                                    <label for="review_text">Your Review</label>
                                    <textarea name="review_text" id="review_text" class="form-control" rows="4" required></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="rating">Rating (1-5)</label>
                                    <input type="number" name="rating" id="rating" class="form-control" min="1" max="5" required>
                                </div>
                                <button type="button" class="btn btn-primary" onclick="submitReview()"><i class="fas fa-paper-plane"></i> Submit Review</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search Books Section -->
            <div class="tab-pane fade" id="search-books" role="tabpanel">
                <h2>Search Books</h2>
                <form id="search-form" onsubmit="return false;">
                    <input type="text" id="search-input" class="form-control" placeholder="Search books...">
                    <button class="btn btn-primary mt-2" onclick="searchBooks()"><i class="fas fa-search"></i> Search</button>
                </form>
                <div id="search-results" class="mt-4"></div>
            </div>

           <!-- Borrowed Books Section -->
            <div class="tab-pane fade" id="borrowed-books" role="tabpanel">
                <h2>Borrowed Books</h2>
                
                <ul class="list-group">
                    <?php if (empty($borrowedBooks)): ?>
                        <li class="list-group-item">You have not borrowed any books.</li>
                    <?php else: ?>
                        <?php foreach ($borrowedBooks as $book): ?>
                            <li class="list-group-item" id="book-<?php echo htmlspecialchars($book['id']); ?>">
                                <?php 
                                    echo htmlspecialchars($book['title']) . " - Status: " . htmlspecialchars($book['status']) . " - Borrowed on: " . htmlspecialchars($book['borrow_date']);
                                ?>
                                <span class="text-muted small" style="background:#f76c6c; font-weight:600; padding:5px; border-radius:10px;" id="countdown-<?php echo htmlspecialchars($book['id']); ?>"></span>
                                <button class="btn btn-danger btn-sm float-right ml-2" onclick="returnBook(<?php echo htmlspecialchars($book['id']); ?>)">
                                    <i class="fas fa-arrow-left"></i> Return
                                </button>
                            </li>

                            <script>
                                //// Get the due date from PHP and convert it to a JavaScript Date object
                                var dueDate = new Date("<?php echo htmlspecialchars($book['due_date']); ?>").getTime();
                                var countdownElement = document.getElementById("countdown-<?php echo htmlspecialchars($book['id']); ?>");

                                //// Update the countdown every second
                                var countdownTimer = setInterval(function() {
                                    var now = new Date().getTime();
                                    var distance = dueDate - now;

                                    //// Time calculations for days, hours, minutes, and seconds
                                    var days = Math.floor(distance / (1000 * 60 * 60 * 24));
                                    var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                                    var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                                    var seconds = Math.floor((distance % (1000 * 60)) / 1000);

                                    //// Display the remaining time in the countdown element
                                    countdownElement.innerHTML = days + "d " + hours + "h " + minutes + "m " + seconds + "s ";

                                    //// If the countdown is over, stop the timer and show "Due!" text
                                    if (distance < 0) {
                                        clearInterval(countdownTimer);
                                        countdownElement.innerHTML = "Due!";

                                        //// Automatically return the book when it's due
                                        returnBook(<?php echo htmlspecialchars($book['id']); ?>); 
                                    }
                                }, 1000);
                            </script>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>


            <!-- Returned Books Section -->
            <div class="tab-pane fade" id="returned-books" role="tabpanel">
                <h2>Returned Books</h2>
                <ul class="list-group">
                    <?php if (empty($returnedBooks)): ?>
                        <li class="list-group-item">You have not returned any books.</li>
                    <?php else: ?>
                        <?php foreach ($returnedBooks as $book): ?>
                            <li class="list-group-item">
                                <?php echo htmlspecialchars($book['title']) . ' by ' . htmlspecialchars($book['author']) . ' - Returned on: ' . htmlspecialchars($book['return_date']); ?>
                                <button class="btn btn-danger btn-sm float-right" onclick="deleteReturnedBook(<?php echo $book['id']; ?>)"><i class="fas fa-trash-alt"></i> Delete</button>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Submit Review Section -->
            <div class="tab-pane fade" id="submit-review" role="tabpanel">
            <h2>Submit a Review</h2>
            <form id="review-form" action="submitReview.php" method="POST">
                <select name="book_id" class="form-control" required id="review-book-select">
                    <option value="">Select a book</option>
                    <?php foreach ($books as $book): ?>
                        <option value="<?php echo $book['id']; ?>"><?php echo htmlspecialchars($book['title']); ?></option>
                    <?php endforeach; ?>
                </select>
                <textarea name="review_text" class="form-control" id="review_text" placeholder="Write your review here..." required></textarea>
                <input type="number" name="rating" id="rating" min="1" max="5" class="form-control" placeholder="Rate the book (1-5)" required>
                <button type="submit" class="btn btn-primary mt-2"><i class="fas fa-paper-plane"></i> Submit Review</button>
            </form>

            <!-- Responses Section -->
            <h2 class="mt-4">Admin Responses</h2>
            <div id="responses-section">
                <?php
                //// Fetch user responses 
                $userId = $_SESSION['user_id'];
                $stmt = $pdo->prepare("SELECT r.*, b.title AS book_title FROM reviews r JOIN books b ON r.book_id = b.id WHERE r.user_id = ?");
                $stmt->execute([$userId]);
                $responses = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if ($responses): ?>
                    <ul class="list-group mt-3">
                        <?php foreach ($responses as $response): ?>
                            <li class="list-group-item">
                                <strong>Book: <?php echo htmlspecialchars($response['book_title']); ?> (ID: <?php echo htmlspecialchars($response['book_id']); ?>)</strong><br>
                                Rating: <?php echo htmlspecialchars($response['rating']); ?><br>
                                Review: <?php echo htmlspecialchars($response['review_text']); ?><br>
                                Response: <?php echo htmlspecialchars($response['response']) ?: 'No response yet.'; ?><br>
                                <button class="btn btn-danger btn-sm mt-2" onclick="deleteResponse(<?php echo $response['id']; ?>)">    <i class="fas fa-trash-alt"></i> Delete Response</button>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No reviews submitted yet.</p>
                <?php endif; ?>
            </div>
        </div>
            <script>
                function submitYourReview() {
                    event.preventDefault();

                    const formData = new FormData(document.getElementById('review-form'));

                    fetch('submitReview.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Review submitted successfully!');
                            document.getElementById('review-form').reset();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while submitting your review. Please try again.');
                    });

                    return false; 
                }

                function deleteResponse(responseId) {
                    if (confirm('Are you sure you want to delete this response?')) {
                        fetch('delete_response.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ response_id: responseId })
                        })
                        .then(response => response.json())
                        .then(data => {
                            alert(data.message);
                            if (data.status === 'success') {
                                location.reload(); 
                            }
                        })
                        .catch(error => {
                            alert("🚫An error occurred: " + error.message);
                        });
                    }
                }
            </script>

            <!-- Reviews Section -->
            <div class="tab-pane fade" id="reviews" role="tabpanel">
                <h2>Reviews</h2>
                <ul class="list-group">
                    <?php foreach ($books as $book): ?>
                        <li class="list-group-item">
                            <strong><?php echo htmlspecialchars($book['title']); ?></strong> - Average Rating: <?php echo isset($bookRatings[$book['id']]) ? number_format($bookRatings[$book['id']], 1) : 'No ratings yet'; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Library History Section -->
            <div class="tab-pane fade" id="library-history" role="tabpanel">
                <h2>Library History</h2>
                <div id="history-content">
                    <!--<p>This section will contain the history of the library.(Editable by the Admin)</p>-->
                </div>
            </div>

            <!-- Privacy Policy Section -->
            <div class="tab-pane fade" id="privacy-policy" role="tabpanel">
                <h2>Privacy Policy</h2>
                <div id="policy-content">
                    <!--<p>This section will contain the privacy policy of the system.(Editable by the admin)</p>-->
                </div>
            </div>

            <!-- Help Center Section -->
            <div class="tab-pane fade" id="help-center" role="tabpanel">
                <h2>Help Center</h2>
                <h3>Frequently Asked Questions</h3>
                <p class="query">Q: What is an Online Library System?</p>
                <p>A: An Online Library System is a digital platform that provides access to a wide range of books, journals, and other educational resources. Users can borrow, read, and review materials from anywhere with an internet connection.</p>
                <p class="query">Q: How do I create an account?</p>
                <p>A: To create an account,Navigate to the homepage and Fill in the required information, including your username, email address, and password and then click on the "Register" button.You will receive a confirmation message upon successful registration</p>
                <p class="query">Q: Is there a membership fee?</p>
                <p>A: No, our services are free.</p>                
                <p class="query">Q: How do I borrow a book?</p>
                <p>A: Once you are logged in, browse the available books and select one you wish to borrow or Navigate to search for a book you wish to borrow. Click the "Borrow" button, and confirm the prompts to complete the process. The book will be added to your list of borrowed books.</p>
                <p class="query">Q: Can I return a book early?</p>
                <p>A: Yes, you can return a borrowed book at any time before the due date which is after six days since borrowing. Simply navigate to the borrowed books section and click the "Return" button for a confirmed book.</p>
                <p class="query">Q: Can I leave reviews for books?</p>
                <p>A: Yes, registered users can submit reviews for books they have read. Navigate to the Available book's page, and you will find an option to write a review.</p>
                <h3>Contact Us</h3>
                <p>If you have further questions, please reach out to our admin via <a href="#" onclick="window.location.reload()">email</a>.</p>
            </div>
        </div>

        <!-- Account Modal -->
        <div class="modal fade" id="settingsModal" tabindex="-1" aria-labelledby="settingsModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="settingsModalLabel">Account Settings</h5>
                        <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <ul class="nav nav-tabs" id="myTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <a class="nav-link active" id="edit-tab" data-bs-toggle="tab" href="#edit" role="tab">Edit Account</a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link" id="delete-tab" data-bs-toggle="tab" href="#delete" role="tab">Delete Account</a>
                            </li>
                        </ul>
                        <div class="tab-content mt-2">
                            <!-- Edit Account Tab -->
                            <div class="tab-pane fade show active" id="edit" role="tabpanel">
                                <form id="editForm">
                                    <input type="text" id="username" placeholder="Username" maxlength="15" required class="form-control mb-2" value="<?php echo htmlspecialchars($user['username']); ?>">
                                    <input type="email" id="email" placeholder="Email" required class="form-control mb-2" value="<?php echo isset($user['email']) ? htmlspecialchars($user['email']) : ''; ?>">

                                    <div class="input-group mb-2">
                                        <input type="password" id="currentPassword" placeholder="Current Password" required class="form-control" minlength="6">
                                        <div class="input-group-append">
                                            <span toggle="#currentPassword" class="fa fa-eye field-icon toggle-password input-group-text"></span>
                                        </div>
                                    </div>

                                    <div class="input-group mb-2">
                                        <input type="password" id="newPassword" placeholder="New Password" required class="form-control" minlength="6">
                                        <div class="input-group-append">
                                            <span toggle="#newPassword" class="fa fa-eye field-icon toggle-password input-group-text"></span>
                                        </div>
                                    </div>

                                    <div class="input-group mb-2">
                                        <input type="password" id="confirmPassword" placeholder="Confirm New Password" required class="form-control" minlength="6">
                                        <div class="input-group-append">
                                            <span toggle="#confirmPassword" class="fa fa-eye field-icon toggle-password input-group-text"></span>
                                        </div>
                                    </div>

                                    <div id="errorMessage" class="text-danger"></div>
                                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save Changes</button>
                                </form>
                            </div>

                            <!-- Delete Account Tab -->
                            <div class="tab-pane fade" id="delete" role="tabpanel">
                                <p>Are you sure you want to delete your account?</p>
                                <button id="deleteAccountBtn" class="btn btn-danger"><i class="fas fa-trash-alt"></i> Delete Account</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- Admins Modal -->
        <div class="modal fade" id="adminsModal" tabindex="-1" role="dialog" aria-labelledby="adminsModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="adminsModalLabel">Admins</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <ul class="list-group">
                            <?php foreach ($admins as $admin): ?>
                                <li class="list-group-item"><?php echo htmlspecialchars($admin['username']); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="assets/jquery/jquery-3.5.1.slim.min.js"></script>
    <script src="assets/popperjs/v2.9.3/popper.min.js"></script>
    <script src="assets/bootstrap/js/v4.5.2/bootstrap.bundle.min.js"></script>
    <script src="assets/popperjs/v2.5.4/popper.min.js"></script>
    <script src="assets/popperjs/v2.10.2/popper.min.js"></script>
    <script src="assets/bootstrap/js/v5.3.0-alpha1/bootstrap.bundle.min.js"></script>
    <script src="assets/bootstrap/js/v4.5.2/bootstrap.min.js"></script>
    <script>
         //// Set the active tab based on localStorage
         document.addEventListener('DOMContentLoaded', function() {
            const activeTab = localStorage.getItem('activeTab') || 'home'; 
            document.querySelector(`.nav-link[href="#${activeTab}"]`).click();
        });

        //// Store the active tab in localStorage
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function() {
                localStorage.setItem('activeTab', this.getAttribute('href').substring(1));
            });
        });

        function loadUserNotifications() {
            fetch('getNotificationsForUser.php')
                .then(response => response.json())
                .then(data => {
                    const notifications = data.notifications;
                    const bellBadge = document.getElementById('bell-badge');
                    bellBadge.textContent = notifications.length > 0 ? notifications.length : '0';

                    //// Add notifications to the modal
                    const notificationsList = document.getElementById('notifications-list');
                    notificationsList.innerHTML = ''; 

                    if (notifications.length === 0) {
                        notificationsList.innerHTML = '<li>No notifications available.</li>';
                    } else {
                        let currentDate = '';
                        notifications.forEach(notification => {
                            let notificationDate = new Date(notification.sent_at);
                            let notificationDateString = notificationDate.toLocaleDateString();
                            if (currentDate !== notificationDateString) {
                                currentDate = notificationDateString;
                                let dateHeader = document.createElement('li');
                                dateHeader.classList.add('list-group-item');
                                dateHeader.innerHTML = `<strong>${currentDate}</strong>`;
                                notificationsList.appendChild(dateHeader);
                            }
                            let listItem = document.createElement('li');
                            listItem.classList.add('list-group-item');
                            listItem.innerHTML = `
                                <strong>${notification.message}</strong>
                                <button class="btn btn-danger btn-sm" onclick="deleteNotification(${notification.id})">Delete</button>
                            `;
                            notificationsList.appendChild(listItem);
                        });
                    }
                })
                .catch(error => console.error('Error fetching notifications:', error));
        }

        //// Delete notification locally
        function deleteNotification(id) {
            fetch('deleteNotificationForUser.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ notification_id: id })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    loadUserNotifications();
                }
            })
            .catch(error => console.error('Error deleting notification:', error));
        }

        //// Load notifications when the bell is clicked
        document.getElementById('bell-icon').addEventListener('click', function() {
            loadUserNotifications();
        });

        //// Load notifications on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadUserNotifications(); 
        });

        function searchBooks() {
            const searchTerm = document.getElementById('search-input').value;

            if (!searchTerm.trim()) {
                alert("Please enter a search term.");
                return;
            }

            const formData = new FormData();
            formData.append('action', 'search');
            formData.append('search_term', searchTerm);

            fetch('dashboard.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const resultsDiv = document.getElementById('search-results');
                resultsDiv.innerHTML = '';
                if (data.length > 0) {
                    data.forEach(book => {
                        resultsDiv.innerHTML += `<div>${book.title} by ${book.author} <button class="btn btn-success btn-sm" onclick="borrowBook(${book.id})"><i class="fas fa-book-open"></i> Borrow</button></div>`;
                    });
                } else {
                    resultsDiv.innerHTML = '<div>No books 📚 with such details found.</div>';
                }
            });
        }

        function borrowBook(bookId) {
            const formData = new FormData();
            formData.append('action', 'borrow');
            formData.append('book_id', bookId);

            fetch('book_management.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.status === 'success') {
                    location.reload(); 
                }
            });
        }

        function returnBook(bookId) {
            var userConfirmed = confirm("Are you sure you want to return this book?");
            
            if (userConfirmed) {

                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'return_book.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                
                //// Send the book ID to the server
                xhr.send('book_id=' + bookId);

                xhr.onload = function() {
                    if (xhr.status == 200) {
                        var bookElement = document.getElementById('book-' + bookId);
                        if (bookElement) {
                            bookElement.innerHTML = 'This book has been returned.';
                            var returnButton = bookElement.querySelector('button');
                            if (returnButton) {
                                returnButton.style.display = 'none';
                            }
                        }
                    } else {
                        alert('Failed to return the book. Please try again.');
                    }
                };

                xhr.onerror = function() {
                    alert('Error occurred while returning the book. Please try again.');
                };
            } else {
                console.log('User canceled the return action.');
            }
        }

        //// Function to handle book deletion with confirmation
        function deleteReturnedBook(bookId) {
            if (confirm("Are you sure you want to delete this returned book?")) {
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'delete_returned_book.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        var response = JSON.parse(xhr.responseText);

                        if (response.success) {
                            alert('The book has been successfully deleted.');
                            location.reload();  
                        } else {
                            alert('There was an error deleting the book. Please try again.');
                        }
                    }
                };
                
                //// Send the request with the book ID
                xhr.send('id=' + bookId);
            }
        }

        function openReviewModal(bookId, bookTitle) {
            document.getElementById('modal-book-id').value = bookId;
            document.getElementById('reviewModalLabel').innerText = 'Review ' + bookTitle;
            document.getElementById('review_text').value = '';
            document.getElementById('rating').value = ''; 
            $('#reviewModal').modal('show');
        }

        function submitReview() {
            const reviewText = document.getElementById('review_text').value.trim();
            const rating = document.getElementById('rating').value;

            //// Validate the input fields
            if (!reviewText) {
                alert("Please enter your review.");
                return;
            }
            
            if (!rating || rating < 1 || rating > 5) {
                alert("Please enter a rating between 1 and 5.");
                return;
            }

            const form = document.getElementById('review-form');
            const formData = new FormData(form);
            formData.append('action', 'submit_review');

            fetch('dashboard.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.status === 'success') {
                    $('#reviewModal').modal('hide'); 
                    location.reload(); 
                }
            });
        }

        //// Toggle password visibility
        document.querySelectorAll(".toggle-password").forEach(item => {
            item.addEventListener("click", function() {
                const input = document.querySelector(this.getAttribute("toggle"));
                const type = input.getAttribute("type") === "password" ? "text" : "password";
                input.setAttribute("type", type);
                this.classList.toggle("fa-eye-slash");
            });
        });

        //// Handle form submission for editing account
        document.getElementById('editForm').addEventListener('submit', function(event) {
            event.preventDefault();

            const currentPassword = document.getElementById('currentPassword').value;
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;

            if (newPassword !== confirmPassword) {
                document.getElementById('errorMessage').innerText = "New password and confirmation do not match!";
                return;
            }

            fetch('update_account.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    username: document.getElementById('username').value,
                    email: document.getElementById('email').value,
                    currentPassword: currentPassword,
                    newPassword: newPassword
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert("Account updated successfully!");
                    location.reload(); 
                } else {
                    document.getElementById('errorMessage').innerText = data.message;
                }
            })
            .catch(error => console.error('Error:', error));
        });

        //// Handle account deletion
        document.getElementById('deleteAccountBtn').addEventListener('click', function() {
            if (confirm("Are you sure you want to delete your account? This action cannot be undone.")) {

                fetch('delete_account.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ action: 'delete' })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert("Account deleted successfully!");
                        window.location.href = "logout.php"; 
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        });

        function loadLibrarySettings() {
            fetch('get_library_settings.php')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('home-content').innerHTML = data.welcome_message || '<p>No message set.</p>';
                    document.getElementById('history-content').innerHTML = data.library_history || '<p>No history available.</p>';
                    document.getElementById('policy-content').innerHTML = data.privacy_policy || '<p>No policy available.</p>';
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while loading settings.');
                });
        }

        //// Call loadLibrarySettings when the page loads
        document.addEventListener('DOMContentLoaded', loadLibrarySettings);

    </script>
</body>
</html>
