<?php
session_start();
require_once '../db/db.php';
require_once '../functions/email_notifications.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../view/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch pending game requests from game_requests table
$stmt = $conn->prepare("
    SELECT gr.request_id, gr.game_id, gr.requested_game_id, gr.status, gr.request_date, 
           g.title AS game_title, og.title AS offered_game_title, u.username AS borrower_username, u.email AS borrower_email
    FROM game_requests gr
    JOIN games g ON gr.game_id = g.id
    JOIN games og ON gr.requested_game_id = og.id
    JOIN users u ON gr.borrower_id = u.id
    WHERE gr.lender_id = ? AND gr.status = 'pending'
    ORDER BY gr.request_date DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$trades = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Approve Transaction</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/trade_requests.css">
</head>
<body>
    <div class="container">
        <h1>Approve Trade Requests</h1>
        <?php if (!empty($trades)): ?>
            <?php foreach ($trades as $trade): ?>
                <div class="transaction-card">
                    <div class="requested-game">
                        <h4>Requested Game:</h4>
                        <p><?php echo htmlspecialchars($trade['game_title']); ?></p>
                    </div>
                    
                    <div class="offered-game">
                        <h4>Offered Game:</h4>
                        <p><?php echo htmlspecialchars($trade['offered_game']); ?></p>
                    </div>
                    
                    <div class="action-buttons">
                        <form method="POST" action="../actions/trade_requests.php">
                            <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($trade['request_id']); ?>">
                            <button type="submit" name="action" value="approve" class="btn btn-approve">
                                <i class='bx bx-check'></i> Approve
                            </button>
                            <button type="submit" name="action" value="reject" class="btn btn-reject">
                                <i class='bx bx-x'></i> Reject
                            </button>
                        </form>
                    </div>
                    
                    <div class="status">
                        <span class="status-badge status-<?php echo htmlspecialchars($trade['status']); ?>">
                            <?php echo ucfirst(htmlspecialchars($trade['status'])); ?>
                        </span>
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