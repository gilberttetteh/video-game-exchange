<?php
session_start();
require_once '../db/db.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please login to request games.";
    header("Location: login.php");
    exit();
}
$requester_id = $_SESSION['user_id']; 

// Validate game_id from POST parameters
$game_id = isset($_POST['game_id']) ? (int)$_POST['game_id'] : 0;



// Fetch game details from the database
$game_query = "SELECT g.*, u.username, u.user_id AS owner_id FROM game_games g 
               JOIN game_users u ON g.user_id = u.user_id 
               WHERE g.game_id = ?";
$stmt = $conn->prepare($game_query);
$stmt->bind_param("i", $game_id);
$stmt->execute();
$result = $stmt->get_result();
$game = $result->fetch_assoc();

if (!$game) {
    $_SESSION['error'] = "The requested game does not exist.";
    header("Location: explore_games.php");
    exit();
}

$feedback = "";

// Extensive POST request handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Capture request details
    $borrower_id = $_SESSION['user_id']; // Assuming the borrower is the requester
    $lender_id = $game['owner_id'];
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $return_date = isset($_POST['return_date']) ? $_POST['return_date'] : '';
    $request_date = date('Y-m-d H:i:s'); // Current timestamp for request_date

    // Prevent users from borrowing or exchanging their own games
    if ($borrower_id === $lender_id) {
        $feedback = "You cannot borrow or exchange your own game.";
    } else {
        if ($action === 'borrow') {
            // Check if the game is already requested or borrowed
            $check_query = "SELECT * FROM game_transactions 
                            WHERE game_id = ? AND status IN ('pending', 'approved')";
            $stmt = $conn->prepare($check_query);
            $stmt->bind_param("i", $game_id);
            $stmt->execute();

            if ($stmt->get_result()->num_rows > 0) {
                $feedback = "This game is currently not available for borrowing.";
            } else {
                $offered_game_id = null; // No offered game for borrowing
                if (!empty($return_date)) {
                    $insert_query = "INSERT INTO game_transactions 
                                    (game_id, requested_game_id, lender_id, borrower_id, requester_id, offered_game_id, return_date, request_date, status) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
                    $stmt = $conn->prepare($insert_query);
                    $stmt->bind_param("iiiiisss", $game_id, $game_id, $lender_id, $borrower_id, $borrower_id, $offered_game_id, $return_date, $request_date);
                } else {
                    $default_return_date = date('Y-m-d', strtotime('+14 days'));
                    $insert_query = "INSERT INTO game_transactions 
                                    (game_id, requested_game_id, lender_id, borrower_id, requester_id, offered_game_id, return_date, request_date, status) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
                    $stmt = $conn->prepare($insert_query);
                    $stmt->bind_param("iiiiisss", $game_id, $game_id, $lender_id, $borrower_id, $borrower_id, $offered_game_id, $default_return_date, $request_date);
                }

                if ($stmt->execute()) {
                    $_SESSION['success'] = "Borrow request sent successfully.";
                    header("Location: explore_games.php");
                    exit();
                } else {
                    $feedback = "Error sending borrow request.";
                }
            }
        } elseif ($action === 'exchange') {
            // Handle exchange functionality
            $exchange_game_id = isset($_POST['exchange_game_id']) ? (int)$_POST['exchange_game_id'] : 0;

            // Validate user's game for exchange
            $user_game_query = "SELECT * FROM game_games WHERE game_id = ? AND user_id = ?";
            $stmt = $conn->prepare($user_game_query);
            $stmt->bind_param("ii", $exchange_game_id, $borrower_id);
            $stmt->execute();

            if ($stmt->get_result()->num_rows === 0) {
                $feedback = "Invalid game selected for exchange.";
            } else {
                $insert_query = "INSERT INTO game_transactions 
                                (game_id, requested_game_id, lender_id, borrower_id, requester_id, offered_game_id, exchange_game_id, request_date, status) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
                $offered_game_id = null; // Set to NULL if not explicitly provided
                $stmt = $conn->prepare($insert_query);
                $stmt->bind_param("iiiiiiis", $game_id, $game_id, $lender_id, $borrower_id, $borrower_id, $offered_game_id, $exchange_game_id, $request_date);

                if ($stmt->execute()) {
                    $_SESSION['success'] = "Exchange request sent successfully.";
                    header("Location: explore_games.php");
                    exit();
                } else {
                    $feedback = "Error sending exchange request.";
                }
            }
        } else {
            $feedback = "Invalid action.";
        }
    }
}

?>

<!DOCTYPE html>
<link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
<html>
<head>
    <title>Trade or Borrow Game</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        h2 {
            color: #333;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
        }
        input[type="date"], select {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .btn {
            display: inline-block;
            padding: 10px 15px;
            color: #fff;
            background-color: #007BFF;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            text-align: center;
            cursor: pointer;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .error, .success {
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 4px;
            color: #fff;
        }
        .error {
            background-color: #f44336;
        }
        .success {
            background-color: #4CAF50;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Request to Borrow or Exchange: <?php echo htmlspecialchars($game['title']); ?></h2>
        <?php if (!empty($feedback)): ?>
            <div class="error"><?php echo $feedback; ?></div>
        <?php endif; ?>
        
        <!-- Add a hidden input to confirm game_id is being passed -->
        <form method="POST" id="gameRequestForm" onsubmit="return validateForm()">
            <input type="hidden" name="game_id" value="<?php echo $game_id; ?>">
            
            <div class="form-group">
                <label>Action:</label>
                <select name="action" required>
                    <option value="borrow">Borrow</option>
                    <option value="exchange">Exchange</option>
                </select>
            </div>
            <div class="form-group">
                <label>Return Date:</label>
                <input type="date" name="return_date" required 
                       min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
            </div>
            <div class="form-group" id="exchange-game">
                <label>Select Your Game for Exchange (if applicable):</label>
                <select name="exchange_game_id">
                    <option value="">-- Select a Game --</option>
                    <?php
                    $user_games_query = "SELECT * FROM game_games WHERE user_id = ?";
                    $stmt = $conn->prepare($user_games_query);
                    $stmt->bind_param("i", $_SESSION['user_id']);
                    $stmt->execute();
                    $user_games_result = $stmt->get_result();
                    while ($user_game = $user_games_result->fetch_assoc()) {
                        echo "<option value='" . $user_game['game_id'] . "'>" . htmlspecialchars($user_game['title']) . "</option>";
                    }
                    ?>
                </select>
            </div>
            <button type="submit" class="btn">Submit Request</button>
            <a href="explore_games.php" class="btn" style="background-color: #6c757d;">Cancel</a>
        </form>
    </div>

    <script>
        function validateForm() {
            var action = document.querySelector('select[name="action"]').value;
            var returnDate = document.querySelector('input[name="return_date"]').value;
            var today = new Date().toISOString().split('T')[0];

            if (!action) {
                alert('Please select an action');
                return false;
            }

            if (!returnDate) {
                alert('Please select a return date');
                return false;
            }

            if (returnDate <= today) {
                alert('Return date must be in the future');
                return false;
            }

            if (action === 'exchange') {
                var exchangeGame = document.querySelector('select[name="exchange_game_id"]').value;
                if (!exchangeGame) {
                    alert('Please select a game for exchange');
                    return false;
                }
            }

            return true;
        }
        // Additional logging for form events
        document.getElementById('gameRequestForm').addEventListener('submit', function(e) {
            console.log('Form submission event triggered');
        });
    </script>
</body>
</html>