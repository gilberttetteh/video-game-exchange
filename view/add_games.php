<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include the database connection
require_once '../db/db.php'; // Ensure this file sets up a $conn MySQLi connection

// Start session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please log in to add a game.";
    header("Location: login.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $platform = trim($_POST['platform'] ?? '');
    $genre = trim($_POST['genre'] ?? '');

    // Validate inputs
    if (empty($title) || empty($description) || empty($platform) || empty($genre)) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: add_games.php");
        exit();
    }

    // Handle image upload
    $imageData = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileType = $_FILES['image']['type'];

        if (!in_array($fileType, $allowedTypes)) {
            $_SESSION['error'] = "Invalid image format. Only JPG, PNG, and GIF are allowed.";
            header("Location: add_games.php");
            exit();
        }

        // Read image data into a binary format
        $imageData = file_get_contents($_FILES['image']['tmp_name']);
    }

    try {
        // Prepare the query to insert game details
        $query = "INSERT INTO game_games (title, description, platform, genre, image, user_id, created_at) 
                  VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($query);
        if ($stmt === false) {
            throw new Exception("Failed to prepare statement: " . $conn->error);
        }

        // Bind parameters
        $stmt->bind_param("sssssi", $title, $description, $platform, $genre, $imageData, $_SESSION['user_id']);

        // Execute the statement
        if ($stmt->execute()) {
            $_SESSION['success'] = "Game added successfully!";
            header("Location: explore_games.php");
            exit();
        } else {
            throw new Exception("Failed to execute statement: " . $stmt->error);
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error adding game: " . $e->getMessage();
        header("Location: add_games.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Game</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        /* Reset and Base Styles */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #1a1b2e;
    color: #fff;
    line-height: 1.6;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
}

/* Container Styles */
.container {
    width: 100%;
    max-width: 700px;
    background: #242640;
    border-radius: 20px;
    padding: 2.5rem;
    box-shadow: 0 8px 30px rgba(0,0,0,0.3);
    position: relative;
    overflow: hidden;
}

.container::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(45deg, #4caf50, #45a049);
}

/* Header Styles */
h1 {
    font-size: 2rem;
    font-weight: 600;
    margin-bottom: 2rem;
    text-align: center;
    color: #fff;
    position: relative;
    padding-bottom: 1rem;
}

h1::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 60px;
    height: 3px;
    background: #4caf50;
    border-radius: 2px;
}

/* Form Group Styles */
.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #a8abbe;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 1rem;
    border: 2px solid rgba(255,255,255,0.1);
    border-radius: 12px;
    background: #1a1b2e;
    color: #fff;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.form-group input:focus,
.form-group textarea:focus {
    border-color: #4caf50;
    outline: none;
    box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.2);
}

.form-group textarea {
    resize: vertical;
    min-height: 120px;
}

/* File Input Styling */
.form-group input[type="file"] {
    border: 2px dashed rgba(255,255,255,0.2);
    padding: 2rem 1rem;
    text-align: center;
    cursor: pointer;
    position: relative;
}

.form-group input[type="file"]::before {
    content: '📸 Choose an image';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: #a8abbe;
    pointer-events: none;
}

/* Submit Button */
.submit-btn {
    width: 100%;
    padding: 1rem;
    background: #4caf50;
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.submit-btn:hover {
    background: #45a049;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
}

/* Alert Messages */
.alert {
    padding: 1rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    font-weight: 500;
}

.alert-error {
    background: rgba(244, 67, 54, 0.1);
    color: #f44336;
    border: 1px solid rgba(244, 67, 54, 0.3);
}

.alert-success {
    background: rgba(76, 175, 80, 0.1);
    color: #4caf50;
    border: 1px solid rgba(76, 175, 80, 0.3);
}

/* Responsive Design */
@media (max-width: 768px) {
    body {
        padding: 1rem;
    }

    .container {
        padding: 1.5rem;
    }

    h1 {
        font-size: 1.5rem;
    }

    .form-group input,
    .form-group textarea {
        padding: 0.8rem;
    }
}
</style>
</head>
<body>
<div class="container">
        <h1>Add New Game</h1>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class='bx bx-error-circle'></i>
                <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class='bx bx-check-circle'></i>
                <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <form action="add_games.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="title">Game Title</label>
                <input type="text" id="title" name="title" required placeholder="Enter game title">
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4" required placeholder="Enter game description"></textarea>
            </div>
            <div class="form-group">
                <label for="platform">Platform</label>
                <input type="text" id="platform" name="platform" required placeholder="e.g., PS5, Xbox, PC">
            </div>
            <div class="form-group">
                <label for="genre">Genre</label>
                <input type="text" id="genre" name="genre" required placeholder="e.g., Action, RPG, Strategy">
            </div>
            <div class="form-group">
                <label for="image">Game Image</label>
                <input type="file" id="image" name="image" accept="image/*">
            </div>
            <button type="submit" class="submit-btn">
                <i class='bx bx-plus-circle'></i>
                Add Game
            </button>
        </form>
    </div>
</body>
</html>