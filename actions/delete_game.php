<?php
require_once '../db/db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if (!isset($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'Game ID not provided']);
    exit;
}

$gameId = intval($_POST['id']);
$userId = $_SESSION['user_id'];

$stmt = $conn->prepare("DELETE FROM games WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $gameId, $userId);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error deleting game']);
}

$stmt->close();
$conn->close();
?>