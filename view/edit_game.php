<?php
session_start();

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database connection
require_once '../db/db.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please log in to edit games.";
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
$user_id = $_SESSION['user_id'];
$is_admin = ($_SESSION['role'] === 1); // Assuming role 1 is Admin

// Check if the user is authorized to edit the game
if (!$is_admin) {
    // Ensure the user owns the game
    $auth_query = "SELECT game_id FROM game_games WHERE game_id = ? AND user_id = ?";
    $stmt = $conn->prepare($auth_query);
    $stmt->bind_param("ii", $game_id, $user_id);
    $stmt->execute();
    $auth_result = $stmt->get_result();

    if ($auth_result->num_rows === 0) {
        $_SESSION['error'] = "Access denied. You can only edit your own games.";
        header("Location: explore_games.php");
        exit();
    }
    $stmt->close();
}

// Fetch game details
$query = "SELECT * FROM game_games WHERE game_id = ?";
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
$stmt->close();

// Handle form submission for updating game details
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $platform = trim($_POST['platform']);
    $genre = trim($_POST['genre']);

    // Validate inputs
    if (empty($title) || empty($description) || empty($platform) || empty($genre)) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: edit_game.php?game_id=$game_id");
        exit();
    }

    // Handle image upload
    $imageData = $game['image']; // Retain the current image by default
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileType = $_FILES['image']['type'];

        if (!in_array($fileType, $allowedTypes)) {
            $_SESSION['error'] = "Invalid image format. Only JPG, PNG, and GIF are allowed.";
            header("Location: edit_game.php?game_id=$game_id");
            exit();
        }

        // Read new image data
        $imageData = file_get_contents($_FILES['image']['tmp_name']);
    }

    // Update the game in the database
    $update_query = "UPDATE game_games SET title = ?, description = ?, platform = ?, genre = ?, image = ? WHERE game_id = ?";
    $stmt = $conn->prepare($update_query);
    
    // Bind parameters (notice 'b' for BLOB)
    $stmt->bind_param("ssssbi", $title, $description, $platform, $genre, $null, $game_id);
    $stmt->send_long_data(4, $imageData); // Send BLOB data

    if ($stmt->execute()) {
        $_SESSION['success'] = "Game updated successfully.";
        header("Location: explore_games.php");
        exit();
    } else {
        $_SESSION['error'] = "Error updating game: " . $stmt->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Game</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #1a1b2e;
            color: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 2rem;
        }
        .container {
            width: 100%;
            max-width: 700px;
            background: #242640;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }
        h1 {
            text-align: center;
            margin-bottom: 1.5rem;
            color: #4caf50;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        input, textarea {
            width: 100%;
            padding: 0.8rem;
            margin-bottom: 1.5rem;
            border: none;
            border-radius: 8px;
            background: #1a1b2e;
            color: #fff;
        }
        button {
            width: 100%;
            padding: 1rem;
            background: #4caf50;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        button:hover {
            background: #45a049;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Edit Game</h1>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert" style="color: #f44336;"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert" style="color: #4caf50;"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <form action="edit_game.php?game_id=<?php echo $game_id; ?>" method="POST" enctype="multipart/form-data">
        <label for="title">Game Title</label>
        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($game['title']); ?>" required>

        <label for="description">Description</label>
        <textarea id="description" name="description" rows="4" required><?php echo htmlspecialchars($game['description']); ?></textarea>

        <label for="platform">Platform</label>
        <input type="text" id="platform" name="platform" value="<?php echo htmlspecialchars($game['platform']); ?>" required>

        <label for="genre">Genre</label>
        <input type="text" id="genre" name="genre" value="<?php echo htmlspecialchars($game['genre']); ?>" required>

        <label for="image">Game Image (Optional)</label>
        <input type="file" id="image" name="image" accept="image/*">

        <button type="submit">Update Game</button>
    </form>
</div>
</body>
</html>
