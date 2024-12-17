<?php
// Function to safely get username by user ID
function getUserName($connection, $user_id) {
    try {
        $query = "SELECT user_id, username FROM game_users WHERE user_id = ?";
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
        $query = "SELECT user_id, email_subject FROM game_users WHERE user_id = ?";
        $stmt = $connection->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            return filter_var($row['email_subject'], FILTER_VALIDATE_EMAIL) ? $row['email_subject'] : null;
        }
        
        return null;
    } catch (Exception $e) {
        error_log("Error retrieving user email: " . $e->getMessage());
        return null;
    }
}

// Function to get user profile information
function getUserProfile($connection, $user_id) {
    try {
        $query = "SELECT 
                    user_id, 
                    username, 
                    email_subject, 
                    profile_picture, 
                    created_at, 
                    last_login,
                    role
                  FROM game_users 
                  WHERE user_id = ?";
        
        $stmt = $connection->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            return [
                'user_id' => $row['user_id'],
                'username' => htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8'),
                'email' => filter_var($row['email_subject'], FILTER_VALIDATE_EMAIL) ? $row['email_subject'] : null,
                'profile_picture' => $row['profile_picture'] ?? 'default_avatar.png',
                'created_at' => $row['created_at'],
                'last_login' => $row['last_login'],
                'role' => $row['role']
            ];
        }
        
        return null;
    } catch (Exception $e) {
        error_log("Error retrieving user profile: " . $e->getMessage());
        return null;
    }
}

// Function to update user profile
function updateUserProfile($connection, $user_id, $update_data) {
    try {
        $allowed_fields = ['username', 'email_subject', 'profile_picture'];
        $update_fields = [];
        $param_types = '';
        $param_values = [];

        foreach ($allowed_fields as $field) {
            if (isset($update_data[$field])) {
                switch ($field) {
                    case 'username':
                        $update_fields[] = "username = ?";
                        $param_types .= 's';
                        $param_values[] = htmlspecialchars($update_data['username'], ENT_QUOTES, 'UTF-8');
                        break;
                    
                    case 'email_subject':
                        $email = filter_var($update_data['email_subject'], FILTER_VALIDATE_EMAIL);
                        if ($email) {
                            $update_fields[] = "email_subject = ?";
                            $param_types .= 's';
                            $param_values[] = $email;
                        }
                        break;
                    
                    case 'profile_picture':
                        $update_fields[] = "profile_picture = ?";
                        $param_types .= 's';
                        $param_values[] = $update_data['profile_picture'];
                        break;
                }
            }
        }

        if (empty($update_fields)) {
            return false;
        }

        $param_types .= 'i';
        $param_values[] = $user_id;

        $query = "UPDATE game_users SET " . implode(', ', $update_fields) . " WHERE user_id = ?";
        
        $stmt = $connection->prepare($query);
        $stmt->bind_param($param_types, ...$param_values);
        
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Error updating user profile: " . $e->getMessage());
        return false;
    }
}

// Function to validate user credentials
function validateUserCredentials($connection, $username, $password) {
    try {
        $query = "SELECT user_id, password_hash, role, status FROM game_users WHERE username = ?";
        $stmt = $connection->prepare($query);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            if ($row['status'] !== 'active') {
                return ['valid' => false, 'reason' => 'Account is not active'];
            }
            
            if (password_verify($password, $row['password_hash'])) {
                return [
                    'valid' => true, 
                    'user_id' => $row['user_id'],
                    'role' => $row['role']
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
function checkUserExists($connection, $username = null, $email_subject = null) {
    try {
        $conditions = [];
        $param_types = '';
        $param_values = [];

        if ($username !== null) {
            $conditions[] = "username = ?";
            $param_types .= 's';
            $param_values[] = $username;
        }

        if ($email_subject !== null) {
            $conditions[] = "email_subject = ?";
            $param_types .= 's';
            $param_values[] = $email_subject;
        }

        if (empty($conditions)) {
            return false;
        }

        $query = "SELECT COUNT(*) as count FROM game_users WHERE " . implode(' OR ', $conditions);
        
        $stmt = $connection->prepare($query);
        $stmt->bind_param($param_types, ...$param_values);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $row = $result->fetch_assoc();
        return $row['count'] > 0;
    } catch (Exception $e) {
        error_log("Error checking user existence: " . $e->getMessage());
        return false;
    }
}

// Function to log user login activity
function logUserLogin($connection, $user_id, $login_type = 'web') {
    try {
        $query = "INSERT INTO user_login_history 
                  (user_id, login_type, login_time, ip_address) 
                  VALUES (?, ?, NOW(), ?)";
        
        $stmt = $connection->prepare($query);
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $stmt->bind_param("iss", $user_id, $login_type, $ip_address);
        
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Error logging user login: " . $e->getMessage());
        return false;
    }
}
?>