<?php
session_start();
require_once '../db/db.php'; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        // Redirect back to the login page with an error message
        header("Location: ../view/login.php?error=Email and password are required.");
        exit;
    }

    try {
        $stmt = $conn->prepare("SELECT * FROM game_users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // Verify the password
            if (password_verify($password, $user['password_hash'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role']; // Admin: 1, User: 2

                // Redirect based on role
                if ($user['role'] === 1) { // Admin
                    header("Location: ../view/admin/admin_dashboard.php");
                } elseif ($user['role'] === 2) { // Regular User
                    header("Location: ../view/admin/dashboard.php");
                }
                exit();
            } else {
                // Redirect back to login with error
                header("Location: ../view/login.php?error=Incorrect password.");
                exit();
            }
        } else {
            // Redirect back to login with error
            header("Location: ../view/login.php?error=No user found with this email.");
            exit();
        }
    } catch (Exception $e) {
        // Redirect back to login with error
        header("Location: ../view/login.php?error=An error occurred. Please try again later.");
        exit();
    } finally {
        $stmt->close();
        $conn->close();
    }
} else {
    // Redirect back to login for invalid request method
    header("Location: ../view/login.php?error=Invalid request method.");
    exit();
}
