<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include the database connection
require_once '../db/db.php'; // Ensure this path is correct

// Start session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please log in to view game details.";
    header("Location: login.php");
    exit();
}

// Check if game_id is provided
if (!isset($_GET['game_id']) || !is_numeric($_GET['game_id'])) {
    $_SESSION['error'] = "Invalid game ID.";
    header("Location: explore_games.php");
    exit();
}

$game_id = intval($_GET['game_id']);

// Fetch game details from the database
try {
    $query = "SELECT title, description, platform, genre, image, created_at 
              FROM game_games WHERE game_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $game_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $_SESSION['error'] = "Game not found.";
        header("Location: explore_games.php");
        exit();
    }

    $game = $result->fetch_assoc();
} catch (Exception $e) {
    $_SESSION['error'] = "An error occurred: " . $e->getMessage();
    header("Location: explore_games.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($game['title']); ?> - Details</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        :root {
            --primary: #4CAF50;
            --secondary: #2E7D32;
            --dark: #1C1C1C;
            --light: #FFFFFF;
            --gray: #9E9E9E;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--dark);
            color: var(--light);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            background-image: 
                radial-gradient(at 40% 20%, hsla(228,100%,74%,0.1) 0px, transparent 50%),
                radial-gradient(at 80% 0%, hsla(189,100%,56%,0.1) 0px, transparent 50%),
                radial-gradient(at 0% 50%, hsla(355,100%,93%,0.1) 0px, transparent 50%);
        }

        .container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 2rem;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        h1 {
            color: var(--primary);
            margin-bottom: 2rem;
        }

        .game-details {
            display: grid;
            gap: 2rem;
            grid-template-columns: 1fr 2fr;
        }

        .game-details img {
            width: 100%;
            height: auto;
            border-radius: 16px;
            object-fit: cover;
        }

        .game-details p {
            margin: 1rem 0;
            line-height: 1.6;
        }

        .game-details span {
            color: var(--primary);
            font-weight: 600;
        }

        .action-buttons {
            margin-top: 2rem;
            display: flex;
            gap: 1rem;
        }

        .action-buttons a {
            padding: 0.8rem 1.5rem;
            background: var(--primary);
            color: var(--light);
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .action-buttons a:hover {
            background: var(--secondary);
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .game-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo htmlspecialchars($game['title']); ?></h1>
        <div class="game-details">
            <?php if ($game['image']): ?>
                <img src="<?php echo htmlspecialchars($game['image']); ?>" alt="Game Image">
            <?php endif; ?>
            <div>
                <p><span>Description:</span> <?php echo htmlspecialchars($game['description']); ?></p>
                <p><span>Platform:</span> <?php echo htmlspecialchars($game['platform']); ?></p>
                <p><span>Genre:</span> <?php echo htmlspecialchars($game['genre']); ?></p>
                <p><span>Owner:</span> <?php echo htmlspecialchars($game['username']); ?></p>
                <p><span>Uploaded On:</span> <?php echo htmlspecialchars($game['created_at']); ?></p>
            </div>
        </div>
        <div class="action-buttons">
            <a href="explore_games.php"><i class='bx bx-arrow-back'></i> Back to Games</a>
        </div>
    </div>
</body>
</html>