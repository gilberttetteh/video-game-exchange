<?php
session_start();
require_once '../db/db.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Base query
$base_query = "
    SELECT t.*, 
           g1.title as requested_game,
           g2.title as offered_game,
           u1.username as requester_name,
           u2.username as lender_name,
           t.status,
           t.request_date
    FROM game_transactions t
    JOIN game_games g1 ON t.requested_game_id = g1.game_id
    JOIN game_games g2 ON t.offered_game_id = g2.game_id
    JOIN game_users u1 ON t.requester_id = u1.user_id
    JOIN game_users u2 ON t.lender_id = u2.user_id";

if ($is_admin) {
    // Admin sees all trades
    $stmt = $conn->prepare($base_query . " ORDER BY t.request_date DESC");
} else {
    // Users see only their trades
    $stmt = $conn->prepare($base_query . " 
        WHERE t.requester_id = ? OR t.lender_id = ?
        ORDER BY t.request_date DESC");
    $stmt->bind_param("ii", $user_id, $user_id);
}

$stmt->execute();
$history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Trade History</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
    :root {
        --primary-color: #4facfe;
        --secondary-color: #00f2fe;
        --dark-bg: #1a1a2e;
        --card-bg: rgba(26, 27, 46, 0.95);
        --success-color: #4CAF50;
        --warning-color: #ffd700;
        --danger-color: #f44336;
        --text-primary: #ffffff;
        --text-secondary: rgba(255, 255, 255, 0.7);
        --border-color: rgba(255, 255, 255, 0.1);
    }

    body {
        background: linear-gradient(135deg, var(--dark-bg), #16213e);
        color: var(--text-primary);
        font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        line-height: 1.6;
        min-height: 100vh;
        margin: 0;
        padding: 0;
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem;
    }

    .header {
        background: linear-gradient(145deg, rgba(79, 172, 254, 0.1), rgba(0, 242, 254, 0.1));
        border-radius: 20px;
        padding: 2rem;
        margin-bottom: 3rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        backdrop-filter: blur(10px);
        border: 1px solid var(--border-color);
    }

    .header h2 {
        margin: 0;
        font-size: 2.5rem;
        background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
        font-weight: 700;
    }

    .btn {
        background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
        color: white;
        padding: 0.8rem 1.5rem;
        border-radius: 12px;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 500;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(79, 172, 254, 0.3);
    }

    .timeline {
        position: relative;
        padding: 2rem 0;
    }

    .timeline::before {
        content: '';
        position: absolute;
        left: 50px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: linear-gradient(to bottom, 
            rgba(79, 172, 254, 0.5),
            rgba(0, 242, 254, 0.5));
    }

    .timeline-item {
        padding: 1rem 1rem 1rem 100px;
        position: relative;
        margin-bottom: 2rem;
    }

    .timeline-item::before {
        content: '';
        position: absolute;
        left: 42px;
        top: 50%;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: var(--primary-color);
        border: 4px solid var(--dark-bg);
        transform: translateY(-50%);
        box-shadow: 0 0 20px rgba(79, 172, 254, 0.5);
    }

    .history-card {
        background: linear-gradient(145deg, var(--card-bg), rgba(20, 21, 36, 0.95));
        border: 1px solid var(--border-color);
        border-radius: 20px;
        padding: 2rem;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .history-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
    }

    .history-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    }

    .transaction-header {
        margin-bottom: 1.5rem;
    }

    .transaction-header h3 {
        font-size: 1.5rem;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .status-badge {
        padding: 0.5rem 1rem;
        border-radius: 30px;
        font-size: 0.9rem;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .status-pending {
        background: rgba(255, 215, 0, 0.2);
        color: var(--warning-color);
        border: 1px solid var(--warning-color);
    }

    .status-approved {
        background: rgba(76, 175, 80, 0.2);
        color: var(--success-color);
        border: 1px solid var(--success-color);
    }

    .status-rejected {
        background: rgba(244, 67, 54, 0.2);
        color: var(--danger-color);
        border: 1px solid var(--danger-color);
    }

    .transaction-details {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        color: var(--text-secondary);
    }

    .transaction-details p {
        margin: 0.5rem 0;
    }

    .transaction-details strong {
        color: var(--text-primary);
        font-weight: 600;
    }

    @media (max-width: 768px) {
        .container {
            padding: 1rem;
        }

        .header {
            flex-direction: column;
            gap: 1rem;
            text-align: center;
            padding: 1.5rem;
        }

        .timeline::before {
            left: 30px;
        }

        .timeline-item {
            padding-left: 70px;
        }

        .timeline-item::before {
            left: 22px;
        }

        .transaction-details {
            grid-template-columns: 1fr;
        }
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .timeline-item {
        animation: fadeIn 0.5s ease forwards;
        opacity: 0;
    }

    .timeline-item:nth-child(1) { animation-delay: 0.1s; }
    .timeline-item:nth-child(2) { animation-delay: 0.2s; }
    .timeline-item:nth-child(3) { animation-delay: 0.3s; }
    .timeline-item:nth-child(4) { animation-delay: 0.4s; }
    .timeline-item:nth-child(5) { animation-delay: 0.5s; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Trade History</h2>
            <a href="./admin/dashboard.php" class="btn">
                <i class='bx bx-arrow-back'></i> Back to Dashboard
            </a>
            <a href="trade_borrow.php" class="btn">
                <i class='bx bx-arrow-back'></i> Exchange Games
            </a>
        </div>

        <div class="timeline">
            <?php if (!empty($history)): ?>
                <?php foreach ($history as $transaction): ?>
                    <div class="timeline-item">
                        <div class="history-card">
                            <div class="transaction-header">
                                <h3>
                                    Trade Request 
                                    <span class="status-badge status-<?php echo strtolower($transaction['status']); ?>">
                                        <?php echo ucfirst($transaction['status']); ?>
                                    </span>
                                </h3>
                            </div>
                            <div class="transaction-details">
                                <p>
                                    <strong>Requester:</strong> 
                                    <?php echo htmlspecialchars($transaction['requester_name']); ?>
                                </p>
                                <p>
                                    <strong>Owner:</strong> 
                                    <?php echo htmlspecialchars($transaction['lender_name']); ?>
                                </p>
                                <p>
                                    <strong>Requested Game:</strong> 
                                    <?php echo htmlspecialchars($transaction['requested_game']); ?>
                                </p>
                                <p>
                                    <strong>Offered Game:</strong> 
                                    <?php echo htmlspecialchars($transaction['offered_game']); ?>
                                </p>
                                <p>
                                    <strong>Date:</strong> 
                                    <?php echo date('M d, Y h:i A', strtotime($transaction['request_date'])); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-history">
                    <p>No trade history found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>