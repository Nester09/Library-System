<?php

include 'db.php';

$stmt = $pdo->query("SELECT * FROM library_settings LIMIT 1");
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode($settings);
?>