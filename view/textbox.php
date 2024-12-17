<?php
session_start();
require_once '../db/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
// Fetch all users except current user
$query = "SELECT user_id, username, email, role, created_at, profile_picture 
          FROM game_users 
          WHERE user_id != ? 
          ORDER BY username ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$connections = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameLink Chat</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

        body, html {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #0f0c29, #24243e, #302b63);
            color: #fff;
            line-height: 1.6;
            min-height: 100vh;
        }

        /* Chat specific styles */
        .chat-layout {
            max-width: 1200px;
            margin: 20px auto;
            display: flex;
            gap: 20px;
            height: 80vh;
            padding: 20px;
        }

        .contacts-sidebar {
            width: 300px;
            background: linear-gradient(145deg, rgba(29, 32, 43, 0.8), rgba(16, 18, 27, 0.9));
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            padding: 20px;
            overflow-y: auto;
        }

        .contact-card {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 20px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .contact-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.05);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            border-color: rgba(255, 51, 102, 0.2);
        }

        .chat-window {
            flex-grow: 1;
            background: linear-gradient(145deg, rgba(29, 32, 43, 0.8), rgba(16, 18, 27, 0.9));
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            background: linear-gradient(90deg, rgba(255, 51, 102, 0.1), rgba(255, 107, 107, 0.1));
        }

        .messages-container {
            flex-grow: 1;
            overflow-y: auto;
            padding: 20px;
        }

        .message {
            margin: 10px 0;
            padding: 10px 15px;
            border-radius: 15px;
            max-width: 70%;
        }

        .message.sent {
            background: linear-gradient(45deg, #FF3366, #FF6B6B);
            margin-left: auto;
        }

        .message.received {
            background: rgba(255, 255, 255, 0.1);
            margin-right: auto;
        }

        .message-input {
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .message-input textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
            resize: none;
        }

        .message-input button {
            padding: 12px 20px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(45deg, #FF3366, #FF6B6B);
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .message-input button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 51, 102, 0.3);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="chat-layout">

            <div class="chat-window" id="chat-window">
                <div class="chat-header" id="chat-header">
                    <h2>Select a contact to start chatting</h2>
                </div>
                <div class="messages-container" id="messages-container"></div>
                <form class="message-input" id="message-form" style="display: none;">
                    <textarea placeholder="Type your message..." rows="2" required></textarea>
                    <button type="submit">Send</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        let activeContactId = null;

        async function loadChat(contactId) {
            activeContactId = contactId;
            const messagesContainer = document.getElementById('messages-container');
            const messageForm = document.getElementById('message-form');
            
            try {
                const response = await fetch(`../actions/receivemessage.php?contact_id=${contactId}`);
                const data = await response.json();
                
                if (data.success && data.messages) {
                    // Update messages display
                    messagesContainer.innerHTML = data.messages.map(msg => `
                        <div class="message ${msg.sender_id == <?php echo $_SESSION['user_id']; ?> ? 'sent' : 'received'}">
                            <div class="message-content">${msg.message}</div>
                            <small>${new Date(msg.created_at).toLocaleTimeString()}</small>
                        </div>
                    `).join('');
                    
                    messageForm.style.display = 'flex';
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }

        // Handle message sending
        document.getElementById('message-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!activeContactId) return;

            const textarea = e.target.querySelector('textarea');
            const message = textarea.value.trim();
            if (!message) return;

            try {
                const response = await fetch('../actions/sendmessage.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        receiver_id: activeContactId,
                        message: message
                    })
                });

                if (response.ok) {
                    textarea.value = '';
                    loadChat(activeContactId); // Refresh chat after sending
                }
            } catch (error) {
                console.error('Error:', error);
            }
        });

        // Auto-load chat if receiver_id is in URL
        const urlParams = new URLSearchParams(window.location.search);
        const receiverId = urlParams.get('receiver_id');
        if (receiverId) {
            loadChat(parseInt(receiverId));
        }
    </script>
</body>
</html>