<?php
// error_config.php

// Display all errors (for development)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log errors to a specific file (for production)
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error_log.txt');

// Custom error handler function
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    $logMessage = "Error [$errno]: $errstr in $errfile on line $errline";
    error_log($logMessage);
    if (ini_get('display_errors')) {
        echo "<b>Error:</b> $logMessage<br>";
    }
    return true;
}
