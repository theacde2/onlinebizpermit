<?php
// A simple router for Vercel.

$request_path = strtok($_SERVER['REQUEST_URI'], '?');

// Security: Don't allow direct access to sensitive files.
$restricted_files = [
    'db.php',
    'config_mail.php',
    'vercel.json',
    'composer.json',
    'composer.lock',
    '.vercelignore',
    '.git'
];

if (in_array(basename($request_path), $restricted_files)) {
    http_response_code(403);
    die('Access denied.');
}

// Serve static files directly if they exist.
$file_path = __DIR__ . $request_path;
if (file_exists($file_path) && !is_dir($file_path) && pathinfo($file_path, PATHINFO_EXTENSION) !== 'php') {
    $mime_type = mime_content_type($file_path);
    header("Content-Type: $mime_type");
    readfile($file_path);
    exit;
}

// If the requested path is a directory, look for an index file.
if (is_dir($file_path)) {
    $file_path = rtrim($file_path, '/') . '/index.php';
}

// If the PHP file exists, include it. Otherwise, show a 404 error.
if (file_exists($file_path) && pathinfo($file_path, PATHINFO_EXTENSION) === 'php') {
    require_once $file_path;
} else {
    http_response_code(404);
    echo "<h1>404 Not Found</h1><p>The page you requested could not be found.</p>";
}