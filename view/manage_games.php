<?php
session_start();

// Include database connection
require_once '../db/db.php'; // Adjust the path if needed

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 1) {
    die("Access denied: You must be an admin to manage games.");
}

// Handle form submissions for deleting a game
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_game'])) {
    $id = intval($_POST['game_id']);
    $stmt = $conn->prepare("DELETE FROM game_games WHERE game_id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo "Game deleted successfully.";
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}

// Fetch all games with user info
$games = [];
$query = "SELECT g.game_id, g.title, g.platform, g.genre, g.description, g.status, g.created_at, u.username AS added_by 
          FROM game_games g 
          JOIN game_users u ON g.user_id = u.user_id";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $games[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Games</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        body, html {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #fff;
            height: 100%;
            background: linear-gradient(135deg, #1a1a2e, #16213e, #0f3460, #533483);
            animation: gradient 15s ease infinite;
        }

        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
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
        .content-container {
            padding: 2rem;
            overflow-y: auto;
        }

        h1 {
            text-align: center;
            background: linear-gradient(45deg, #fff, #e0e0e0);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 2rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            overflow: hidden;
            margin-bottom: 2rem;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.9);
        }

        th {
            background: rgba(255, 255, 255, 0.05);
            text-transform: uppercase;
        }

        tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .action-buttons button {
            padding: 8px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .edit-btn {
            background: linear-gradient(45deg, #4facfe, #00f2fe);
            color: #1a1a2e;
        }

        .delete-btn {
            background: linear-gradient(45deg, #f45b5b, #ff4d4d);
            color: white;
        }

        .edit-btn:hover, .delete-btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
    </style>
</head>
<body>
<header>
<div class="header-container">
        <a href="/view/admin/admin_dashboard.php" class="logo">
            <i class='bx bx-game'></i>
            GameLink Admin
        </a>
        
        <nav>
            <ul>
                <li>
                    <a href="manage_users.php">
                        <i class='bx bx-user-circle'></i>
                        Manage Users
                    </a>
                </li>
                <li>
                    <a href="manage_games.php" class="active">
                        <i class='bx bx-game'></i>
                        Manage Games
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
    </div>
</header>

<div class="content-container">
    <div class="page-header">
        <h1>Manage Games</h1>
    </div>
    <?php if (count($games) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Platform</th>
                    <th>Genre</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Added By</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($games as $game): ?>
                    <tr>
                        <td><?= htmlspecialchars($game['game_id']) ?></td>
                        <td><?= htmlspecialchars($game['title']) ?></td>
                        <td><?= htmlspecialchars($game['platform']) ?></td>
                        <td><?= htmlspecialchars($game['genre']) ?></td>
                        <td><?= htmlspecialchars($game['description']) ?></td>
                        <td><?= htmlspecialchars($game['status']) ?></td>
                        <td><?= htmlspecialchars($game['added_by']) ?></td>
                        <td><?= htmlspecialchars($game['created_at']) ?></td>
                        <td class="action-buttons">
                            <form method="GET" action="edit_game.php">
                                <input type="hidden" name="game_id" value="<?= htmlspecialchars($game['game_id']) ?>">
                                <button type="submit" class="edit-btn">Edit</button>
                            </form>
                            <form method="POST">
                                <input type="hidden" name="game_id" value="<?= htmlspecialchars($game['game_id']) ?>">
                                <button type="submit" name="delete_game" class="delete-btn" onclick="return confirm('Are you sure?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No games found.</p>
    <?php endif; ?>
</div>
</body>
</html>
