<?php
/**
 * Database Connection for Admin Dashboard
 */

// --- Database Configuration: Heroku vs. Local ---
// Vercel uses DATABASE_URL, Heroku might use CLEARDB_DATABASE_URL. We check both.
$db_url = getenv('DATABASE_URL') ?: getenv('CLEARDB_DATABASE_URL');

if ($db_url) {
    // Production environment (Vercel/Heroku)
    $url_parts = parse_url($db_url);
    define('DB_HOST', $url_parts['host']);
    define('DB_USER', $url_parts['user']);
    define('DB_PASS', $url_parts['pass']);
    define('DB_NAME', ltrim($url_parts['path'], '/'));
} else {
    // Local development environment
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'onlinebizpermit');
}

// --- Establish the Connection ---
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// --- Check for Connection Errors ---
if ($conn->connect_error) {
    // Use a more generic error in production
    $error_message = $db_url ? "Database connection failed." : "Database Connection Failed: " . $conn->connect_error;
    die($error_message);
}

$conn->set_charset("utf8mb4");
?>