<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../db/db.php';

// Check if the user is an administrator
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 1) { // Role '1' for admin
    die("Access denied. Administrator access is required.");
}

$messages = []; // Array to store messages for success or error feedback

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = intval($_POST['user_id'] ?? 0);

    if ($action === 'delete' && $user_id > 0) {
        // Delete user
        $stmt = $conn->prepare("DELETE FROM game_users WHERE user_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            if ($stmt->execute()) {
                $messages[] = "User deleted successfully.";
            } else {
                $messages[] = "Error deleting user: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $messages[] = "Error preparing delete statement: " . $conn->error;
        }
    } elseif ($action === 'update' && $user_id > 0) {
        // Update user role
        $new_role = intval($_POST['role'] ?? 0); // Ensure the role is an integer
        if ($new_role === 1 || $new_role === 2) { // Role must be 1 (Admin) or 2 (User)
            $stmt = $conn->prepare("UPDATE game_users SET role = ? WHERE user_id = ?");
            if ($stmt) {
                $stmt->bind_param("ii", $new_role, $user_id);
                if ($stmt->execute()) {
                    $messages[] = "User role updated successfully.";
                } else {
                    $messages[] = "Error updating user role: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $messages[] = "Error preparing update statement: " . $conn->error;
            }
        } else {
            $messages[] = "Invalid role selected.";
        }
    } else {
        $messages[] = "Invalid action or user ID.";
    }

    // Redirect to the same page to refresh the displayed data
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch users
$users = [];
$query = "SELECT user_id, username, email, role FROM game_users";
if ($result = $conn->query($query)) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $result->free();
} else {
    $messages[] = "Error fetching users: " . $conn->error;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
       
body {
    margin: 0;
    padding: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #1a1b2e;
    color: #fff;
    min-height: 100vh;
}

.container {
    max-width: 1200px;
    margin: 90px auto 2rem;
    padding: 0 2rem;
}

h1 {
    color: #fff;
    font-size: 2rem;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

/* Table Styling */
.table-container {
    background: #242640;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

th {
    background: #1a1b2e;
    color: #4caf50;
    padding: 1.2rem 1rem;
    text-align: left;
    font-weight: 600;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

td {
    padding: 1rem;
    color: #a8abbe;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

tr:hover td {
    background: rgba(76, 175, 80, 0.1);
    color: #fff;
}

/* Action Buttons and Controls */
.action-buttons {
    display: flex;
    gap: 10px;
    align-items: center;
}

button {
    background: #2c2f44;
    border: none;
    padding: 0.8rem 1.2rem;
    border-radius: 8px;
    color: #fff;
    cursor: pointer;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

button i {
    font-size: 1.1rem;
}

button[value="update"] {
    background: #4caf50;
}

button[value="update"]:hover {
    background: #45a049;
    transform: translateY(-2px);
}

button[value="delete"] {
    background: #f44336;
}

button[value="delete"]:hover {
    background: #d32f2f;
    transform: translateY(-2px);
}

select {
    padding: 0.8rem;
    border-radius: 8px;
    border: 1px solid rgba(255,255,255,0.1);
    background: #1a1b2e;
    color: #fff;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

select:hover {
    border-color: #4caf50;
}

/* Back to Dashboard Button */
.back-button {
    margin-bottom: 2rem;
}

.back-button button {
    background: #3f4156;
    display: flex;
    align-items: center;
    gap: 8px;
}

.back-button button:hover {
    background: #4caf50;
    transform: translateY(-2px);
}

/* Messages */
.messages {
    margin-bottom: 2rem;
    padding: 1rem;
    border-radius: 8px;
    background: rgba(76, 175, 80, 0.1);
    color: #4caf50;
}

/* Status Badge */
.role-badge {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
}

.role-admin {
    background: rgba(76, 175, 80, 0.2);
    color: #4caf50;
}

.role-user {
    background: rgba(52, 152, 219, 0.2);
    color: #3498db;
}

/* Responsive Design */
@media (max-width: 768px) {
    .container {
        padding: 0 1rem;
    }

    .action-buttons {
        flex-direction: column;
        gap: 8px;
    }

    td {
        padding: 0.8rem;
    }

    button {
        width: 100%;
        justify-content: center;
    }
}
    </style>
</head>
<body>
    <div class="container">
        <!-- Back to Dashboard Button -->
        <div class="back-button">
            <button onclick="window.location.href='./admin/admin_dashboard.php'">
                <i class='bx bx-arrow-back'></i>
                Back to Dashboard
            </button>
        </div>

        <h1>Manage Users</h1>

        <!-- Messages Section -->
        <?php if (!empty($messages)): ?>
            <div class="messages">
                <?php foreach ($messages as $message): ?>
                    <p><?php echo htmlspecialchars($message); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Users Table -->
        <div class="table-container">
            <?php if (!empty($users)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="role-badge <?php echo intval($user['role']) === 1 ? 'role-admin' : 'role-user'; ?>">
                                        <?php echo intval($user['role']) === 1 ? 'Administrator' : 'User'; ?>
                                    </span>
                                </td>
                                <td class="action-buttons">
                                    <form method="POST" style="display:inline-block;">
                                        <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                        <select name="role">
                                            <option value="2" <?php echo intval($user['role']) === 2 ? 'selected' : ''; ?>>User</option>
                                            <option value="1" <?php echo intval($user['role']) === 1 ? 'selected' : ''; ?>>Administrator</option>
                                        </select>
                                        <button type="submit" name="action" value="update">
                                            <i class='bx bx-edit'></i>
                                            Update
                                        </button>
                                    </form>
                                    <form method="POST" style="display:inline-block;">
                                        <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                        <button type="submit" name="action" value="delete" onclick="return confirm('Are you sure you want to delete this user?');">
                                            <i class='bx bx-trash'></i>
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-users">No users found.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
