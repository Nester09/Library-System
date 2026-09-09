# Library System

A web-based Library Management System built with PHP and MySQL/MariaDB. The system provides separate functionality for library users and administrators, including book management, borrowing requests, returns, reviews, notifications, account management, and password recovery.

## Features

### User Features

* User registration and login
* Secure password hashing using PHP's password hashing functions
* Account management
* Password reset functionality
* Browse available books
* Request books for borrowing
* View borrowed books
* Return borrowed books
* Automatic handling of overdue returns
* Submit book reviews and ratings
* View notifications
* Delete notifications
* Logout functionality

### Administrator Features

* Administrator login and dashboard
* Add and manage books
* Edit and remove books
* Manage registered users
* Review and process borrowing requests
* Confirm or deny borrowing requests
* Monitor returned books
* Respond to user reviews
* Manage library notifications
* Update library information and settings
* Manage library welcome message, privacy policy, and history

### Other Features

* Email functionality using PHPMailer
* Login attempt tracking and temporary account lockout
* MySQL/MariaDB database integration through PDO
* Responsive interface using Bootstrap
* Bootstrap Icons and Font Awesome
* Local JavaScript and CSS assets
* Database seed/sample data included in the SQL dump

---

## Technology Stack

| Technology      | Purpose                        |
| --------------- | ------------------------------ |
| PHP             | Server-side application logic  |
| MySQL / MariaDB | Database management            |
| PDO             | Database connectivity          |
| HTML5           | Page structure                 |
| CSS3            | Custom styling                 |
| JavaScript      | Client-side functionality      |
| Bootstrap       | Responsive UI components       |
| Bootstrap Icons | Interface icons                |
| Font Awesome    | Additional icons               |
| jQuery          | Client-side JavaScript support |
| PHPMailer       | Email functionality            |
| Composer        | PHP dependency management      |
| Apache          | Recommended web server         |

### Main Dependencies

The project currently uses:

* PHPMailer
* Bootstrap
* Bootstrap Icons
* Font Awesome
* jQuery
* Popper.js

PHPMailer is managed through Composer.

---

## Project Structure

Library-System/
│
├── .htaccess
├── .gitignore
├── README.md
├── PROJECT_STRUCTURE.md
│
├── add_book.php
├── add_user.php
├── admin_dashboard.php
├── auto_return.php
├── book_management.php
├── borrow_request.php
├── dashboard.php
├── db.php
├── deleteNotificationAdmin.php
├── deleteNotificationForUser.php
├── delete_account.php
├── delete_response.php
├── delete_returned_book.php
├── delete_review.php
├── delete_user.php
├── edit_book.php
├── getNotificationsForAdmin.php
├── getNotificationsForUser.php
├── get_library_settings.php
├── index.php
├── login.php
├── logout.php
├── password_reset.php
├── register.php
├── remove_book.php
├── reset_password.php
├── return_book.php
├── script.js
├── sendNotification.php
├── send_email.php
├── styles.css
├── submitReview.php
├── updateNotification.php
├── update_account.php
├── update_library_settings.php
│
├── composer.json
├── composer.lock
│
├── database/
│   └── system.sql
│
├── assets/
│   ├── bootstrap/
│   ├── bootstrap-icons-1.5.0/
│   ├── font-awesome/
│   ├── jquery/
│   └── popperjs/
│
├── uploads/
│   └── .gitkeep
│
└── vendor/
    └── phpmailer/


### Important Directories

* `database/system.sql` — database structure and sample data.
* `assets/` — locally stored frontend libraries and assets.
* `uploads/` — directory for files uploaded by the application.
* `vendor/` — Composer dependencies, including PHPMailer.
* `db.php` — database connection configuration.

### Important Application Files

* `index.php` — application entry point.
* `login.php` — user and administrator authentication.
* `register.php` — user registration.
* `dashboard.php` — regular user dashboard.
* `admin_dashboard.php` — administrator dashboard.
* `book_management.php` — book borrowing and returning functionality.
* `borrow_request.php` — administrator handling of borrowing requests.
* `return_book.php` — book return processing.
* `auto_return.php` — automatic handling of overdue books.
* `send_email.php` — email functionality using PHPMailer.
* `password_reset.php` and `reset_password.php` — password recovery functionality.
* `styles.css` and `dashboard_styles.css` — application styling.
* `script.js` — client-side JavaScript functionality.

---

## Requirements

Before running the application, make sure the following are installed:

* PHP 8.0 or later
* MySQL 5.7+ or MariaDB 10.4+
* Apache or another PHP-compatible web server
* Composer
* A modern web browser

### Recommended Environment

The project was developed/tested using an environment similar to:

* PHP 8.2
* MariaDB 10.4
* Apache
* Composer

You can use XAMPP, WAMP, Laragon, or a manually configured Apache/PHP/MySQL environment.

---

## Installation

### 1. Clone the repository

```bash
git clone https://github.com/MY-USERNAME/Library-System.git
```

Move into the project directory:

```bash
cd Library-System
```

### 2. Install PHP dependencies

If Composer is installed, run:

```bash
composer install
```

This installs the dependencies listed in `composer.json`.

### 3. Configure the database connection

Open:

`db.php`

Update the database connection values to match your local environment.

For example:

```php
<?php

$host = 'localhost';
$db_name = 'system';
$username = 'root';
$password = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db_name",
        $username,
        $password
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
```

**For production use, do not store database credentials directly in a publicly accessible source file. Use environment variables or another secure configuration method.**

---

## Database Setup

The project includes a database dump at:

`database/system.sql`

### Using phpMyAdmin

1. Start Apache and MySQL/MariaDB.
2. Open phpMyAdmin.
3. Create a database named:

`system`

4. Select the `system` database.
5. Open the **Import** tab.
6. Select:

`database/system.sql`

7. Click **Import**.

The SQL file creates the required tables and includes sample/test data.

### Using the MySQL command line

Create the database:

```sql
CREATE DATABASE system;
```

Then import the SQL file:

```bash
mysql -u root -p system < database/system.sql
```

If your local MySQL installation does not require a password, press Enter when prompted.

---

## Running the Application

### Using XAMPP

Copy the project into the Apache web directory:

`C:\xampp\htdocs\Library-System`

Start:

* Apache
* MySQL

Then open:

`http://localhost/Library-System/`

### Using PHP's Built-in Development Server

From the project directory, run:

```bash
php -S localhost:8000
```

Then open:

`http://localhost:8000`

Make sure the database server is running before using database-dependent functionality.

---

## Default/Test Accounts

The database dump contains sample accounts for testing.

| Role          | Username      | Email                                   | Password         |
| ------------- | ------------- | --------------------------------------- |------------------|
| Administrator | LibraryAdmin1 | [libraryadmin1@gmail.com]               | admin001         |
| Administrator | LibraryAdmin2 | [libraryadmin2@gmail.com]               | admin002         |
| User          | LibraryUser1  | [libraryuser1@gmail.com]                | user001          |
| User          | LibraryUser2  | [libraryuser2@gmail.com]                | user002          |
| User          | LibraryUser3  | [libraryuser3@gmail.com]                | user003          |

> **Note:** You can create a new account through the registration page and test.

For a public repository, avoid publishing real passwords or other sensitive credentials.

---

## Known Limitations

* Database credentials are currently configured in `db.php` and should be moved to environment variables or another secure configuration mechanism before production deployment.
* The application depends on a configured PHP/MySQL/MariaDB and web-server environment.
* Email functionality requires valid SMTP configuration before it can be used successfully.
* Some frontend libraries are stored locally in the repository, which increases repository size.
* The database contains sample/test data and should be reviewed before using the system in a production environment.
* Authentication and authorization should receive additional security hardening before production deployment.
* Database IDs are auto-incrementing and are not guaranteed to remain sequential after records are deleted. This is expected database behavior.
* The current database design does not include comprehensive foreign-key delete/update behavior for every relationship.
* Additional validation, CSRF protection, rate limiting, logging, and production security configuration would be beneficial for a production deployment.

---

## Development Notes

This project is intended as a practical Library Management System and can be extended with features such as:

* Book categories and genres
* Authors and publishers
* ISBN management
* Search and filtering
* Book cover images
* Fines and payment tracking
* Reservation queues
* Reports and analytics
* Role-based permissions
* REST API support
* Database migrations
* Improved email configuration
* Audit logs
* Advanced security controls

---

## License

This project is distributed under the license included in the repository.

---