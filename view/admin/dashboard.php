<?php
// Start the session
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// User verification
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 2) {
    header("Location: ../login.php");
    exit;
}

// Include the database connection file
require_once '../../db/db.php';


// Ensure the database connection works
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Fetch user-specific data
$user_id = $_SESSION['user_id'];

try {
    // Fetch user statistics
    $stmt = $conn->prepare("
        SELECT 
            (SELECT COUNT(*) FROM game_games WHERE user_id = ?) AS total_uploaded_games,
            (SELECT COUNT(*) FROM game_trades WHERE requester_id = ?) AS total_requests_made,
            (SELECT COUNT(*) FROM game_trades WHERE owner_id = ? AND status = 'completed') AS successful_trades
    ");
    $stmt->bind_param("iii", $user_id, $user_id, $user_id);
    $stmt->execute();
    $user_stats = $stmt->get_result()->fetch_assoc();

    // Fetch active games
    $stmt = $conn->prepare("
        SELECT 
            game_id, title, platform, status, created_at 
        FROM game_games 
        WHERE user_id = ? AND status = 'available'
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $active_games = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Fetch recent activity
    $stmt = $conn->prepare("
        SELECT 
            'trade_request' AS activity_type,
            t.requested_at AS activity_time,
            g.title AS game_title
        FROM game_trades t
        JOIN game_games g ON t.game_id = g.game_id
        WHERE t.requester_id = ?
        UNION ALL
        SELECT 
            'game_upload' AS activity_type,
            g.created_at AS activity_time,
            g.title AS game_title
        FROM game_games g
        WHERE g.user_id = ?
        ORDER BY activity_time DESC
        LIMIT 10
    ");
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();
    $recent_activity = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    echo "An error occurred. Please try again later.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    margin: 0;
    padding: 0;
    background: #f0f2f5;
    color: #2c3e50;
    min-height: 100vh;
}

/* Header and Navigation Styles */
header {
    background: #ffffff;
    padding: 0;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    position: fixed;
    width: 100%;
    top: 0;
    z-index: 1000;
}

.header-container {
    max-width: 1400px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 2rem;
    height: 70px;
}

.logo {
    font-size: 1.8rem;
    font-weight: bold;
    color: #2c3e50;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 10px;
}

.logo i {
    color: #3498db;
    font-size: 2rem;
}

nav {
    height: 100%;
}

nav ul {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    height: 100%;
    gap: 5px;
}

nav ul li {
    height: 100%;
}

nav ul li a {
    height: 100%;
    display: flex;
    align-items: center;
    padding: 0 1.5rem;
    color: #505965;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    position: relative;
}

nav ul li a:hover {
    color: #3498db;
    background: rgba(52, 152, 219, 0.1);
}

nav ul li a.active {
    color: #3498db;
}

nav ul li a.active::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 3px;
    background: #3498db;
}

/* Responsive Menu Button */
.menu-toggle {
    display: none;
    background: none;
    border: none;
    color: #2c3e50;
    font-size: 1.5rem;
    cursor: pointer;
}

/* Navigation Icons */
nav ul li a i {
    margin-right: 8px;
    font-size: 1.2rem;
}

@media (max-width: 768px) {
    .menu-toggle {
        display: block;
    }

    nav ul {
        display: none;
        position: absolute;
        top: 70px;
        left: 0;
        width: 100%;
        background: white;
        flex-direction: column;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    nav ul.show {
        display: flex;
    }

    nav ul li {
        height: auto;
    }

    nav ul li a {
        padding: 1rem 2rem;
        border-bottom: 1px solid #eee;
    }

    nav ul li a.active::after {
        display: none;
    }

    nav ul li a.active {
        background: rgba(52, 152, 219, 0.1);
    }
}

/* Welcome Section */
.welcome-section {
    background: linear-gradient(135deg, #3498db, #2980b9);
    color: white;
    padding: 2rem;
    border-radius: 15px;
    margin-bottom: 2rem;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 15px;
    text-align: center;
    transition: transform 0.3s ease;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-card h3 {
    color: #7f8c8d;
    font-size: 1rem;
    margin-bottom: 0.5rem;
}

.stat-card p {
    color: #2c3e50;
    font-size: 2rem;
    font-weight: bold;
    margin: 0;
}

.stat-card i {
    font-size: 2.5rem;
    color: #3498db;
    margin-bottom: 1rem;
}

/* Sections */
.section {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.section h2 {
    color: #2c3e50;
    font-size: 1.5rem;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

/* Tables */
table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    margin-top: 1rem;
}

th {
    background: #f8f9fa;
    color: #2c3e50;
    font-weight: 600;
    padding: 1rem;
    text-align: left;
}

td {
    padding: 1rem;
    border-bottom: 1px solid #eee;
    color: #2c3e50;
}

tr:hover {
    background: #f8f9fa;
}

/* Status Badges */
.status-badge {
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
}

.status-available {
    background: #e8f5e9;
    color: #2e7d32;
}

.status-pending {
    background: #fff3e0;
    color: #ef6c00;
}

/* Action Buttons */
.action-button {
    padding: 0.6rem 1.2rem;
    border-radius: 8px;
    border: none;
    background: #3498db;
    color: white;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.action-button:hover {
    background: #2980b9;
    transform: translateY(-2px);
}

/* Activity Feed */
.activity-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border-bottom: 1px solid #eee;
}

.activity-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #e3f2fd;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #3498db;
}
    </style>
</head>
<body>
<header>
    <div class="header-container">
        <a href="dashboard.php" class="logo">
            <i class='bx bx-game'></i>
            GameLink
        </a>
        
        <button class="menu-toggle" id="menuToggle">
            <i class='bx bx-menu'></i>
        </button>

        <nav>
            <ul id="mainMenu">
                <li><a href="dashboard.php" class="active">
                    <i class='bx bx-grid-alt'></i>
                    Dashboard
                </a></li>
                <li><a href="../my_games.php">
                    <i class='bx bx-joystick'></i>
                    My Games
                </a></li>
                <li><a href="../explore_games.php">
                    <i class='bx bx-joystick'></i>
                    Explore All Games
                </a></li>
                <li><a href="../chatbot.php">
                <i class='bx bx-bot'></i>
                Jarvis
                </a></li>
                <li><a href="../trade_history.php">
                    <i class='bx bx-transfer'></i>
                    Trade History
                </a></li>
                <li><a href="../../actions/logout.php">
                    <i class='bx bx-log-out'></i>
                    Logout
                </a></li>
            </ul>
        </nav>
    </div>
</header>

<!-- Add this JavaScript for mobile menu toggle -->
<script>
document.getElementById('menuToggle').addEventListener('click', function() {
    document.getElementById('mainMenu').classList.toggle('show');
});
</script>

    <div class="content">
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></h1>

        <div class="stats-grid">
        <div class="stat-card">
    <i class='bx bx-game'></i>
    <h3>Total Uploaded Games</h3>
    <p><?php echo $user_stats['total_uploaded_games']; ?></p>
</div>
<div class="stat-card">
    <i class='bx bx-transfer'></i>
    <h3>Trade Requests Made</h3>
    <p><?php echo $user_stats['total_requests_made']; ?></p>
</div>
<div class="stat-card">
    <i class='bx bx-check-circle'></i>
    <h3>Successful Trades</h3>
    <p><?php echo $user_stats['successful_trades']; ?></p>
</div>
        </div>

        <div class="section">
            <h2>Active Games</h2>
            <table>
                <thead>
                    <tr>
                        <th>Game Title</th>
                        <th>Platform</th>
                        <th>Status</th>
                        <th>Uploaded On</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($active_games as $game): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($game['title']); ?></td>
                            <td><?php echo htmlspecialchars($game['platform']); ?></td>
                            <td><?php echo htmlspecialchars($game['status']); ?></td>
                            <td><?php echo htmlspecialchars($game['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="section">
            <h2>Recent Activity</h2>
            <table>
                <thead>
                    <tr>
                        <th>Activity Type</th>
                        <th>Game Title</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_activity as $activity): ?>
                        <tr>
                            <td><?php echo ucfirst($activity['activity_type']); ?></td>
                            <td><?php echo htmlspecialchars($activity['game_title']); ?></td>
                            <td><?php echo htmlspecialchars($activity['activity_time']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
