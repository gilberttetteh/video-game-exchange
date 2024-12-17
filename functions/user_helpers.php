<?php
require_once '../db/db.php';
require_once '../config1.php';

// Function to safely get username by user ID 
function getUserName($connection, $user_id) {
    try {
        $query = "SELECT username FROM game_users WHERE user_id = ?";
        $stmt = $connection->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            return htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8');
        }
        
        return "Unknown User";
    } catch (Exception $e) {
        error_log("Error retrieving username: " . $e->getMessage());
        return "Unknown User";
    }
}

// Function to get user email by user ID
function getUserEmail($connection, $user_id) {
    try {
        $query = "SELECT email FROM game_users WHERE user_id = ?";
        $stmt = $connection->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            return filter_var($row['email'], FILTER_VALIDATE_EMAIL) ? $row['email'] : null;
        }
        
        return null;
    } catch (Exception $e) {
        error_log("Error retrieving user email: " . $e->getMessage());
        return null;
    }
}

// Function to get user's game library
function getUserGameLibrary($connection, $user_id) {
    try {
        $query = "SELECT 
                    g.game_id, 
                    g.title, 
                    g.platform, 
                    g.genre, 
                    g.created_at
                  FROM game_games g
                  WHERE g.user_id = ?
                  ORDER BY g.created_at DESC";
        
        $stmt = $connection->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $game_library = [];
        while ($row = $result->fetch_assoc()) {
            $game_library[] = [
                'game_id' => $row['game_id'],
                'title' => htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8'),
                'platform' => $row['platform'],
                'genre' => $row['genre'],
                'created_at' => $row['created_at']
            ];
        }
        
        return $game_library;
    } catch (Exception $e) {
        error_log("Error retrieving user game library: " . $e->getMessage());
        return [];
    }
}

// Function to validate user credentials
function validateUserCredentials($connection, $username, $password) {
    try {
        $query = "SELECT user_id, password_hash FROM game_users WHERE email = ?";
        $stmt = $connection->prepare($query);
        $stmt->bind_param("s", $username); // Using email as username
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['password_hash'])) {
                return [
                    'valid' => true, 
                    'user_id' => $row['user_id']
                ];
            }
        }
        
        return ['valid' => false, 'reason' => 'Invalid credentials'];
    } catch (Exception $e) {
        error_log("Error validating user credentials: " . $e->getMessage());
        return ['valid' => false, 'reason' => 'System error'];
    }
}

// Function to check if username or email already exists
function checkUserExists($connection, $email) {
    try {
        $query = "SELECT COUNT(*) as count FROM game_users WHERE email = ?";
        $stmt = $connection->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $row = $result->fetch_assoc();
        return $row['count'] > 0;
    } catch (Exception $e) {
        error_log("Error checking user existence: " . $e->getMessage());
        return false;
    }
}
?>