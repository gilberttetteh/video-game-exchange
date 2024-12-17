<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameLink - Login</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #1a2a6c, #b21f1f, #fdbb2d);
            color: #333;
        }

        .container {
            background: #fff;
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }

        h1 {
            text-align: center;
            margin-bottom: 1.5rem;
            font-size: 2rem;
            color: #333;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        label {
            margin-top: 1rem;
            font-weight: 500;
        }

        input {
            padding: 0.75rem;
            margin-top: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 6px;
        }

        button {
            margin-top: 1.5rem;
            padding: 0.75rem;
            background: #1a2a6c;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }

        button:hover {
            background: #fdbb2d;
            color: #1a2a6c;
        }

        .switch {
            text-align: center;
            margin-top: 1rem;
            font-size: 0.9rem;
        }

        .switch a {
            color: #1a2a6c;
            text-decoration: none;
        }

        .switch a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>GameLink Login</h1>
        <form id="loginForm" method="POST" action="../actions/login_user.php">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Login</button>
        </form>
        <div class="switch">
            Don't have an account? <a href="register.php">Sign Up</a>
        </div>
    </div>
</body>
</html>
