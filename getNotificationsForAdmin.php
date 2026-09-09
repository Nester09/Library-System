<?php

include 'db.php';

//// Fetch notifications with user names
$query = "
    SELECT n.id, n.message, n.sent_at, u.username 
    FROM notification n
    LEFT JOIN users u ON n.user_id = u.id
    WHERE n.is_deleted = 0
    ORDER BY n.sent_at DESC
";
$stmt = $pdo->prepare($query);
$stmt->execute();

$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['notifications' => $notifications]);
?>
