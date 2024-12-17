<?php
// Start the session
session_start();

// Ensure the user is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 1) {
    header("Location: ../view/login.php");
    exit;
}

// Include the database connection
require_once('/home/gilbert.tetteh/public_html/VIDEO_GAME/db/db.php');

// Fetch admin data
try {
    // Total Users Count
    $query_total_users = "SELECT COUNT(*) AS total_users FROM game_users WHERE 1";
    $result_total_users = $conn->query($query_total_users);
    $total_users = $result_total_users->fetch_assoc()['total_users'];

    // Total Active Games
    $query_active_games = "SELECT COUNT(*) AS active_games FROM game_games WHERE status = 'available'";
    $result_active_games = $conn->query($query_active_games);
    $total_active_games = $result_active_games->fetch_assoc()['active_games'];

    // Recent User Registrations
    $query_recent_users = "
        SELECT user_id, username, email, created_at 
        FROM game_users
        ORDER BY created_at DESC 
        LIMIT 5
    ";
    $result_recent_users = $conn->query($query_recent_users);
    $recent_users = [];
    while ($row = $result_recent_users->fetch_assoc()) {
        $recent_users[] = $row;
    }

    // Recent Games Added
    $query_recent_games = "
        SELECT g.game_id, g.title, g.platform, g.created_at, u.username AS added_by
        FROM game_games g
        JOIN game_users u ON g.user_id = u.user_id
        ORDER BY g.created_at DESC
        LIMIT 5
    ";
    $result_recent_games = $conn->query($query_recent_games);
    $recent_games = [];
    while ($row = $result_recent_games->fetch_assoc()) {
        $recent_games[] = $row;
    }
} catch (Exception $e) {
    echo "Error fetching dashboard data: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameLink - Admin Dashboard</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        margin: 0;
        padding: 0;
        background: #1a1b2e;
        color: #fff;
        min-height: 100vh;
    }

    /* Header and Navigation Styles */
header {
    background: #242640;
    padding: 0;
    box-shadow: 0 2px 15px rgba(0,0,0,0.3);
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
    font-weight: 600;
    color: #fff;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 12px;
}

.logo i {
    color: #4caf50;
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
    color: #a8abbe;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    position: relative;
    gap: 8px;
}

nav ul li a:hover {
    color: #4caf50;
    background: rgba(76, 175, 80, 0.1);
}

nav ul li a.active {
    color: #4caf50;
}

nav ul li a.active::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 3px;
    background: #4caf50;
}

/* Responsive Menu Button */
.menu-toggle {
    display: none;
    background: none;
    border: none;
    color: #fff;
    font-size: 1.5rem;
    cursor: pointer;
}

/* Navigation Icons */
nav ul li a i {
    font-size: 1.2rem;
}

/* Admin Badge */
.admin-badge {
    background: rgba(76, 175, 80, 0.2);
    color: #4caf50;
    padding: 0.3rem 0.8rem;
    border-radius: 20px;
    font-size: 0.8rem;
    margin-left: 1rem;
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
        background: #242640;
        flex-direction: column;
        box-shadow: 0 2px 15px rgba(0,0,0,0.3);
    }

    nav ul.show {
        display: flex;
    }

    nav ul li {
        height: auto;
    }

    nav ul li a {
        padding: 1rem 2rem;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }

    nav ul li a.active::after {
        display: none;
    }

    nav ul li a.active {
        background: rgba(76, 175, 80, 0.1);
    }
}
    /* Stats Cards */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 25px;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: #242640;
        padding: 2rem;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        transition: transform 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-5px);
    }

    .stat-card i {
        font-size: 2.5rem;
        color: #4caf50;
        margin-bottom: 1rem;
    }

    .stat-card h3 {
        color: #a8abbe;
        font-size: 1.1rem;
        margin-bottom: 0.5rem;
    }

    .stat-card p {
        color: #fff;
        font-size: 2.5rem;
        font-weight: 600;
        margin: 0;
    }

    /* Action Buttons */
    .action-buttons {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin: 2rem 0;
    }

    .action-button {
        background: #242640;
        border: none;
        padding: 1.2rem;
        border-radius: 12px;
        color: #a8abbe;
        display: flex;
        align-items: center;
        gap: 12px;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }

    .action-button:hover {
        background: #2f3152;
        color: #4caf50;
        transform: translateY(-3px);
    }

    .action-button i {
        font-size: 1.5rem;
    }

    /* Tables */
    .section {
        background: #242640;
        border-radius: 15px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 4px 20px rgba(0,0,0,0.2);
    }

    .section h2 {
        color: #fff;
        font-size: 1.5rem;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }

    table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    th {
        background: #1a1b2e;
        color: #4caf50;
        font-weight: 600;
        padding: 1.2rem 1rem;
        text-align: left;
        border-bottom: 2px solid #4caf50;
    }

    td {
        padding: 1rem;
        color: #a8abbe;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }

    tr:hover td {
        background: rgba(255,255,255,0.05);
        color: #fff;
    }
    </style>
</head>
<body>
<header>
    <div class="header-container">
        <a href="dashboard.php" class="logo">
            <i class='bx bx-game'></i>
            GameLink
            <span class="admin-badge">Admin</span>
        </a>
        
        <button class="menu-toggle" id="menuToggle">
            <i class='bx bx-menu'></i>
        </button>

        <nav>
    <ul id="mainMenu">
        <li><a href="/~gilbert.tetteh/VIDEO_GAME/index.php">
            <i class='bx bx-home'></i>
            Home
        </a></li>
        <li><a href="/~gilbert.tetteh/VIDEO_GAME/about.php">
            <i class='bx bx-info-circle'></i>
            About
        </a></li>
        <li><a href="/~gilbert.tetteh/VIDEO_GAME/actions/trade_requests.php">
            <i class='bx bx-envelope'></i>
            Trade Requests
        </a></li>
        <li><a href="/~gilbert.tetteh/VIDEO_GAME/view/explore_games.php">
            <i class='bx bx-joystick'></i>
            Explore Games
        </a></li>
        <li><a href="/~gilbert.tetteh/VIDEO_GAME/view/admin/admin_dashboard.php" class="active">
            <i class='bx bx-grid-alt'></i>
            Dashboard
        </a></li>
        <li><a href="/~gilbert.tetteh/VIDEO_GAME/actions/logout.php">
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
        <div class="dashboard-welcome">
            <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></h1>
        </div>

        <div class="stats-grid">
        <div class="stat-card">
    <i class='bx bx-user'></i>
    <h3>Total Users</h3>
    <p><?php echo $total_users; ?></p>
</div>
<div class="stat-card">
    <i class='bx bx-game'></i>
    <h3>Active Games</h3>
    <p><?php echo $total_active_games; ?></p>
</div>
            <div class="action-buttons">
        <a href="../manage_users.php" class="action-button">
            <i class='bx bx-user-circle'></i>
            Manage Users
        </a>
        <a href="../manage_games.php" class="action-button">
            <i class='bx bx-game'></i>
            Manage Games
        </a>
</div>
        </div>

        <div class="section">
            <h2>Recent User Registrations</h2>
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Registration Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="section">
            <h2>Recently Added Games</h2>
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Platform</th>
                        <th>Added By</th>
                        <th>Added On</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_games as $game): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($game['title']); ?></td>
                            <td><?php echo htmlspecialchars($game['platform']); ?></td>
                            <td><?php echo htmlspecialchars($game['added_by']); ?></td>
                            <td><?php echo htmlspecialchars($game['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
