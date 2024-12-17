<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About GameLink</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

<style>
    /* General Styles */
    body, html {
        margin: 0;
        padding: 0;
        font-family: 'Poppins', sans-serif;
        color: #fff;
        height: 100%;
        background-color: #0a0a0a;
        overflow-x: hidden;
    }

    /* Parallax Background */
    body::before {
        content: '';
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-image: url('https://images.unsplash.com/photo-1538481199705-c710c4e965fc?auto=format&fit=crop&w=1950&q=80');
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
        filter: brightness(0.3);
        z-index: -1;
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem;
        position: relative;
    }

    /* Header Styles */
    header {
        background: rgba(0, 0, 0, 0.8);
        backdrop-filter: blur(10px);
        padding: 1.2rem 5%;
        position: fixed;
        width: 100%;
        top: 0;
        z-index: 1000;
        box-shadow: 0 2px 20px rgba(0, 0, 0, 0.2);
    }

    .logo a {
        color: #7c4dff;
        text-decoration: none;
        font-size: 2rem;
        font-weight: 700;
        text-shadow: 0 0 10px rgba(124, 77, 255, 0.3);
    }

    nav ul {
        list-style: none;
        display: flex;
        gap: 2rem;
    }

    nav ul li a {
        color: #fff;
        text-decoration: none;
        font-size: 1.1rem;
        font-weight: 500;
        padding: 0.8rem 1.5rem;
        border-radius: 30px;
        transition: all 0.3s ease;
        background: rgba(124, 77, 255, 0.1);
    }

    nav ul li a:hover {
        background: rgba(124, 77, 255, 0.2);
        box-shadow: 0 0 20px rgba(124, 77, 255, 0.3);
    }

    /* Hero Section */
    .hero-section {
        text-align: center;
        padding: 8rem 2rem 4rem;
        background: url('https://images.unsplash.com/photo-1550745165-9bc0b252726f?auto=format&fit=crop&w=1950&q=80') center/cover no-repeat;
        border-radius: 20px;
        margin-top: 2rem;
        position: relative;
        overflow: hidden;
    }

    .hero-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        backdrop-filter: blur(5px);
    }

    .hero-section > * {
        position: relative;
        z-index: 1;
    }

    .hero-section h1 {
        font-size: 3.5rem;
        background: linear-gradient(45deg, #7c4dff, #0099ff);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: 1.5rem;
        text-shadow: 0 0 30px rgba(124, 77, 255, 0.5);
    }

    .hero-section p {
        font-size: 1.3rem;
        line-height: 1.8;
        color: rgba(255, 255, 255, 0.9);
        max-width: 800px;
        margin: 0 auto;
    }

    /* Features Section */
    .features-section {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
        margin-top: 4rem;
        padding: 2rem 0;
    }

    .feature-card {
        background: rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(10px);
        border-radius: 20px;
        padding: 3rem 2rem;
        text-align: center;
        transition: all 0.3s ease;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .feature-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        background: rgba(255, 255, 255, 0.1);
    }

    .feature-card i {
        font-size: 3rem;
        color: #7c4dff;
        margin-bottom: 1.5rem;
        display: inline-block;
        padding: 1rem;
        border-radius: 50%;
        background: rgba(124, 77, 255, 0.1);
        box-shadow: 0 0 20px rgba(124, 77, 255, 0.2);
    }

    .feature-card h3 {
        font-size: 1.8rem;
        margin-bottom: 1rem;
        color: #fff;
        font-weight: 600;
    }

    .feature-card p {
        font-size: 1.1rem;
        color: rgba(255, 255, 255, 0.8);
        line-height: 1.6;
    }

    /* Animation */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .hero-section, .feature-card {
        animation: fadeInUp 0.8s ease-out forwards;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .hero-section h1 {
            font-size: 2.5rem;
        }

        .hero-section p {
            font-size: 1.1rem;
        }

        nav ul {
            gap: 1rem;
        }

        nav ul li a {
            padding: 0.6rem 1rem;
            font-size: 1rem;
        }

        .feature-card {
            padding: 2rem;
        }
    }
</style>
</head>
<body>
    <header>
        <div class="logo">
            <a href="index.php">Game Exchange</a>
        </div>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="./view/explore_games.php">Explore Games</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="contact.php">Contact</a></li>
            </ul>
        </nav>
    </header>

    <div class="container">
        <section class="hero-section">
            <h1>About GameLink</h1>
            <p>The ultimate platform for gamers to lend, borrow, and exchange video games. Build your gaming library, connect with the community, and save money while enjoying endless adventures.</p>
        </section>

        <section class="features-section">
            <div class="feature-card">
                <i class='bx bx-game'></i>
                <h3>Game Library</h3>
                <p>Browse a diverse collection of games across multiple platforms, all available for exchange.</p>
            </div>
            <div class="feature-card">
                <i class='bx bx-group'></i>
                <h3>Community-Driven</h3>
                <p>Connect with fellow gamers who share your passion and enthusiasm for gaming.</p>
            </div>
            <div class="feature-card">
                <i class='bx bx-money'></i>
                <h3>Cost-Effective</h3>
                <p>Save money by swapping games instead of buying new ones every time.</p>
            </div>
        </section>
    </div>
    <!-- Add this just before closing body tag -->
<section class="mission-section container">
    <div class="feature-card" style="grid-column: 1 / -1; margin-top: 2rem;">
        <i class='bx bx-target-lock'></i>
        <h3>Our Mission</h3>
        <p>To create the most accessible and user-friendly game exchange platform, fostering a community where gamers can share their passion while making gaming more affordable and sustainable.</p>
    </div>
</section>

<section class="stats-section container" style="margin-top: 4rem; text-align: center;">
    <div class="features-section">
        <div class="feature-card">
            <i class='bx bx-user'></i>
            <h3>10,000+</h3>
            <p>Active Users</p>
        </div>
        <div class="feature-card">
            <i class='bx bx-game'></i>
            <h3>50,000+</h3>
            <p>Games Listed</p>
        </div>
        <div class="feature-card">
            <i class='bx bx-transfer'></i>
            <h3>25,000+</h3>
            <p>Successful Exchanges</p>
        </div>
    </div>
</section>

</body>
</html>
