<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
session_start();

$requiredFiles = [
    '../db/db.php',
    '../functions/user_helpers.php',
    '../functions/email_notifications.php'
];

foreach ($requiredFiles as $file) {
    require_once $file;
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

class GameExchangeChatbot {
    private $connection;
    private $user_id;

    public function __construct($connection, $user_id) {
        $this->connection = $connection;
        $this->user_id = $user_id;
    }

    private function getUserName() {
        $query = "SELECT username FROM game_users WHERE user_id = ?";
        $stmt = $this->connection->prepare($query);
        $stmt->bind_param("i", $this->user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return ($row = $result->fetch_assoc()) ? $row['username'] : "User";
    }

    private function sendEmail($to_email, $subject, $message) {
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8\r\n";
        $headers .= "From: GameLink <no-reply@gamelink.com>\r\n";
        
        return mail($to_email, $subject, $message, $headers);
    }

    private function getEmailTemplate($content) {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .container { padding: 20px; max-width: 600px; margin: 0 auto; }
                .header { background: #4834d4; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f8f9fa; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>GameLink Notification</h1>
                </div>
                <div class="content">
                    ' . $content . '
                </div>
            </div>
        </body>
        </html>';
    }

    public function getBorrowableGames() {

$query = "SELECT g.game_id, g.title, u.username, u.email, u.user_id as owner_id 
          FROM game_games g
          JOIN game_users u ON g.user_id = u.user_id
          WHERE g.user_id != ? AND g.game_id != 99999";
        $stmt = $this->connection->prepare($query);
        $stmt->bind_param("i", $this->user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $games = [];
        $gameMap = [];
        
        while ($row = $result->fetch_assoc()) {
            $title = $row['title'] . " (Owner: " . $row['username'] . ")";
            $games[] = $title;
            $gameMap[$title] = [
                'id' => $row['game_id'],
                'email' => $row['email'],
                'owner_id' => $row['owner_id']
            ];
        }
        error_log('Number of borrowable games found: ' . count($games));

        return [
            'games' => $games,
            'gameMap' => $gameMap
        ];
    }

    public function getUserGamesForExchange() {
        $query = "SELECT game_id, title FROM game_games WHERE user_id = ? AND game_id != 99999";
        $stmt = $this->connection->prepare($query);
        $stmt->bind_param("i", $this->user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $games = [];
        $gameMap = [];
        
        while ($row = $result->fetch_assoc()) {
            $games[] = $row['title'];
            $gameMap[$row['title']] = $row['game_id'];
        }

        return [
            'games' => $games,
            'gameMap' => $gameMap
        ];
    }

    public function processBorrowRequest($selected_game, $gameMap) {
        // Debugging output
        error_log("Selected game: " . $selected_game);
        error_log("Game map keys: " . implode(", ", array_keys($gameMap)));
    
        if (!isset($gameMap[$selected_game])) {
            return ['message' => 'Invalid game selection.'];
        }
    
        $game_data = $gameMap[$selected_game];
        $this->connection->begin_transaction();
    
        try {
            $insert_query = "INSERT INTO game_requests 
                           (borrower_id, lender_id, game_id, requested_game_id, request_type, status, request_date) 
                           VALUES (?, ?, ?, ?, 'borrow', 'pending', NOW())";
            $stmt = $this->connection->prepare($insert_query);
            $stmt->bind_param("iiii", $this->user_id, $game_data['owner_id'], $game_data['id'], $game_data['id']);
            $stmt->execute();
    
            $content = "<h2>New Game Request</h2>
                       <p>Hello,</p>
                       <p>" . $this->getUserName() . " would like to borrow your game:</p>
                       <p><strong>" . htmlspecialchars(explode(" (Owner:", $selected_game)[0]) . "</strong></p>
                       <p>Please login to respond to this request.</p>";
            
            $message = $this->getEmailTemplate($content);
            $subject = "New Game Request - GameLink";
            
            $sent = $this->sendEmail($game_data['email'], $subject, $message);
    
            if ($sent) {
                $log_query = "INSERT INTO game_emails (user_id, game_id, email_subject, email_body, recipient_email, status, sent_at, created_at, error_message) 
                             VALUES (?, ?, ?, ?, ?, 'sent', NOW(), NOW(), 'none')";
                $stmt = $this->connection->prepare($log_query);
                $stmt->bind_param("iisss", $game_data['owner_id'], $game_data['id'], $subject, $message, $game_data['email']);
                $stmt->execute();
            }
    
            $this->connection->commit();
            return ['message' => "Borrow request sent successfully!"];
    
        } catch (Exception $e) {
            $this->connection->rollback();
            error_log("Borrow request error: " . $e->getMessage());
            return ["Request failed"];
        }
    }

    public function handleRequest($request) {
        $action = $request['action'] ?? null;
        $selected_game = $request['selected_game'] ?? null;

        if (!isset($_SESSION['chat_state'])) {
            $_SESSION['chat_state'] = "start";
        }

        $response = [
            "message" => "",
            "options" => []
        ];

        switch ($_SESSION['chat_state']) {
            case "start":
                $response['message'] = "Hi! You can borrow or exchange games. What would you like to do?";
                $response['options'] = ["Borrow", "Exchange"];
                $_SESSION['chat_state'] = "select_action";
                break;

            case "select_action":
                if ($action === "Borrow") {
                    $borrowable_games = $this->getBorrowableGames();
                    if (!empty($borrowable_games['games'])) {
                        $response['message'] = "Here are available games for borrowing:";
                        $response['options'] = $borrowable_games['games'];
                        $_SESSION['game_map'] = $borrowable_games['gameMap'];
                        $_SESSION['chat_state'] = "borrow_game";
                    } else {
                        $response['message'] = "Sorry, no games are currently available for borrowing.";
                        $_SESSION['chat_state'] = "start";
                    }
                } elseif ($action === "Exchange") {
                    $user_games = $this->getUserGamesForExchange();
                    if (!empty($user_games['games'])) {
                        $response['message'] = "Select a game you want to offer for exchange:";
                        $response['options'] = $user_games['games'];
                        $_SESSION['offered_game_map'] = $user_games['gameMap'];
                        $_SESSION['chat_state'] = "select_offered_game";
                    } else {
                        $response['message'] = "You don't have any games to offer for exchange.";
                        $_SESSION['chat_state'] = "start";
                    }
                }
                break;

            case "borrow_game":
                $response = $this->processBorrowRequest($selected_game, $_SESSION['game_map']);
                $_SESSION['chat_state'] = "start";
                break;

            case "select_offered_game":
                $borrowable_games = $this->getBorrowableGames();
                if (!empty($borrowable_games['games'])) {
                    $response['message'] = "Select a game you want to receive:";
                    $response['options'] = $borrowable_games['games'];
                    $_SESSION['requested_game_map'] = $borrowable_games['gameMap'];
                    $_SESSION['offered_game'] = $selected_game;
                    $_SESSION['chat_state'] = "select_requested_game";
                } else {
                    $response['message'] = "Sorry, no games are currently available for exchange.";
                    $_SESSION['chat_state'] = "start";
                }
                break;

            default:
                $response['message'] = "Something went wrong. Please start over.";
                $_SESSION['chat_state'] = "start";
                break;
        }

        return $response;
    }
}

// API Handler Section
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $request = json_decode(file_get_contents('php://input'), true);
    $chatbot = new GameExchangeChatbot($connection, $_SESSION['user_id']);
    $response = $chatbot->handleRequest($request);
    echo json_encode($response);
    exit;
}

// Only output HTML for GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Game Exchange Chatbot</title>
        <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
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
         class GameExchangeChatbot {
            constructor() {
                this.messagesDiv = document.getElementById('messages');
                this.optionsDiv = document.getElementById('options');
                
                // Event listener for initial load
                document.addEventListener('DOMContentLoaded', () => this.sendMessage("Hello"));
            }

            // Add message to chat interface
            addMessage(type, text) {
                const messageDiv = document.createElement('div');
                messageDiv.className = `message ${type}`;
                messageDiv.textContent = text;
                this.messagesDiv.appendChild(messageDiv);
                this.scrollToBottom();
            }

            // Scroll messages to bottom
            scrollToBottom() {
                this.messagesDiv.scrollTop = this.messagesDiv.scrollHeight;
            }

            // Show interactive options
            showOptions(options) {
            this.optionsDiv.innerHTML = '';
            
            options.forEach(option => {
                const button = document.createElement('button');
                button.textContent = option;
                button.onclick = () => {
                    this.addMessage('user', option);
                    this.optionsDiv.innerHTML = '';
                    // Determine whether to send as 'action' or 'selectedGame'
                    if (option === 'Borrow' || option === 'Exchange') {
                        this.sendMessage(option); // Send 'option' as 'action'
                    } else {
                        this.sendMessage(null, option); // Send 'option' as 'selectedGame'
                    }
                };
                this.optionsDiv.appendChild(button);
            });
        }

            // Send message to server
            sendMessage(action = null, selectedGame = null) {
    if (!action && !selectedGame) {
        this.addMessage('bot', "Invalid selection. Please try again.");
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
            this.addMessage('bot', data.message);

            // Highlight successful messages
            if (data.message.includes('successfully')) {
                const lastMessage = this.messagesDiv.lastElementChild;
                lastMessage.classList.add('success');

                // Auto-reset after successful action
                setTimeout(() => this.sendMessage("Hello"), 2000);
            }

            // Show options if available
            if (data.options && data.options.length > 0) {
                this.showOptions(data.options);
            }
        }
    })
    .catch(error => console.error('Error:', error));
}
         }

        // Initialize chatbot
        const chatbot = new GameExchangeChatbot();
    </script>
</body>
</html>
<?php
}
?>
