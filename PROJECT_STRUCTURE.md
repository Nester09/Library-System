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
│   │   ├── css/
│   │   │   ├── v4.5.2/bootstrap.min.css
│   │   │   ├── v5.1.3/bootstrap.min.css
│   │   │   └── v5.3.0-alpha1/bootstrap.min.css
│   │   └── js/
│   │       ├── v4.5.2/bootstrap.bundle.min.js
│   │       ├── v4.5.2/bootstrap.min.js
│   │       └── v5.3.0-alpha1/bootstrap.bundle.min.js
│   │
│   ├── bootstrap-icons-1.5.0/
│   │   ├── bootstrap-icons.css
│   │   ├── bootstrap-icons.json
│   │   ├── bootstrap-icons.svg
│   │   └── many Bootstrap Icons SVG files
│   │
│   ├── font-awesome/
│   │   ├── css/
│   │   └── webfonts/
│   │
│   ├── jquery/
│   │   └── jquery-3.5.1.slim.min.js
│   │
│   └── popperjs/
│       ├── v2.5.4/popper.min.js
│       ├── v2.9.2/popper.min.js
│       ├── v2.9.3/popper.min.js
│       └── v2.10.2/popper.min.js
│
├── uploads/
│   └── .gitkeep
│
└── vendor/
    ├── autoload.php
    ├── composer/
    └── phpmailer/
        └── phpmailer/
            ├── src/
            ├── language/
            └── test/


## Main folders and files

- `README.md` - project overview, features, requirements, installation, database setup, and usage instructions.
- `PROJECT_STRUCTURE.md` - explanation of the project's folders and important files.
- `database/system.sql` - database structure and sample data for the system.
- `assets/` - local Bootstrap, Bootstrap Icons, Font Awesome, jQuery and Popper files used by the project.
- `vendor/` - Composer files and PHPMailer used for email functionality.
- `uploads/` - local uploaded files if the application creates any. The folder is kept in Git with `.gitkeep`, while uploaded files are ignored.
- `db.php` - database connection.
- `dashboard.php` - user dashboard and book borrowing/returning interface.
- `admin_dashboard.php` - administrator dashboard.
- `book_management.php` - book borrowing and returning actions.
- `borrow_request.php` - confirms or denies borrowing requests.
- `auto_return.php` - handles automatic returns for overdue books.
- `return_book.php` - handles a user's return request and records the return in `returned_books`.
- `login.php`, `register.php`, `logout.php` - user account and login functions.
- `password_reset.php`, `reset_password.php` - password reset functions.
- `send_email.php` - email sending functionality using PHPMailer.
- `styles.css`, `dashboard_styles.css`, `script.js` - main styling and JavaScript.