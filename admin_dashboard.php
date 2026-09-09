<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

include 'db.php';

//// Fetch all books, users, borrowed books, reviews, and admin details
$booksStmt = $pdo->query("SELECT id, title, author, total_copies, available_copies FROM books");
$books = $booksStmt->fetchAll(PDO::FETCH_ASSOC);

$usersStmt = $pdo->query("SELECT * FROM users");
$users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);

$borrowedBooksStmt = $pdo->query("SELECT bb.*, u.username, b.title FROM borrowed_books bb JOIN users u ON bb.user_id = u.id JOIN books b ON bb.book_id = b.id");
$borrowedBooks = $borrowedBooksStmt->fetchAll(PDO::FETCH_ASSOC);

$reviewsStmt = $pdo->query("SELECT r.*, u.username FROM reviews r JOIN users u ON r.user_id = u.id ORDER BY r.created_at DESC");
$reviews = $reviewsStmt->fetchAll(PDO::FETCH_ASSOC);

$adminId = $_SESSION['admin_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$adminId]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

//// Function to automatically generate responses based on the review's content
function generateResponse($reviewText) {
    
    $reviewText = strtolower($reviewText);
    
    //// Simple rules for automatic responses
    if (strpos($reviewText, 'love') !== false || strpos($reviewText, 'great') !== false || strpos($reviewText, 'good') !== false || strpos($reviewText, 'excellent') !== false) {
        return "Thank you for the positive feedback! We’re glad you enjoyed the book.";
    } elseif (strpos($reviewText, 'hate') !== false || strpos($reviewText, 'bad') !== false || strpos($reviewText, 'dissatisfied') !== false || strpos($reviewText, 'poor') !== false) {
        return "We are sorry to hear you didn’t enjoy the book. We appreciate your feedback and will work on improving it.";
    } elseif (strpos($reviewText, 'okay') !== false || strpos($reviewText, 'neutral') !== false) {
        return "Thank you for your feedback. We are constantly working to improve.";
    } else {
        return "Thank you for your review! We value all feedback.";
    }
}

//// Handle other actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    //// Handle account update
    if (isset($_POST['action']) && $_POST['action'] === 'update_account') {
        $username = $_POST['username'];
        $email = $_POST['email'];
        
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$adminId]);
        $currentPassword = $stmt->fetchColumn();

        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        } else {
            $password = $currentPassword;
        }

        //// Prepare and execute the update statement
        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?");
        if ($stmt->execute([$username, $email, $password, $adminId])) {
            echo json_encode(['status' => 'success', 'message' => 'Account updated successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update account.']);
        }
        exit;
    }

    //// Handle account deletion
    if (isset($_POST['action']) && $_POST['action'] === 'delete_account') {

        try {

            //// Start transaction
            $pdo->beginTransaction();

            //// Lock the current admin account and confirm it still exists
            $stmt = $pdo->prepare("
                SELECT id, is_admin
                FROM users
                WHERE id = ? AND is_admin = 1
                FOR UPDATE
            ");
            $stmt->execute([$adminId]);
            $currentAdmin = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$currentAdmin) {
                throw new Exception('Admin account not found.');
            }

            //// Count all other administrators
            $stmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM users
                WHERE is_admin = 1 AND id != ?
            ");
            $stmt->execute([$adminId]);
            $otherAdmins = (int) $stmt->fetchColumn();

            //// Prevent deletion if this is the last admin
            if ($otherAdmins < 1) {
                throw new Exception(
                    'You cannot delete your account because you are the only administrator. Please create another admin account first.'
                );
            }

            /*
            * In the current database structure, admin actions such as:
            * - adding books
            * - managing library settings
            * - responding to reviews
            *
            * are not directly linked to an admin_id foreign key.
            *
            * Therefore, deleting the admin account does not leave orphaned
            * foreign-key records in the current schema.
            */

            //// Delete any notifications belonging to this admin
            $stmt = $pdo->prepare("
                DELETE FROM notification
                WHERE user_id = ?
            ");
            $stmt->execute([$adminId]);

            //// Delete returned book records if any exist for this admin
            $stmt = $pdo->prepare("
                DELETE FROM returned_books
                WHERE user_id = ?
            ");
            $stmt->execute([$adminId]);

            /*
            * Safety check:
            * An admin normally should not borrow books, but if records exist,
            * restore all active borrowed/requested books before deleting.
            */

            $stmt = $pdo->prepare("
                SELECT id, book_id
                FROM borrowed_books
                WHERE user_id = ?
                FOR UPDATE
            ");
            $stmt->execute([$adminId]);
            $borrowedBooks = $stmt->fetchAll(PDO::FETCH_ASSOC);

            //// Restore book copies if the admin has active borrow records
            foreach ($borrowedBooks as $borrowedBook) {

                $bookId = (int) $borrowedBook['book_id'];

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

            //// Delete active borrowing records
            $stmt = $pdo->prepare("
                DELETE FROM borrowed_books
                WHERE user_id = ?
            ");
            $stmt->execute([$adminId]);

            //// Delete the administrator
            //// Associated reviews are protected by ON DELETE CASCADE
            $stmt = $pdo->prepare("
                DELETE FROM users
                WHERE id = ? AND is_admin = 1
            ");
            $stmt->execute([$adminId]);

            if ($stmt->rowCount() === 0) {
                throw new Exception('Failed to delete administrator account.');
            }

            //// Commit transaction
            $pdo->commit();

            //// Clear admin session
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
                'status' => 'success',
                'message' => 'Administrator account deleted successfully.'
            ]);

        } catch (Exception $e) {

            //// Roll back all changes if anything fails
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }

        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/bootstrap/css/v4.5.2/bootstrap.min.css">
    <link rel="stylesheet" href="assets/bootstrap/css/v5.1.3/bootstrap.min.css">
    <link rel="stylesheet" href="assets/bootstrap-icons-1.5.0/bootstrap-icons.css">
    <link rel="stylesheet" href="dashboard_styles.css">
    <title>👥Admin Dashboard</title>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <a class="navbar-brand" onclick="window.location.reload()" href="admin_dashboard.php">Admin Dashboard</a>
        <div class="nav bar-nav ml-auto" id="one">
            <a href="#" class="nav-link" data-toggle="modal" data-target="#userModal"><i class="bi bi-person"></i> Users</a>

            <a href="#" class="nav-link" data-toggle="modal" data-target="#accountModal"><i class="bi bi-gear"></i> Account</a>

            <a href="logout.php" class="nav-link"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </div>
    </nav>

    <!-- User Modal -->
    <div class="modal fade" id="userModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Manage Users</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs" id="userTab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="all-users-tab" data-toggle="tab" href="#all-users" role="tab">All Users</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="add-user-tab" data-toggle="tab" href="#add-user" role="tab">Add User</a>
                        </li>
                    </ul>
                    <div class="tab-content mt-2">
                        <div class="tab-pane fade show active" id="all-users" role="tabpanel">
                        <input type="text" id="search-user" class="form-control" placeholder="Search Users" onkeyup="searchUsers()">
                        <ul class="list-group mt-2" id="user-list">
                                <?php foreach ($users as $user): ?>
                                    <li class="list-group-item">
                                        <?php echo htmlspecialchars($user['username']); ?>
                                        <button class="btn btn-danger btn-sm float-right" onclick="deleteUser(<?php echo $user['id']; ?>)">Delete</button>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="tab-pane fade" id="add-user" role="tabpanel">
                            <input type="text" id="new-username" class="form-control mt-2" placeholder="Username" required>
                            <input type="email" id="new-email" class="form-control mt-2" placeholder="Email" required>
                            <input type="password" id="new-password" class="form-control mt-2" placeholder="Password" required>
                            <button class="btn btn-primary mt-2" onclick="addUser()"><i class="bi bi-plus-circle"></i> Add User</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Account Modal -->
    <div class="modal fade" id="accountModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Account Settings</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs" id="accountTab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="edit-account-tab" data-toggle="tab" href="#edit-account" role="tab"><i class="bi bi-pencil"></i> Edit Account</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="delete-account-tab" data-toggle="tab" href="#delete-account" role="tab"><i class="bi bi-trash"></i> Delete Account</a>
                        </li>
                    </ul>
                    <div class="tab-content mt-2">
                        <div class="tab-pane fade show active" id="edit-account" role="tabpanel">
                            <input type="text" id="edit-username" class="form-control mt-2" value="<?php echo htmlspecialchars($admin['username']); ?>" required>
                            <input type="email" id="edit-email" class="form-control mt-2" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                            <input type="password" id="edit-password" class="form-control mt-2" placeholder="New Password (optional)">
                            <button class="btn btn-primary mt-2" onclick="updateAccount()"><i class="bi bi-save"></i> Save Changes</button>
                        </div>
                        <div class="tab-pane fade" id="delete-account" role="tabpanel">
                            <p>Are you sure you want to delete your account permanently?</p>
                            <button class="btn btn-danger mt-2" onclick="deleteAccount()"><i class="bi bi-trash"></i> Delete Account</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-5">
        <ul class="nav nav-tabs" id="dashboard-tabs">
            <li class="nav-item">
                <a class="nav-link active" data-toggle="tab" href="#add-book"><i class="bi bi-plus-circle"></i> Add New Book</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#all-books"><i class="bi bi-book"></i> All Books</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#borrow-requests"><i class="bi bi-file-earmark-plus"></i> Borrow Requests</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#user-reviews"><i class="bi bi-star"></i> User Reviews</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#library-settings"><i class="bi bi-gear"></i> Library Settings</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#create-notification"><i class="bi bi-bell"></i> Create Notifications</a>
            </li>
        </ul>

        <div class="tab-content mt-3">
            <div class="tab-pane fade show active" id="add-book">
                <h2>Add New Book</h2>
                <form id="add-book-form" onsubmit="return false;">
                    <input type="text" id="book-title" class="form-control" placeholder="Book Title" required>
                    <input type="text" id="book-author" class="form-control" placeholder="Book Author" required>
                    <input type="number" id="book-copies" class="form-control" placeholder="Total Copies" required>
                    <button class="btn btn-primary mt-2" onclick="addBook()"><i class="bi bi-plus-circle"></i> Add Book</button>
                </form><br>          
                
                <button id="autoReturnButton" class="btn btn-warning" onclick="triggerAutoReturn()"><i class="bi bi-arrow-return-left"></i>
                    Return Overdue Books
                </button>
            </div>

            <div class="tab-pane fade" id="all-books">
                <h2>All Books</h2>
                <input type="text" id="search-book" class="form-control" placeholder="Search Books" onkeyup="searchBooks()">
                <ul class="list-group mt-3" id="book-list">
                    <?php foreach ($books as $book): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center" data-title="<?php echo htmlspecialchars($book['title']); ?>">
                            <div>
                                <?php echo htmlspecialchars($book['title']) . ' by ' . htmlspecialchars($book['author']); ?><br>
                                <small>Total Copies: <?php echo htmlspecialchars($book['total_copies']); ?></small><br>
                                <small>Available Copies: <?php echo htmlspecialchars($book['available_copies']); ?></small>
                            </div>
                            <div>
                                <button class="btn btn-warning btn-sm mr-2" onclick="editBook(<?php echo $book['id']; ?>)"><i class="bi bi-pencil"></i> Edit</button>
                                <button class="btn btn-danger btn-sm" onclick="removeBook(<?php echo $book['id']; ?>)"><i class="bi bi-trash"></i> Remove</button>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="tab-pane fade" id="borrow-requests">
                <!-- Borrow Requests Section -->
                <h2 class="mt-3">Borrow Requests</h2>
                <input type="text" id="search-borrow" class="form-control" placeholder="Search Borrow Requests" onkeyup="searchBorrowRequests()">
                <ul class="list-group mt-3" id="borrow-list">
                    <?php foreach ($borrowedBooks as $borrowedBook): ?>
                        <li class="list-group-item" data-title="<?php echo htmlspecialchars($borrowedBook['title']); ?>" data-username="<?php echo htmlspecialchars($borrowedBook['username']); ?>">
                            <?php echo htmlspecialchars($borrowedBook['title']) . " requested by " . htmlspecialchars($borrowedBook['username']); ?>
                            <button class="btn btn-success btn-sm float-right" onclick="confirmBorrow(<?php echo htmlspecialchars($borrowedBook['id']); ?>)"><i class="bi bi-check-circle"></i> Confirm</button>
                            <button class="btn btn-danger btn-sm float-right mr-2" onclick="denyBorrow(<?php echo htmlspecialchars($borrowedBook['id']); ?>)"><i class="bi bi-x-circle"></i> Deny</button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="tab-pane fade" id="user-reviews">
                <h2>User Reviews</h2>
                <ul class="list-group mt-3">
                    <?php foreach ($reviews as $review): ?>
                        <li class="list-group-item">
                            <strong><?php echo htmlspecialchars($review['username']); ?></strong>: 
                            <?php echo htmlspecialchars($review['review_text']); ?>
                            <button class="btn btn-danger btn-sm float-right" onclick="deleteReview(<?php echo $review['id']; ?>)"><i class="bi bi-trash"></i> Delete</button>

                            <?php if ($review['response']): ?>
                                <p><strong style="color: brown;">Your Response As Admin:</strong> <?php echo htmlspecialchars($review['response']); ?></p>
                            <?php else: ?>
                                <input type="text" id="response-<?php echo $review['id']; ?>" class="form-control mt-1" 
                                    placeholder="Enter your response here..." 
                                    value="<?php echo htmlspecialchars(generateResponse($review['review_text'])); ?>"
                                >
                            <?php endif; ?>
                            
                            <button class="btn btn-info btn-sm float-right mr-2" onclick="respondToReview(<?php echo $review['id']; ?>)">
                                <i class="bi bi-reply"></i> Respond
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Library Settings Section -->
            <div class="tab-pane fade" id="library-settings">
                <h2>Edit Library Settings</h2>
                <form id="library-settings-form" onsubmit="return false;">
                    <input type="text" id="welcome-message" class="form-control mb-2" placeholder="Welcome Message" required>
                    <textarea id="privacy-policy" class="form-control mb-2" placeholder="Privacy Policy" rows="4" required></textarea>
                    <textarea id="library-history" class="form-control mb-2" placeholder="Library History" rows="4" required></textarea>
                    <button class="btn btn-primary mt-2" onclick="saveLibrarySettings()"><i class="bi bi-save"></i> Save Changes</button>
                </form>
            </div>

            <div class="tab-pane fade" id="create-notification">
                <h2>Create/Edit Notification</h2>
                <form id="create-notification-form">
                    <div class="form-group">
                        <label for="notification-message">Notification Message</label>
                        <textarea class="form-control" id="notification-message" rows="3" placeholder="Enter the notification message" required></textarea>
                    </div>
                    <!-- Hidden input to hold the notification ID when editing -->
                    <input type="hidden" id="notification-id">
                    <button type="submit" class="btn btn-primary">Send Notification</button>
                </form>

                <h3 class="mt-5">Manage Notifications</h3>
                <ul class="list-group" id="admin-notifications-list">
                    <!-- Notifications to be listed here for editing or deletion -->
                </ul>
            </div>
                  
        </div>
    </div>

    <script src="assets/jquery/jquery-3.5.1.slim.min.js"></script>
    <script src="assets/popperjs/v2.9.2/popper.min.js"></script>
    <script src="assets/bootstrap/js/v4.5.2/bootstrap.min.js"></script>
    <script>
        //// Save current tab in localStorage
        $(document).ready(function() {
            const activeTab = localStorage.getItem('activeTab') || '#add-book';
            $('.nav-tabs a[href="' + activeTab + '"]').tab('show');

            $('.nav-tabs a').on('click', function() {
                const target = $(this).attr('href');
                localStorage.setItem('activeTab', target);
            });
        });

        function triggerAutoReturn() {
            //// Create an AJAX request
            var xhr = new XMLHttpRequest();
            xhr.open("GET", "auto_return.php", true);
            
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    alert(xhr.responseText);
                }
            };
            
            xhr.send();
        }

        //// Searching books
        function searchBooks() {
            const searchInput = document.getElementById('search-book').value.toLowerCase();
            console.log("Search Input:", searchInput); 

            const bookItems = document.querySelectorAll('#book-list .list-group-item');

            bookItems.forEach(item => {
                const title = item.getAttribute('data-title').toLowerCase();
                console.log("Book Title:", title); //// Log each book title

                if (title.includes(searchInput)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        //// Function to load notifications for the admin
        function loadAdminNotifications() {
            fetch('getNotificationsForAdmin.php')
                .then(response => response.json())
                .then(data => {
                    const notificationsList = document.getElementById('admin-notifications-list');
                    notificationsList.innerHTML = ''; 

                    data.notifications.forEach(notification => {
                        let listItem = document.createElement('li');
                        listItem.classList.add('list-group-item');
                        listItem.innerHTML = `
                            <strong>${notification.message}</strong><br>
                            <small>Sent to: ${notification.username}</small><br>
                            <small>Sent at: ${new Date(notification.sent_at).toLocaleString()}</small>
                            <button class="btn btn-warning btn-sm" onclick="editNotification(${notification.id}, '${notification.message}')">Edit</button>
                            <button class="btn btn-danger btn-sm" onclick="deleteNotificationAdmin(${notification.id})">Delete</button>
                        `;
                        notificationsList.appendChild(listItem);
                    });
                })
                .catch(error => console.error('Error fetching notifications:', error));
        }

        function editNotification(id, message) {
            document.getElementById('notification-message').value = message;
            document.getElementById('notification-id').value = id;
            
            const notificationItems = document.querySelectorAll('.list-group-item');
            notificationItems.forEach(item => item.classList.remove('bg-warning')); 
            const selectedItem = document.querySelector(`.list-group-item[data-id='${id}']`);
            if (selectedItem) selectedItem.classList.add('bg-warning');
        }

        //// Function to update the notification message
        document.getElementById('create-notification-form').addEventListener('submit', function (e) {
            e.preventDefault();

            const message = document.getElementById('notification-message').value.trim();
            const notificationId = document.getElementById('notification-id').value;

            if (!message) {
                alert('Please enter a notification message.');
                return;
            }

            //// If an ID exists, update the existing notification
            if (notificationId) {
                fetch('updateNotification.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ notification_id: notificationId, message: message })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert('Notification updated successfully!');
                        loadAdminNotifications(); 
                        document.getElementById('create-notification-form').reset(); 
                    } else {
                        alert('Failed to update notification.');
                    }
                })
                .catch(error => console.error('Error updating notification:', error));
            } else {
                fetch('sendNotification.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message: message })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert('Notification sent successfully!');
                        loadAdminNotifications(); 
                        document.getElementById('create-notification-form').reset(); 
                    } else {
                        alert('Failed to send notification.');
                    }
                })
                .catch(error => console.error('Error sending notification:', error));
            }
        });

        //// Function to delete a notification
        function deleteNotificationAdmin(id) {
            fetch('deleteNotificationAdmin.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ notification_id: id })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    loadAdminNotifications(); 
                }
            })
            .catch(error => console.error('Error deleting notification:', error));
        }

        //// Call to load notifications when the page loads
        loadAdminNotifications();

        function searchBorrowRequests() {
            const query = $('#search-borrow').val().toLowerCase();
            $('#borrow-list li').filter(function() {
                $(this).toggle($(this).data('title').toLowerCase().indexOf(query) > -1 || $(this).data('username').toLowerCase().indexOf(query) > -1);
            });
        }

        function searchUsers() {
            const query = $('#search-user').val().toLowerCase();
            $('#user-list li').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(query) > -1);
            });
        }

        function addUser() {
            const username = document.getElementById('new-username').value;
            const email = document.getElementById('new-email').value;
            const password = document.getElementById('new-password').value;

            const formData = new FormData();
            formData.append('action', 'add_user');
            formData.append('username', username);
            formData.append('email', email);
            formData.append('password', password);

            fetch('add_user.php', {
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

        function updateAccount() {
            const username = document.getElementById('edit-username').value;
            const email = document.getElementById('edit-email').value;
            const password = document.getElementById('edit-password').value;

            const formData = new FormData();
            formData.append('action', 'update_account');
            formData.append('username', username);
            formData.append('email', email);
            formData.append('password', password);

            fetch('admin_dashboard.php', {
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

        function deleteAccount() {
            if (confirm('Are you sure you want to delete your account?')) {
                const formData = new FormData();
                formData.append('action', 'delete_account');

                fetch('admin_dashboard.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                    if (data.status === 'success') {
                        window.location.href = 'index.php';
                    }
                });
            }
        }

        function deleteUser(userId) {
            if (confirm('Are you sure you want to delete this user?')) {
                const formData = new FormData();
                formData.append('user_id', userId);

                fetch('delete_user.php', {
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
        }

        function addBook() {
            const title = document.getElementById('book-title').value;
            const author = document.getElementById('book-author').value;
            const copies = document.getElementById('book-copies').value;

            const formData = new FormData();
            formData.append('title', title);
            formData.append('author', author);
            formData.append('copies', copies);

            fetch('add_book.php', {
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

        function removeBook(bookId) {
            if (confirm('Are you sure you want to remove this book?')) {
                const formData = new FormData();
                formData.append('book_id', bookId);

                fetch('remove_book.php', {
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
        }

        function editBook(bookId) {
            const newTitle = prompt("Enter new title:");
            const newAuthor = prompt("Enter new author:");
            const newCopies = prompt("Enter new total copies:");

            if (newTitle && newAuthor && newCopies) {
                const formData = new FormData();
                formData.append('book_id', bookId);
                formData.append('title', newTitle);
                formData.append('author', newAuthor);
                formData.append('copies', newCopies);

                fetch('edit_book.php', {
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
        }

        function confirmBorrow(borrowId) {
            const formData = new FormData();
            formData.append('borrow_id', borrowId);
            formData.append('action', 'confirm');

            fetch('borrow_request.php', {
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

        function denyBorrow(borrowId) {
            const formData = new FormData();
            formData.append('borrow_id', borrowId);
            formData.append('action', 'deny');

            fetch('borrow_request.php', {
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

        function respondToReview(reviewId) {
            const response = document.getElementById(`response-${reviewId}`).value;
            if (response) {
                const formData = new FormData();
                formData.append('review_id', reviewId);
                formData.append('response', response);

                fetch('respond_review.php', {
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
            } else {
                alert('Please enter a response before submitting.');
            }
        }

        function deleteReview(reviewId) {
            if (confirm('Are you sure you want to delete this review?')) {
                const formData = new FormData();
                formData.append('review_id', reviewId);

                fetch('delete_review.php', {
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
        }

        function saveLibrarySettings() {
            const welcomeMessage = document.getElementById('welcome-message').value;
            const privacyPolicy = document.getElementById('privacy-policy').value;
            const libraryHistory = document.getElementById('library-history').value;

            const formData = new FormData();
            formData.append('welcome_message', welcomeMessage);
            formData.append('privacy_policy', privacyPolicy);
            formData.append('library_history', libraryHistory);

            fetch('update_library_settings.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.status === 'success') {
                    loadLibrarySettings(); 
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while saving settings.');
            });
        }

        //// Load existing library settings on page load
        function loadLibrarySettings() {
            fetch('get_library_settings.php')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('welcome-message').value = data.welcome_message || '';
                    document.getElementById('privacy-policy').value = data.privacy_policy || '';
                    document.getElementById('library-history').value = data.library_history || '';
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
