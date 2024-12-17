<?php
session_start();
require_once './db/db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameLink: Trade, Play, Connect!</title>
    <style>
/* General Styles and Fonts */

@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

body, html {
    margin: 0;
    padding: 0;
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(135deg, #0f0c29, #24243e, #302b63);
    color: #fff;
    line-height: 1.6;
    box-sizing: border-box;
    min-height: 100vh;
    overflow-x: hidden;
}

/* Custom Scrollbar */
::-webkit-scrollbar {
    width: 10px;
}

::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
}

::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.3);
    border-radius: 5px;
}

::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.5);
}

/* Header Section */
header {
    background: url('https://images.unsplash.com/photo-1538481199705-c710c4e965fc?auto=format&fit=crop&w=1920&q=80') center/cover no-repeat;
    height: 100vh;
    color: white;
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
}

header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(10px);
    z-index: 0;
}

header h1 {
    font-size: 4rem;
    font-weight: 700;
    color: #fff;
    background: linear-gradient(90deg, #00f2fe, #4facfe);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    position: relative;
    z-index: 1;
    text-shadow: 0px 4px 10px rgba(0, 0, 0, 0.5);
}

header p {
    font-size: 1.5rem;
    color: rgba(255, 255, 255, 0.9);
    font-weight: 300;
    margin: 20px 0;
    position: relative;
    z-index: 1;
}

nav ul {
    list-style: none;
    padding: 0;
    display: flex;
    justify-content: center;
    gap: 15px;
    position: relative;
    z-index: 1;
}

nav ul li a {
    color: #fff;
    text-decoration: none;
    font-size: 1.1rem;
    padding: 12px 25px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: 30px;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(5px);
    transition: all 0.3s ease;
}

nav ul li a:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
}

/* Main Content */
main {
    max-width: 1200px;
    margin: 20px auto;
    padding: 20px;
}

//* Section: How It Works */
.intro {
    position: relative;
    margin: 80px auto;
    padding: 60px 40px;
    max-width: 1200px;
    background: linear-gradient(145deg, rgba(29, 32, 43, 0.8), rgba(16, 18, 27, 0.9));
    border-radius: 30px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(20px);
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
    overflow: hidden;
}

.intro::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #FF3366, #FF6B6B, #4facfe, #00f2fe);
    animation: gradientFlow 3s linear infinite;
}

.intro-content {
    position: relative;
    z-index: 2;
}

.intro h2 {
    font-size: 3.2rem;
    margin-bottom: 30px;
    font-weight: 800;
    letter-spacing: -0.5px;
    background: linear-gradient(45deg, #FF3366, #FF6B6B, #4facfe, #00f2fe);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    text-align: center;
    animation: titleGlow 3s ease-in-out infinite;
}

.steps-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 30px;
    margin-top: 50px;
}

.step-card {
    background: rgba(255, 255, 255, 0.03);
    border-radius: 20px;
    padding: 30px;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    border: 1px solid rgba(255, 255, 255, 0.05);
}

.step-number {
    font-size: 4rem;
    font-weight: 800;
    background: linear-gradient(45deg, #FF3366, #FF6B6B);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: 20px;
    line-height: 1;
}

.step-title {
    font-size: 1.5rem;
    color: #fff;
    margin-bottom: 15px;
    font-weight: 600;
}

.step-description {
    font-size: 1.1rem;
    line-height: 1.7;
    color: #B8B9CF;
    margin-bottom: 20px;
}

.step-icon {
    font-size: 2.5rem;
    margin-bottom: 20px;
    color: #FF3366;
    transition: all 0.3s ease;
}

.step-card:hover {
    transform: translateY(-10px);
    background: rgba(255, 255, 255, 0.05);
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
    border-color: rgba(255, 51, 102, 0.2);
}

.step-card:hover .step-icon {
    transform: scale(1.2);
    color: #00f2fe;
}

/* Decorative Elements */
.intro::after {
    content: '';
    position: absolute;
    width: 200px;
    height: 200px;
    background: linear-gradient(45deg, #FF3366, #FF6B6B);
    filter: blur(80px);
    opacity: 0.15;
    border-radius: 50%;
    bottom: -100px;
    right: -100px;
    z-index: 1;
}

/* Animations */
@keyframes gradientFlow {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

@keyframes titleGlow {
    0%, 100% { text-shadow: 0 0 30px rgba(255, 51, 102, 0.3); }
    50% { text-shadow: 0 0 50px rgba(79, 172, 254, 0.5); }
}

/* Responsive Design */
@media (max-width: 768px) {
    .intro {
        margin: 40px 20px;
        padding: 40px 20px;
    }

    .intro h2 {
        font-size: 2.5rem;
    }

    .steps-container {
        grid-template-columns: 1fr;
        gap: 20px;
    }
}

/* Section: Featured Games */
.featured-games {
    margin: 60px auto;
    text-align: center;
    padding: 50px;
    background: rgba(16, 18, 27, 0.4);
    backdrop-filter: blur(10px);
    border-radius: 30px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.featured-games h2 {
    font-size: 2.8rem;
    background: linear-gradient(45deg, #FF3366, #FF6B6B);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    font-weight: 800;
    text-transform: uppercase;
    margin-bottom: 50px;
    letter-spacing: 2px;
    text-shadow: 2px 2px 20px rgba(255, 51, 102, 0.3);
}

.games-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 30px;
    padding: 20px;
    max-width: 1400px;
    margin: 0 auto;
}

.game-card {
    background: linear-gradient(145deg, rgba(26, 27, 46, 0.9), rgba(20, 21, 36, 0.95));
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 24px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    overflow: hidden;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    position: relative;
    padding: 20px;
}

.game-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, #FF3366, #FF6B6B);
}

.game-card img {
    max-width: 100%;
    max-height: 200px; /* Adjust this to the desired height */
    object-fit: contain;
}



.game-card h3 {
    font-size: 1.6rem;
    color: #fff;
    margin: 15px 0;
    font-weight: 600;
    background: linear-gradient(45deg, #fff, #e0e0e0);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
}

.game-card .platform {
    font-size: 0.9rem;
    color: #FF3366;
    margin: 15px 0;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    font-weight: 600;
    display: inline-block;
    padding: 6px 12px;
    background: rgba(255, 51, 102, 0.1);
    border-radius: 20px;
}

.game-card p {
    font-size: 1rem;
    color: #B8B9CF;
    line-height: 1.6;
    margin: 15px 0;
}

.game-card .actions {
    display: flex;
    gap: 12px;
    margin-top: 20px;
}

.game-card .btn {
    flex: 1;
    padding: 12px 20px;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.game-card .btn-primary {
    background: linear-gradient(45deg, #FF3366, #FF6B6B);
    color: white;
}

.game-card .btn-secondary {
    background: rgba(255, 255, 255, 0.1);
    color: #fff;
}

.game-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
    border-color: rgba(255, 51, 102, 0.3);
}

.game-card:hover img {
    transform: scale(1.05);
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .featured-games {
        padding: 30px 20px;
        margin: 30px auto;
    }

    .featured-games h2 {
        font-size: 2rem;
    }

    .games-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
}

/* Animation */
@keyframes cardEntrance {
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
    animation: cardEntrance 0.8s ease forwards;
}

/* Animations */
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

/* Responsive Design */
@media (max-width: 768px) {
    .games-grid {
        flex-direction: column;
        align-items: center;
    }

    .game-card {
        width: 90%;
    }
}

    </style>
</head>
<body>
    <!-- Header Section -->
    <header>
        <h1>Welcome to GameLink</h1>
        <p>Borrow, lend, and swap video games with a community of gamers.</p>
        <nav>
            <ul>
                <li><a href="./view/login.php">Login</a></li>
                <li><a href="./view/register.php">Register</a></li>
                <li><a href="./view/explore_games.php">Explore Games</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="contact.php">Contact</a></li>
            </ul>
        </nav>
    </header>

    <!-- Main Content Section -->
    <main>
    <section class="intro">
    <h2>How It Works</h2>
    <p>
        With GameLink, you can trade, borrow, and lend video games effortlessly. Post your games, explore listings,
        and connect with fellow gamers in your community. It's simple, secure, and designed for gamers like you.
    </p>
</section>


<section class="featured-games">
    <h2>Featured Games</h2>
    <div class="games-grid">
    <?php
$query = "SELECT title, platform, image, description FROM game_games WHERE status = 'available' ORDER BY created_at DESC LIMIT 6";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Convert BLOB data to Base64
        $imageData = base64_encode($row['image']);
        $imageSrc = "data:image/jpeg;base64," . $imageData;

        echo "<div class='game-card'>";
        echo "<img src='" . $imageSrc . "' alt='" . htmlspecialchars($row['title']) . "'>";
        echo "<h3>" . htmlspecialchars($row['title']) . "</h3>";
        echo "<p class='platform'>" . htmlspecialchars($row['platform']) . "</p>";
        echo "<p>" . htmlspecialchars($row['description']) . "</p>";
        echo "</div>";
    }
} else {
    echo "<p>No featured games available right now. Check back later!</p>";
}
?>

    </div>
</section>

    </main>

</body>
</html>
