<?php
// Include the database connection configuration
include '../db/db.php'; 

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session to retrieve the logged-in user's ID
session_start();
if (!isset($_SESSION['user_id'])) {
    die('You must be logged in to view your games.');
}

// Get the current user's ID from the session
$currentUserID = $_SESSION['user_id'];

// Query to fetch games created by the current user
$sql = "SELECT game_id AS id, title, genre, description FROM game_games WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $currentUserID);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="../assets/images/game-icon.ico">
    <title>My Games</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>*/
/* Reset and Base Styles */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body, html {
    margin: 0;
    padding: 0;
    min-height: 100vh;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    color: #fff;
}

.background-container {
    min-height: 100vh;
    background: linear-gradient(135deg, #1a1b2e, #16213e, #0f3460, #533483);
    background-size: 400% 400%;
    animation: gradient 15s ease infinite;
}

@keyframes gradient {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

/* Header Styles */
header {
    background: rgba(36, 38, 64, 0.95);
    backdrop-filter: blur(10px);
    padding: 0.5rem 2rem;
    position: fixed;
    width: 100%;
    top: 0;
    z-index: 1000;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.logo a {
    font-size: 2rem;
    font-weight: 700;
    color: #fff;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 10px;
    background: linear-gradient(45deg, #4caf50, #45a049);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    text-shadow: 0 2px 10px rgba(76, 175, 80, 0.3);
}

.logo a i {
    font-size: 2.2rem;
    color: #4caf50;
}

nav {
    display: flex;
    align-items: center;
}

nav ul {
    display: flex;
    gap: 1rem;
    list-style: none;
    margin: 0;
    padding: 0;
}

nav ul li a {
    color: #a8abbe;
    text-decoration: none;
    padding: 0.8rem 1.2rem;
    border-radius: 8px;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 500;
}

nav ul li a:hover {
    color: #4caf50;
    background: rgba(76, 175, 80, 0.1);
    transform: translateY(-2px);
}

nav ul li a.active {
    color: #4caf50;
    background: rgba(76, 175, 80, 0.1);
    position: relative;
}

nav ul li a.active::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 100%;
    height: 2px;
    background: #4caf50;
    border-radius: 2px;
}

/* Content Styles */
.content-container {
    max-width: 1400px;
    margin: 90px auto 2rem;
    padding: 2rem;
    position: relative;
}

.games-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 2rem;
    margin-top: 2rem;
}

/* Game Card Styles */
.game-card {
    background: rgba(36, 38, 64, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 15px;
    padding: 1.8rem;
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.game-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(45deg, #4caf50, #45a049);
}

.game-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.3);
    border-color: rgba(76, 175, 80, 0.3);
}

.game-header h3 {
    font-size: 1.5rem;
    margin-bottom: 1rem;
    color: #fff;
    font-weight: 600;
}

.game-description {
    color: #a8abbe;
    margin-bottom: 1.5rem;
    font-size: 0.95rem;
    line-height: 1.6;
}

/* Game Actions */
.game-actions {
    display: flex;
    gap: 1rem;
    margin-top: 1.5rem;
}

.edit-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 0.8rem 1.2rem;
    background: #4caf50;
    color: white;
    border: none;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    cursor: pointer;
    flex: 1;
}

.edit-button i {
    font-size: 1.2rem;
}

.edit-button:hover {
    background: #45a049;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
}

.delete-button {
    background: #f44336;
}

.delete-button:hover {
    background: #d32f2f;
    box-shadow: 0 4px 15px rgba(244, 67, 54, 0.3);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 3rem;
    background: rgba(36, 38, 64, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 15px;
    grid-column: 1 / -1;
    border: 1px solid rgba(255, 255, 255, 0.1);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1.5rem;
}

.empty-state i {
    font-size: 3.5rem;
    color: #4caf50;
}

.empty-state p {
    color: #a8abbe;
    font-size: 1.1rem;
    margin-bottom: 1rem;
}

/* Responsive Design */
@media (max-width: 1200px) {
    .content-container {
        padding: 1.5rem;
    }
}

@media (max-width: 768px) {
    header {
        padding: 0.5rem 1rem;
    }

    .logo a {
        font-size: 1.6rem;
    }

    nav ul {
        position: fixed;
        top: 70px;
        left: 0;
        width: 100%;
        background: rgba(36, 38, 64, 0.98);
        flex-direction: column;
        padding: 1rem;
        transform: translateY(-100%);
        transition: transform 0.3s ease;
    }

    nav ul.show {
        transform: translateY(0);
    }

    nav ul li a {
        padding: 1rem;
    }

    .content-container {
        margin-top: 80px;
        padding: 1rem;
    }

    .games-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }

    .game-card {
        padding: 1.5rem;
    }
}

/* Animation for Cards */
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

.game-card {
    animation: fadeIn 0.5s ease forwards;
}
    </style>
</head>
<body>
    <div class="background-container">
    <header>
    <div class="logo">
        <a href="dashboard.php">
            <i class='bx bx-game'></i>
            GameLink
        </a>
    </div>
    <nav>
        <ul>
            <li>
                <a href="admin/dashboard.php">
                    <i class='bx bx-grid-alt'></i>
                    Dashboard
                </a>
            </li>
            <li>
                <a href="my_games.php" class="active">
                    <i class='bx bx-joystick'></i>
                    My Games
                </a>
            </li>
            <li>
                <a href="add_games.php" class="active">
                    <i class='bx bx-joystick'></i>
                    Publish a Game
                </a>
            </li>
            <li>
                <a href="explore_games.php">
                    <i class='bx bx-search'></i>
                    Explore Games
                </a>
            </li>
            <li>
                <a href="trade_borrow.php">
                    <i class='bx bx-transfer'></i>
                    Trade Requests
                </a>
            </li>
            <li>
                <a href="../actions/logout.php">
                    <i class='bx bx-log-out'></i>
                    Logout
                </a>
            </li>
        </ul>
    </nav>
</header>

        <div class="content-container">
        <div class="games-grid">
    <?php
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            ?>
            <div class="game-card">
                <div class="game-header">
                    <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                </div>
                <div class="game-description">
                    <p><?php echo htmlspecialchars($row['description']); ?></p>
                </div>
                <div class="game-actions">
                    <a href="edit_game.php?id=<?php echo $row['id']; ?>" class="edit-button">
                        <i class='bx bx-edit'></i>
                        Edit Game
                    </a>
                    <button onclick="deleteGame(<?php echo $row['id']; ?>)" class="edit-button delete-button">
                        <i class='bx bx-trash'></i>
                        Delete
                    </button>
                </div>
            </div>
            <?php
        }
    } else {
        ?>
        <div class="empty-state">
            <i class='bx bx-game-off' style="font-size: 3rem; color: #4caf50;"></i>
            <p>You haven't added any games yet.</p>
            <a href="add_games.php" class="edit-button" style="margin-top: 1rem;">
                <i class='bx bx-plus-circle'></i>
                Add Your First Game
            </a>
        </div>
        <?php
    }
    ?>
</div>
        </div>
    </div>
    <script>
function deleteGame(gameId) {
    if (confirm('Are you sure you want to delete this game?')) {
        fetch('../actions/delete_game.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id=' + gameId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const gameElement = document.querySelector(`[data-game-id="${gameId}"]`);
                gameElement.remove();
                if (document.querySelectorAll('.game-card').length === 0) {
                    location.reload();
                }
            } else {
                alert('Error: ' + (data.message || 'Failed to delete game'));
            }
        })
        .catch(error => {
            alert('Error: ' + error);
        });
    }
}
</script>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>