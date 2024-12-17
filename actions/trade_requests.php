<?php
session_start();
require_once '../db/db.php';
require_once '../functions/email_functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../view/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $transaction_id = isset($_POST['transaction_id']) ? intval($_POST['transaction_id']) : 0;
    
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
                
                // Create transaction
                $stmt = $conn->prepare("
                    INSERT INTO game_transactions 
                    (requester_id, lender_id, requested_game_id, offered_game_id, status) 
                    VALUES (?, ?, ?, ?, 'pending')
                ");
                $stmt->bind_param("iiii", $user_id, $lender_id, $game_id, $offered_game_id);
                $stmt->execute();

                // Send notification
                sendTradeNotification($game_result['email'], 'new_request', [
                    'requester_name' => $_SESSION['username'],
                    'game_title' => $game_result['title']
                ]);

                $_SESSION['success'] = "Trade request sent successfully!";
                break;

            case 'approve':
                // Get transaction details
                $stmt = $conn->prepare("
                    SELECT t.*, g1.title as requested_game, g2.title as offered_game,
                           u.email as requester_email
                    FROM game_transactions t
                    JOIN games g1 ON t.requested_game_id = g1.id
                    JOIN games g2 ON t.offered_game_id = g2.id
                    JOIN users u ON t.requester_id = u.id
                    WHERE t.transaction_id = ? AND t.lender_id = ?
                ");
                $stmt->bind_param("ii", $transaction_id, $user_id);
                $stmt->execute();
                $transaction = $stmt->get_result()->fetch_assoc();

                // Update transaction
                $stmt = $conn->prepare("
                    UPDATE game_transactions 
                    SET status = 'approved', 
                        updated_at = CURRENT_TIMESTAMP 
                    WHERE transaction_id = ?
                ");
                $stmt->bind_param("i", $transaction_id);
                $stmt->execute();

                // Update game statuses
                $stmt = $conn->prepare("
                    UPDATE games 
                    SET status = 'traded',
                        traded_to = CASE 
                            WHEN user_id = ? THEN ?
                            ELSE ?
                        END
                    WHERE id IN (?, ?)
                ");
                $stmt->bind_param("iiiii", 
                    $transaction['requester_id'],
                    $user_id,
                    $transaction['requester_id'],
                    $transaction['requested_game_id'],
                    $transaction['offered_game_id']
                );
                $stmt->execute();

                // Send notification
                sendTradeNotification($transaction['requester_email'], 'approved', [
                    'requested_game' => $transaction['requested_game'],
                    'offered_game' => $transaction['offered_game']
                ]);

                $_SESSION['success'] = "Trade request approved successfully!";
                break;

            case 'reject':
                // Get transaction details
                $stmt = $conn->prepare("
                    SELECT t.*, u.email as requester_email
                    FROM game_transactions t
                    JOIN users u ON t.requester_id = u.id
                    WHERE t.transaction_id = ? AND t.lender_id = ?
                ");
                $stmt->bind_param("ii", $transaction_id, $user_id);
                $stmt->execute();
                $transaction = $stmt->get_result()->fetch_assoc();

                // Update status
                $stmt = $conn->prepare("
                    UPDATE game_transactions 
                    SET status = 'rejected',
                        updated_at = CURRENT_TIMESTAMP 
                    WHERE transaction_id = ?
                ");
                $stmt->bind_param("i", $transaction_id);
                $stmt->execute();

                // Send notification
                sendTradeNotification($transaction['requester_email'], 'rejected', [
                    'transaction_id' => $transaction_id
                ]);

                $_SESSION['success'] = "Trade request rejected successfully!";
                break;
        }
        
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Error processing request: " . $e->getMessage();
    }
    
    header("Location: " . ($action === 'request' ? '../view/my_games.php' : '../view/approve_transaction.php'));
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Trade Requests</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .transaction-card {
            background: linear-gradient(145deg, rgba(26, 27, 46, 0.9), rgba(20, 21, 36, 0.95));
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            color: #fff;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 12px;
            font-size: 0.9em;
            margin-left: 10px;
        }

        .status-pending { background: #ffd700; color: #000; }
        .status-approved { background: #4CAF50; }
        .status-rejected { background: #f44336; }

        .action-buttons {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }

        .btn-approve {
            background: linear-gradient(90deg, #4CAF50, #45a049);
        }

        .btn-reject {
            background: linear-gradient(90deg, #f44336, #da190b);
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: rgba(76, 175, 80, 0.2);
            border: 1px solid #4CAF50;
            color: #4CAF50;
        }

        .alert-error {
            background: rgba(244, 67, 54, 0.2);
            border: 1px solid #f44336;
            color: #f44336;
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

        <h2>Trade Requests</h2>
        
        <?php foreach ($transactions as $transaction): ?>
            <div class="transaction-card">
                <div class="transaction-header">
                    <h3>
                        <?php echo htmlspecialchars($transaction['requester_name']); ?>
                        <span class="status-badge status-<?php echo strtolower($transaction['status']); ?>">
                            <?php echo ucfirst($transaction['status']); ?>
                        </span>
                    </h3>
                </div>
                <div class="transaction-details">
                    <p><strong>Wants:</strong> <?php echo htmlspecialchars($transaction['requested_game']); ?></p>
                    <p><strong>Offers:</strong> <?php echo htmlspecialchars($transaction['offered_game']); ?></p>
                    <p><strong>Requested on:</strong> <?php echo date('M d, Y', strtotime($transaction['request_date'])); ?></p>
                </div>
                
                <?php if ($transaction['status'] === 'pending' && $transaction['lender_id'] === $user_id): ?>
                    <div class="action-buttons">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="transaction_id" value="<?php echo $transaction['transaction_id']; ?>">
                            <button type="submit" name="action" value="approve" class="btn btn-approve">
                                <i class='bx bx-check'></i> Approve
                            </button>
                            <button type="submit" name="action" value="reject" class="btn btn-reject">
                                <i class='bx bx-x'></i> Reject
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <?php if (empty($transactions)): ?>
            <p class="no-data">No trade requests found.</p>
        <?php endif; ?>
    </div>

    <script>
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.display = 'none';
            });
        }, 5000);
    </script>
</body>
</html>