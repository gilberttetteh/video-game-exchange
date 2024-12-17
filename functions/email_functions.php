<?php
require_once '../db/db.php';
require_once '../config1.php';

$autoloadPath = __DIR__ . '/../vendor/autoload.php';

// Check if Composer's autoload file exists
if (!file_exists($autoloadPath)) {
    die('Composer dependencies not installed. Please run "composer install" to proceed.');
}

// Include Composer's autoload file
require_once $autoloadPath;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Function to send an email using PHPMailer
function sendEmail($to_email, $subject, $message) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = getenv('SMTP_USERNAME');
        $mail->Password   = getenv('SMTP_PASSWORD');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('noreply@videogame.com', 'Game Link');
        $mail->addAddress($to_email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;

        // Send the email
        $mail->send();
        error_log("Email sent successfully to: {$to_email}");
        return true;
    } catch (Exception $e) {
        error_log("Failed to send email to {$to_email}. Error: {$mail->ErrorInfo}");
        return false;
    }
}

// Function to send trade notifications
function sendTradeNotification($user_email, $type, $data) {
    if (!filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
        error_log("Invalid email address: {$user_email}");
        return false;
    }

    $required_keys = ['requester_name', 'offered_game', 'requested_game'];
    foreach ($required_keys as $key) {
        if (empty($data[$key])) {
            error_log("Missing data key: {$key}");
            return false;
        }
    }

    // Sanitize input
    $data['requester_name'] = htmlspecialchars($data['requester_name']);
    $data['offered_game'] = htmlspecialchars($data['offered_game']);
    $data['requested_game'] = htmlspecialchars($data['requested_game']);

    $subject = "";
    $message = "";

    switch ($type) {
        case 'new_request':
            $subject = "New Trade Request";
            $message = "
                <h2>New Trade Request Received</h2>
                <p>{$data['requester_name']} wants to trade their {$data['offered_game']} 
                for your {$data['requested_game']}.</p>
            ";
            break;

        case 'approved':
            $subject = "Trade Request Approved";
            $message = "
                <h2>Your Trade Request was Approved!</h2>
                <p>Your request to trade {$data['offered_game']} 
                for {$data['requested_game']} has been approved.</p>
            ";
            break;

        case 'rejected':
            $subject = "Trade Request Status Update";
            $message = "
                <h2>Trade Request Update</h2>
                <p>Your request to trade {$data['offered_game']} 
                for {$data['requested_game']} was not approved.</p>
            ";
            break;

        default:
            error_log("Invalid notification type: {$type}");
            return false;
    }

    return sendEmail($user_email, $subject, $message);
}

?>
