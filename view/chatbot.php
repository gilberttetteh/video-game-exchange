<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
session_start();

// Include necessary files
$requiredFiles = [
    '../db/db.php',
    '../functions/email_notifications.php',
    '../functions/user_helpers.php'
];

foreach ($requiredFiles as $file) {
    require_once $file;
}

// Security: Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}


// Main request handler
function handleGameExchangeRequest($connection, $user_id) {
    ob_clean();
    header('Content-Type: application/json');
    
    try {
        $request = json_decode(file_get_contents('php://input'), true);
        $action = $request['action'] ?? null;
        $selected_game = $request['selected_game'] ?? null;

        $response = [
            "message" => "",
            "options" => []
        ];

        if (!isset($_SESSION['chat_state'])) {
            $_SESSION['chat_state'] = "start";
        }

        switch ($_SESSION['chat_state']) {
            case "start":
                $response['message'] = "Hi! You can borrow or exchange games. What would you like to do?";
                $response['options'] = ["Borrow", "Exchange"];
                $_SESSION['chat_state'] = "select_action";
                break;

            case "select_action":
                if ($action === "Borrow") {
                    $response = handleBorrowAction($connection, $user_id);
                } elseif ($action === "Exchange") {
                    $response = handleExchangeAction($connection, $user_id);
                } else {
                    $response['message'] = "Please choose a valid option.";
                }
                break;

            case "borrow_game":
                $response = processBorrowRequest($connection, $user_id, $selected_game);
                break;

            case "select_offered_game":
                $response = handleOfferedGameSelection($connection, $user_id, $selected_game);
                break;

            case "select_requested_game":
                $response = processExchangeRequest($connection, $user_id, $selected_game);
                break;

            default:
                $response['message'] = "Something went wrong. Please start over.";
                $_SESSION['chat_state'] = "start";
                break;
        }

        return $response;

    } catch (Exception $e) {
        return [
            'message' => 'An unexpected error occurred. Please try again.',
            'error' => true
        ];
    }
}

// Helper function to get available games for borrowing
function handleBorrowAction($connection, $user_id) {
    $query = "SELECT g.game_id, g.title, u.username, u.email 
              FROM game_games g
              JOIN game_users u ON g.user_id = u.user_id
              WHERE g.user_id != ? AND g.game_id != 99999";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $response = [
        'message' => 'No games available for borrowing.',
        'options' => []
    ];

    if ($result->num_rows > 0) {
        $response['message'] = "Here are the games available for borrowing. Select a game:";
        $_SESSION['game_map'] = [];
        
        while ($row = $result->fetch_assoc()) {
            $title = $row['title'] . " (Owner: " . $row['username'] . ")";
            $response['options'][] = $title;
            $_SESSION['game_map'][$title] = [
                'id' => $row['game_id'],
                'email' => $row['email']
            ];
        }
        $_SESSION['chat_state'] = "borrow_game";
    }

    return $response;
}

// Helper function to get user's games for exchange
function handleExchangeAction($connection, $user_id) {
    $query = "SELECT game_id, title FROM game_games WHERE user_id = ? AND game_id != 99999";

    $stmt = $connection->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $response = [
        'message' => 'You have no games to offer for exchange.',
        'options' => []
    ];

    if ($result->num_rows > 0) {
        $response['message'] = "Select a game you want to offer for exchange:";
        $_SESSION['game_map'] = [];
        
        while ($row = $result->fetch_assoc()) {
            $response['options'][] = $row['title'];
            $_SESSION['game_map'][$row['title']] = $row['game_id'];
        }
        $_SESSION['chat_state'] = "select_offered_game";
    }

    return $response;
}

// Process borrowing a game
function processBorrowRequest($connection, $user_id, $selected_game) {
    $response = [
        'message' => 'Game selection failed.',
        'options' => []
    ];

    if ($selected_game && isset($_SESSION['game_map'][$selected_game])) {
        $game_data = $_SESSION['game_map'][$selected_game];
        $game_id = $game_data['id'];

        // Validate game ID to prevent placeholder
        if ($game_id == 99999) {
            $response['message'] = "Invalid game selection.";
            return $response;
        }

        $connection->begin_transaction();
        try {
            // Get lender's ID
            $query = "SELECT user_id FROM game_games WHERE game_id = ? AND game_id != 99999";
            $stmt = $connection->prepare($query);
            $stmt->bind_param("i", $game_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                $lender_id = $row['user_id'];

                // Validate game ID again before insertion
                if ($game_id == 99999) {
                    throw new Exception("Invalid game selection");
                }

                // Insert borrow request
                $insert_query = "INSERT INTO game_requests 
                                 (borrower_id, lender_id, game_id, request_type, status, request_date) 
                                 VALUES (?, ?, ?, 'borrow', 'pending', NOW())";
                $stmt = $connection->prepare($insert_query);
                $stmt->bind_param("iii", $user_id, $lender_id, $game_id);
                $stmt->execute();

                $connection->commit();
                $response['message'] = "Borrow request sent successfully!";
                $_SESSION['chat_state'] = "start";
            } else {
                $response['message'] = "Game not found or unavailable.";
            }
        } catch (Exception $e) {
            $connection->rollback();
            $response['message'] = "Request failed: " . $e->getMessage();
        }
    }

    return $response;
}

// Handle selecting a game to offer in exchange
function handleOfferedGameSelection($connection, $user_id, $selected_game) {
    $response = [
        'message' => 'Game selection failed.',
        'options' => []
    ];

    if ($selected_game && isset($_SESSION['game_map'][$selected_game])) {
        $offered_game_id = $_SESSION['game_map'][$selected_game];
        
        // Validate offered game ID
        if ($offered_game_id == 99999) {
            $response['message'] = "Invalid game selection.";
            return $response;
        }
        
        $_SESSION['offered_game_id'] = $offered_game_id;
        
        $query = "SELECT g.game_id, g.title, u.username, u.email 
                  FROM game_games g
                  JOIN game_users u ON g.user_id = u.user_id
                  WHERE g.user_id != ? AND g.game_id != 99999";
        $stmt = $connection->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $response['message'] = "Select a game you want to receive:";
            $_SESSION['game_map'] = [];
            
            while ($row = $result->fetch_assoc()) {
                $title = $row['title'] . " (Owner: " . $row['username'] . ")";
                $response['options'][] = $title;
                $_SESSION['game_map'][$title] = [
                    'id' => $row['game_id'],
                    'email' => $row['email']
                ];
            }
            $_SESSION['chat_state'] = "select_requested_game";
        }
    }

    return $response;
}

// Process game exchange request
function processExchangeRequest($connection, $user_id, $selected_game) {
    $response = [
        'message' => 'Exchange request failed.',
        'options' => []
    ];

    if ($selected_game && 
        isset($_SESSION['game_map'][$selected_game]) && 
        isset($_SESSION['offered_game_id'])
    ) {
        $game_data = $_SESSION['game_map'][$selected_game];
        $requested_game_id = $game_data['id'];
        $offered_game_id = $_SESSION['offered_game_id'];
        
        // Validate game IDs to prevent placeholder
        if ($offered_game_id == 99999 || $requested_game_id == 99999) {
            $response['message'] = "Invalid game selection.";
            return $response;
        }

        $connection->begin_transaction();
        
        try {
            // Verify that both games exist and are valid
            $verify_query = "SELECT game_id FROM game_games WHERE game_id IN (?, ?) AND game_id != 99999";
            $verify_stmt = $connection->prepare($verify_query);
            $verify_stmt->bind_param("ii", $offered_game_id, $requested_game_id);
            $verify_stmt->execute();
            $verify_result = $verify_stmt->get_result();
            
            if ($verify_result->num_rows != 2) {
                throw new Exception("Invalid game selection");
            }
            
            // Get the owner of the requested game
            $owner_query = "SELECT user_id FROM game_games WHERE game_id = ? AND game_id != 99999";
            $owner_stmt = $connection->prepare($owner_query);
            $owner_stmt->bind_param("i", $requested_game_id);
            $owner_stmt->execute();
            $owner_result = $owner_stmt->get_result();
            
            if (!($owner_row = $owner_result->fetch_assoc())) {
                throw new Exception("Game owner not found");
            }
            
            $lender_id = $owner_row['user_id'];
            
            // Insert exchange request
            $insert_query = "INSERT INTO game_requests 
                            (borrower_id, lender_id, game_id, requested_game_id, request_type, status, request_date) 
                            VALUES (?, ?, ?, ?, 'exchange', 'pending', NOW())";
            
            $stmt = $connection->prepare($insert_query);
            $stmt->bind_param("iiii", $user_id, $lender_id, $offered_game_id, $requested_game_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to insert exchange request");
            }
            $connection->commit();
            $response['message'] = "Exchange request sent successfully!";
            $_SESSION['chat_state'] = "start";
            
            
            
            // Send email notification
            $notification_sent = sendEmail(
                $game_data['email'],
                "New Game Exchange Request",
                "Hello,<br><br>A user has requested to exchange games with you.<br>
                Please log in to review and respond to this request.<br><br>
                Best regards,<br>Game Exchange Team"
            );
            
            if (!$notification_sent) {
                throw new Exception("Failed to send email notification");
            }
            
            $connection->commit();
            $response['message'] = "Exchange request sent successfully! The owner has been notified.";
            $_SESSION['chat_state'] = "start";
            
        } catch (Exception $e) {
            $connection->rollback();
            $response['message'] = "Failed to send exchange request: " . $e->getMessage();
            error_log("Exchange request error: " . $e->getMessage());
        }
    }

    return $response;
}

// Main execution remains the same
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = handleGameExchangeRequest($connection, $_SESSION['user_id']);
    echo json_encode($response);
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Game Exchange Chatbot</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
                margin: 0;
                padding: 0;
                min-height: 100vh;
                font-family: 'Inter', sans-serif;
                background: #0F172A;
                background-image: 
                    radial-gradient(at 40% 20%, rgba(61, 65, 251, 0.1) 0px, transparent 50%),
                    radial-gradient(at 80% 0%, rgba(31, 169, 227, 0.1) 0px, transparent 50%),
                    radial-gradient(at 0% 50%, rgba(255, 101, 132, 0.1) 0px, transparent 50%);
                color: #E2E8F0;
            }

            .chat-container {
                max-width: 800px;
                margin: 40px auto;
                background: rgba(255, 255, 255, 0.03);
                backdrop-filter: blur(10px);
                border: 1px solid rgba(255, 255, 255, 0.1);
                border-radius: 24px;
                padding: 20px;
                box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.2);
                position: relative;
                overflow: hidden;
            }

            .messages {
                height: 500px;
                overflow-y: auto;
                padding: 20px;
                scroll-behavior: smooth;
            }

            .messages::-webkit-scrollbar {
                width: 6px;
            }

            .messages::-webkit-scrollbar-track {
                background: rgba(255, 255, 255, 0.05);
                border-radius: 3px;
            }

            .messages::-webkit-scrollbar-thumb {
                background: rgba(255, 255, 255, 0.2);
                border-radius: 3px;
            }

            .message {
                margin: 12px 0;
                padding: 12px 16px;
                border-radius: 16px;
                max-width: 80%;
                animation: fadeIn 0.3s ease-out;
            }
            .message.success {
            background: linear-gradient(135deg, rgba(76, 175, 80, 0.2), rgba(46, 125, 50, 0.2));
            border: 1px solid rgba(76, 175, 80, 0.3);
            color: #4CAF50;
            font-weight: 500;
        }

            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(10px); }
                to { opacity: 1; transform: translateY(0); }
            }

            .user {
                margin-left: auto;
                background: linear-gradient(135deg, #6366F1, #4F46E5);
                color: white;
                border-radius: 16px 16px 4px 16px;
                box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);
            }

            .bot {
                margin-right: auto;
                background: rgba(255, 255, 255, 0.05);
                border: 1px solid rgba(255, 255, 255, 0.1);
                color: #E2E8F0;
                border-radius: 16px 16px 16px 4px;
            }

            .options {
                padding: 20px;
                display: grid;
                gap: 10px;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }

            .options button {
                background: rgba(255, 255, 255, 0.05);
                border: 1px solid rgba(255, 255, 255, 0.1);
                color: #E2E8F0;
                padding: 12px 20px;
                border-radius: 12px;
                cursor: pointer;
                font-size: 14px;
                font-weight: 500;
                transition: all 0.3s ease;
                backdrop-filter: blur(4px);
            }

            .options button:hover {
                background: rgba(99, 102, 241, 0.1);
                border-color: rgba(99, 102, 241, 0.5);
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);
            }

            /* Floating particles animation */
            .chat-container::before {
                content: '';
                position: absolute;
                width: 100px;
                height: 100px;
                background: radial-gradient(circle, rgba(99, 102, 241, 0.1) 0%, transparent 70%);
                animation: float 8s infinite;
                top: -50px;
                left: -50px;
            }

            .chat-container::after {
                content: '';
                position: absolute;
                width: 150px;
                height: 150px;
                background: radial-gradient(circle, rgba(79, 70, 229, 0.1) 0%, transparent 70%);
                animation: float 12s infinite;
                bottom: -75px;
                right: -75px;
            }

            @keyframes float {
                0% { transform: translate(0, 0) rotate(0deg); }
                50% { transform: translate(20px, 20px) rotate(180deg); }
                100% { transform: translate(0, 0) rotate(360deg); }
            }
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="messages" id="messages"></div>
        <div class="options" id="options"></div>
    </div>

    <script>
        function addMessage(type, text) {
            const messagesDiv = document.getElementById('messages');
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${type}`;
            messageDiv.textContent = text;
            messagesDiv.appendChild(messageDiv);
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
        }

        function showOptions(options) {
            const optionsDiv = document.getElementById('options');
            optionsDiv.innerHTML = '';
            
            options.forEach(option => {
                const button = document.createElement('button');
                button.textContent = option;
                button.onclick = () => {
                    addMessage('user', option);
                    optionsDiv.innerHTML = '';
                    sendMessage(option, option);
                };
                optionsDiv.appendChild(button);
            });
        }

        function sendMessage(action, selectedGame = null) {
            if (!action || (selectedGame && selectedGame.includes('99999'))) {
                addMessage('bot', "Invalid selection. Please try again.");
                return;
            }

            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action,
                    selected_game: selectedGame
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.message) {
                    addMessage('bot', data.message);
                    
                    if (data.message.includes('successfully')) {
                        const lastMessage = document.querySelector('.messages .message:last-child');
                        lastMessage.classList.add('success');
                        document.getElementById('options').innerHTML = '';
                        setTimeout(() => sendMessage("Hello"), 2000);
                    } else if (data.options && data.options.length > 0) {
                        showOptions(data.options);
                    }
                }
            })
            .catch(() => {
                addMessage('bot', "Error: An issue occurred. Please try again.");
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            sendMessage("Hello");
        });
    </script>
</body>
</html>