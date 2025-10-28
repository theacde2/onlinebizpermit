<?php
/**
 * Centralized Database Connection
 *
 * This script handles the database connection for the entire application.
 * It intelligently switches between production (Vercel/Heroku) and local environments.
 */

// Prevent direct script access for security.
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    http_response_code(403);
    die('Access denied.');
}

// --- Database Configuration ---
$db_url = getenv('DATABASE_URL') ?: getenv('CLEARDB_DATABASE_URL');

if ($db_url) {
    // Production environment (Vercel, Heroku, etc.)
    $url_parts = parse_url($db_url);
    $db_host = $url_parts['host'];
    $db_user = $url_parts['user'];
    $db_pass = $url_parts['pass'];
    $db_name = ltrim($url_parts['path'], '/');
} else {
    // Local development environment
    $db_host = 'localhost';
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'onlinebizpermit';
}

// --- Establish and Check Connection ---
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // Throw exceptions on error
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
$conn->set_charset("utf8mb4");
