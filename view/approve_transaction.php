<?php
session_start();
require_once '../db/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch pending requests
$stmt = $conn->prepare("
    SELECT t.*, 
           g1.title as requested_game,
           g2.title as offered_game,
           u.username as requester_name,
           u.email as requester_email,
           t.created_at,
           t.status
    FROM game_transactions t
    JOIN games g1 ON t.requested_game_id = g1.id
    JOIN games g2 ON t.offered_game_id = g2.id
    JOIN users u ON t.requester_id = u.id
    WHERE t.lender_id = ? AND t.status = 'pending'
    ORDER BY t.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$trades = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Approve Trade Requests</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .trade-request {
            background: linear-gradient(145deg, rgba(26, 27, 46, 0.9), rgba(20, 21, 36, 0.95));
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.3s ease;
        }

        .trade-request:hover {
            transform: translateY(-5px);
        }

        .request-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .game-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 15px 0;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .btn-approve {
            background: linear-gradient(90deg, #4CAF50, #45a049);
        }

        .btn-reject {
            background: linear-gradient(90deg, #f44336, #da190b);
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <h2>Pending Trade Requests</h2>
        
        <?php if (!empty($trades)): ?>
            <?php foreach ($trades as $trade): ?>
                <div class="trade-request">
                    <div class="request-header">
                        <h3><?php echo htmlspecialchars($trade['requester_name']); ?>'s Request</h3>
                        <span class="date"><?php echo date('M d, Y h:i A', strtotime($trade['created_at'])); ?></span>
                    </div>

                    <div class="game-details">
                        <div class="requested-game">
                            <h4>Wants Your Game:</h4>
                            <p><?php echo htmlspecialchars($trade['requested_game']); ?></p>
                        </div>
                        <div class="offered-game">
                            <h4>Offers Their Game:</h4>
                            <p><?php echo htmlspecialchars($trade['offered_game']); ?></p>
                        </div>
                    </div>

                    <div class="action-buttons">
                        <form method="POST" action="../actions/trade_requests.php">
                            <input type="hidden" name="transaction_id" value="<?php echo $trade['transaction_id']; ?>">
                            <button type="submit" name="action" value="approve" class="btn btn-approve">
                                <i class='bx bx-check'></i> Approve
                            </button>
                            <button type="submit" name="action" value="reject" class="btn btn-reject">
                                <i class='bx bx-x'></i> Reject
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="no-data">No pending trade requests.</p>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>