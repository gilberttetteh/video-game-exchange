<?php
// Change from receiver_id to contact_id
if (!isset($_SESSION['user_id']) || !isset($_GET['contact_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
    exit();
}


try {
    $receiver_id = intval($_GET['contact_id']); 
    
    // Get messages between users with sender/receiver details
    $query = "SELECT 
        m.*,
        s.username as sender_username,
        r.username as receiver_username,
        s.role as sender_role,
        r.role as receiver_role
    FROM game_messages m
    JOIN game_users s ON m.sender_id = s.user_id
    JOIN game_users r ON m.receiver_id = r.user_id
    WHERE (m.sender_id = ? AND m.receiver_id = ?)
    OR (m.sender_id = ? AND m.receiver_id = ?)
    ORDER BY m.created_at ASC";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("iiii", 
        $_SESSION['user_id'], 
        $receiver_id, 
        $receiver_id, 
        $_SESSION['user_id']
    );
    
    $stmt->execute();
    $result = $stmt->get_result();

    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = [
            'message_id' => $row['message_id'],
            'sender_id' => $row['sender_id'],
            'receiver_id' => $row['receiver_id'],
            'message' => htmlspecialchars($row['message']),
            'sender_username' => htmlspecialchars($row['sender_username']),
            'receiver_username' => htmlspecialchars($row['receiver_username']),
            'sender_role' => $row['sender_role'],
            'receiver_role' => $row['receiver_role'],
            'created_at' => $row['created_at'],
            'is_sender' => $_SESSION['user_id'] == $row['sender_id']
        ];
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'messages' => $messages]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error', 'message' => $e->getMessage()]);
}