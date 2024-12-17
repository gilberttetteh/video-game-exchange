<?php
session_start();
require_once '../db/db.php';
require_once '../functions/email_notifications.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../view/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;

    $conn->begin_transaction();
    try {
        switch($action) {
            case 'request':
                $game_id = intval($_POST['game_id']);
                $offered_game_id = intval($_POST['offered_game_id']);
                $lender_id = intval($_POST['lender_id']);

                // Get game and user details
                $stmt = $conn->prepare("
                    SELECT g.title, u.email, u.username 
                    FROM games g 
                    JOIN users u ON g.user_id = u.id 
                    WHERE g.id = ?
                ");
                $stmt->bind_param("i", $game_id);
                $stmt->execute();
                $game_result = $stmt->get_result()->fetch_assoc();

                // Create game request
                $stmt = $conn->prepare("
                    INSERT INTO game_requests 
                    (borrower_id, lender_id, game_id, requested_game_id, request_type, status, request_date) 
                    VALUES (?, ?, ?, ?, 'borrow', 'pending', NOW())
                ");
                $stmt->bind_param("iiii", $user_id, $lender_id, $game_id, $offered_game_id);
                $stmt->execute();

                // Send email notification
                $content = "<h2>New Game Request</h2>
                           <p>Hello " . htmlspecialchars($game_result['username']) . ",</p>
                           <p>" . htmlspecialchars($_SESSION['username']) . " would like to borrow your game: <strong>" . htmlspecialchars($game_result['title']) . "</strong></p>
                           <p>Please login to respond to this request.</p>";

                $message = get_email_template($content);
                $subject = "New Game Request - GameLink";
                send_email($game_result['email'], $subject, $message);

                $conn->commit();
                header("Location: ../view/my_games.php");
                exit();

            case 'approve':
                // Approve game request
                $stmt = $conn->prepare("
                    UPDATE game_requests 
                    SET status = 'approved' 
                    WHERE request_id = ?
                ");
                $stmt->bind_param("i", $request_id);
                $stmt->execute();

                // Optionally, send email notification to borrower

                $conn->commit();
                header("Location: ../view/approve_transaction.php");
                exit();

            case 'reject':
                // Reject game request
                $stmt = $conn->prepare("
                    UPDATE game_requests 
                    SET status = 'rejected' 
                    WHERE request_id = ?
                ");
                $stmt->bind_param("i", $request_id);
                $stmt->execute();

                // Optionally, send email notification to borrower

                $conn->commit();
                header("Location: ../view/approve_transaction.php");
                exit();

            default:
                throw new Exception("Invalid action.");
        }
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Error processing request: " . $e->getMessage();
    }

    header("Location: " . (($action === 'request') ? '../view/my_games.php' : '../view/approve_transaction.php'));
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Trade Requests</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <style>
        body {
    background-color: #f0f2f5;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    margin: 0;
    padding: 0;
    color: #333;
}

.container {
    max-width: 800px;
    margin: 50px auto;
    padding: 20px;
}

h1 {
    text-align: center;
    color: #2c3e50;
    margin-bottom: 40px;
}

.transaction-card {
    background: #ffffff;
    border: 1px solid #ddd;
    border-radius: 10px;
    padding: 25px;
    margin-bottom: 20px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    transition: transform 0.2s, box-shadow 0.2s;
}

.transaction-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 12px rgba(0, 0, 0, 0.2);
}

.transaction-card h3 {
    margin-top: 0;
    color: #34495e;
}

.transaction-card p {
    margin: 10px 0;
    color: #555;
}

.status-badge {
    display: inline-block;
    padding: 5px 12px;
    border-radius: 12px;
    font-size: 0.85em;
    font-weight: bold;
    margin-top: 10px;
}

.status-pending {
    background-color: #f1c40f;
    color: #ffffff;
}

.status-approved {
    background-color: #2ecc71;
    color: #ffffff;
}

.status-rejected {
    background-color: #e74c3c;
    color: #ffffff;
}

form {
    display: inline-block;
    margin-top: 15px;
}

button {
    background-color: #3498db;
    color: #ffffff;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    cursor: pointer;
    font-size: 0.9em;
    transition: background-color 0.3s;
}

button:hover {
    background-color: #2980b9;
}

button[type="submit"].reject-btn {
    background-color: #e74c3c;
}

button[type="submit"].reject-btn:hover {
    background-color: #c0392b;
}

button[type="submit"].approve-btn {
    background-color: #2ecc71;
}

button[type="submit"].approve-btn:hover {
    background-color: #27ae60;
}

.error {
    background-color: #f8d7da;
    color: #721c24;
    border-left: 5px solid #f5c6cb;
    padding: 10px 15px;
    border-radius: 5px;
    margin-top: 20px;
}

@media (max-width: 600px) {
    .container {
        padding: 10px;
    }

    .transaction-card {
        padding: 15px;
    }

    button {
        width: 100%;
        margin-bottom: 10px;
    }

    form {
        display: block;
        width: 100%;
    }
}
    </style>
</head>
<body>
    <div class="container">
        <h1>Trade Requests</h1>
        <?php
        // Fetch pending game requests
        $stmt = $conn->prepare("
            SELECT gr.request_id, gr.game_id, gr.requested_game_id, gr.status, gr.request_date, 
                   g.title AS game_title, ug.username AS lender_username, ug.email AS lender_email
            FROM game_requests gr
            JOIN games g ON gr.game_id = g.id
            JOIN users ug ON gr.lender_id = ug.id
            WHERE gr.lender_id = ? AND gr.status = 'pending'
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $requests = $stmt->get_result();

        if ($requests->num_rows > 0) {
            while ($request = $requests->fetch_assoc()) {
                echo '<div class="transaction-card">';
                echo '<h3>' . htmlspecialchars($request['game_title']) . '</h3>';
                echo '<p>Requested by: ' . htmlspecialchars($request['borrower_id']) . '</p>';
                echo '<p>Date: ' . htmlspecialchars($request['request_date']) . '</p>';
                echo '<span class="status-badge status-pending">Pending</span>';
                echo '<form method="POST" action="trade_requests.php">';
                echo '<input type="hidden" name="action" value="approve">';
                echo '<input type="hidden" name="request_id" value="' . htmlspecialchars($request['request_id']) . '">';
                echo '<button type="submit">Approve</button>';
                echo '</form>';
                echo '<form method="POST" action="trade_requests.php">';
                echo '<input type="hidden" name="action" value="reject">';
                echo '<input type="hidden" name="request_id" value="' . htmlspecialchars($request['request_id']) . '">';
                echo '<button type="submit">Reject</button>';
                echo '</form>';
                echo '</div>';
            }
        } else {
            echo '<p>No pending trade requests.</p>';
        }

        if (isset($_SESSION['error'])) {
            echo '<p class="error">' . htmlspecialchars($_SESSION['error']) . '</p>';
            unset($_SESSION['error']);
        }
        ?>
    </div>
</body>
</html>