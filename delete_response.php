<?php
session_start();

include 'db.php';

header('Content-Type: application/json'); 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
   $data = json_decode(file_get_contents("php://input"), true);
   
   if (!isset($data['response_id'])) {
       echo json_encode(['status' => 'error', 'message' => 'Response ID is required.']);
       exit;
   }

   $responseId = $data['response_id'];

   $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
   
   if ($stmt->execute([$responseId])) {
       echo json_encode(['status' => 'success', 'message' => 'Response deleted successfully.']);
       exit;
   } else {
       echo json_encode(['status' => 'error', 'message' => 'Failed to delete response.']);
   }
}
?>