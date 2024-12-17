<?php
session_start();
require_once '../db/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);  // Unauthorized if user_id session variable is not set
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['receiver_id']) || !isset($data['message'])) {
    http_response_code(400);  // Bad request if required data is not provided
    exit();
}

$query = "INSERT INTO game_messages (sender_id, receiver_id, message_text) 
          VALUES (?, ?, ?)";

$stmt = $conn->prepare($query);
$stmt->bind_param("iis", $_SESSION['user_id'], $data['receiver_id'], $data['message']);

if ($stmt->execute()) {
    http_response_code(200);  // OK response if message is successfully inserted
} else {
    http_response_code(500);  // Internal server error if execution fails
}
