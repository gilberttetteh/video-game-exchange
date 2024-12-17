<?php
// Start session
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database connection file
require_once '../db/db.php'; 

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit;
}

// Fetch all games from the database
$games = [];
$query = "SELECT 
            game_id, 
            user_id, 
            title, 
            platform, 
            genre, 
            image, 
            description, 
            status, 
            created_at
          FROM game_games
          ORDER BY created_at DESC";
$result = mysqli_query($connection, $query);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $games[] = $row;
    }
} else {
    echo "Error fetching games: " . mysqli_error($connection);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Explore Games</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
/* Base styles */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    background: #f5f7fa;
    color: #2c3e50;
    line-height: 1.6;
}

/* Header styles */
.header-container {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    background: #fff;
    z-index: 1000;
    padding: 1rem 2rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header-container h1 {
    font-size: 1.8rem;
    color: #2c3e50;
}

.menu {
    display: flex;
    gap: 1.5rem;
    align-items: center;
}

.menu a {
    text-decoration: none;
    color: #555;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.menu a:hover {
    background: #f0f2f5;
    color: #3498db;
}

/* Container layout */
.container {
    margin-top: 100px;
    padding: 2rem;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 2rem;
    max-width: 1400px;
    margin-left: auto;
    margin-right: auto;
}

/* Game card styles */
.game-card {
    display: flex;
    flex-direction: column;
    height: 450px;
    background: #fff;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    text-decoration: none;
    color: inherit;
}

.game-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.15);
}

.game-image {
    height: 200px;
    width: 100%;
    overflow: hidden;
}

.game-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.game-card:hover .game-image img {
    transform: scale(1.05);
}

.game-info {
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    flex: 1;
}

.game-info h3 {
    font-size: 1.5rem;
    margin-bottom: 1rem;
    color: #2c3e50;
    font-weight: 600;
}

.description {
    flex-grow: 1;
    font-size: 0.9rem;
    color: #666;
    margin-bottom: 1rem;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
}

.stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.5rem;
    padding-top: 1rem;
    border-top: 1px solid #eee;
    margin-top: auto;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    color: #555;
}

.stat-item i {
    font-size: 1.2rem;
    color: #3498db;
}

/* Empty state */
.empty-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 3rem;
    background: linear-gradient(135deg, #f6f8fb 0%, #f1f4f9 100%);
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}

/* Animations */
.bx {
    transition: transform 0.2s ease;
}

.stat-item:hover .bx {
    transform: scale(1.2);
}

/* Responsive design */
@media (max-width: 768px) {
    .header-container {
        padding: 1rem;
    }

    .container {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        padding: 1rem;
        margin-top: 80px;
        gap: 1rem;
    }
    
    .game-card {
        height: 400px;
    }
    
    .game-info h3 {
        font-size: 1.2rem;
    }
    
    .menu {
        gap: 1rem;
    }
    
    .menu a {
        padding: 0.4rem 0.8rem;
        font-size: 0.9rem;
    }
}

@media (max-width: 480px) {
    .container {
        grid-template-columns: 1fr;
    }
    
    .menu a span {
        display: none;
    }
}
    </style>
</head>
<body>
<header>
<header>
    <div class="header-container">
        <h1>Explore Games</h1>
        <nav class="menu">
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 1): ?>
                <a href="./admin/admin_dashboard.php"><i class='bx bx-grid-alt'></i>Dashboard</a>
            <?php else: ?>
                <a href="./admin/dashboard.php"><i class='bx bx-grid-alt'></i>Dashboard</a>
            <?php endif; ?>
            <a href="chatbot.php"><i class='bx bx-bot'></i>Jarvis</a>
            <a href="../actions/logout.php"><i class='bx bx-log-out'></i>Logout</a>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 1): ?>
                <a href="add_games.php"><i class='bx bx-plus-circle'></i>Add Game</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<div class="container">
    <?php if (!empty($games)): ?>
        <?php foreach ($games as $game): ?>
            <a href="view_game.php?id=<?php echo htmlspecialchars($game['game_id']); ?>" class="game-card">
                <?php if (!empty($game['image'])): ?>
                    <div class="game-image">
                        <img src="data:image/jpeg;base64,<?php echo base64_encode($game['image']); ?>" alt="<?php echo htmlspecialchars($game['title']); ?>">
                    </div>
                <?php endif; ?>
                <div class="game-info">
                    <h3><?php echo htmlspecialchars($game['title']); ?></h3>
                    <p class="description"><?php echo htmlspecialchars($game['description']); ?></p>
                    
                    <div class="stats">
                        <div class="stat-item">
                            <i class='bx bx-joystick'></i>
                            <span><?php echo htmlspecialchars($game['platform']); ?></span>
                        </div>
                        <div class="stat-item">
                            <i class='bx bx-category'></i>
                            <span><?php echo htmlspecialchars($game['genre']); ?></span>
                        </div>
                        <div class="stat-item">
                            <i class='bx bx-calendar'></i>
                            <span><?php echo htmlspecialchars($game['created_at']); ?></span>
                        </div>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-state">
            <i class='bx bx-game-off' style="font-size: 3rem; color: #4caf50;"></i>
            <p>No games available at the moment.</p>
        </div>
    <?php endif; ?>
</div>

      
</body>
</html>