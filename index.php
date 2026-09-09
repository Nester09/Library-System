<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/bootstrap/css/v4.5.2/bootstrap.min.css">
    <link rel="stylesheet" href="assets/bootstrap-icons-1.5.0/bootstrap-icons.css">
    <link rel="stylesheet" href="styles.css">
    <title>Library Management System</title>
</head>
<body>
    <div class="container text-center mt-5">
       <h1>Library Management System</h1>
        <div class="header4">
            <i class="bi bi-book"></i>
        </div>
        <div class="icon-container mt-4">
            <div class="icon" id="admin-icon" onclick="showRegistration('admin')">
                <span class="display-4">
                    <i class="bi bi-person-badge"></i>
                </span>
                <p>Admin</p>
            </div>
            <div class="line"></div>
            <div class="icon" id="user-icon" onclick="showRegistration('user')">
                <span class="display-4">
                    <i class="bi bi-person"></i>
                </span>
                <p>User</p>
            </div>
        </div>
        <div id="form-container" style="display:none;"></div>
    </div>

    <script src="script.js"></script>
</body>
</html>