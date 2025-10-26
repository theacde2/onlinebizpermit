<?php
require 'db.php';

// Set a default timezone to avoid potential warnings
date_default_timezone_set('UTC');

$adminEmail = "admin@example.com";

// --- Security First: Check if an admin already exists ---
$checkSql = "SELECT id FROM users WHERE role = 'admin' LIMIT 1";
$result = $conn->query($checkSql);

if ($result && $result->num_rows > 0) {
    echo "<h1>⚠️ Action Not Needed</h1>";
    echo "<p>An admin account already exists. For security, this script will not create another one.</p>";
    echo "<p>If you need to reset the admin password, please do so directly in the database via phpMyAdmin.</p>";
    echo "<p style='color:red; font-weight:bold;'>Please delete this file (<code>create_admin.php</code>) now.</p>";
    exit;
}

// --- If no admin exists, proceed to create one ---
$adminPass  = password_hash("admin123", PASSWORD_DEFAULT);
$adminName  = "Super Admin";

$sql = "INSERT INTO users (name, email, password, role, is_approved) VALUES (?, ?, ?, 'admin', 1)";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
}

$stmt->bind_param("sss", $adminName, $adminEmail, $adminPass);

if ($stmt->execute()) {
    echo "<h1>✅ Admin Account Created!</h1>";
    echo "<p>Your administrator account has been successfully set up.</p>";
    echo "<p><strong>Email:</strong> " . htmlspecialchars($adminEmail) . "</p>";
    echo "<p><strong>Password:</strong> admin123</p>";
    echo "<hr>";
    echo "<p style='color:red; font-weight:bold;'>IMPORTANT: For security reasons, please delete this file (<code>create_admin.php</code>) from your server immediately.</p>";
    echo '<a href="admin_login.php">Go to Login Page</a>';
} else {
    // Provide more specific error for duplicate email
    $errorMessage = ($conn->errno === 1062)
        ? "An account with the email '" . htmlspecialchars($adminEmail) . "' already exists."
        : "Could not create admin account: " . htmlspecialchars($stmt->error);
    echo "<h1>❌ Error</h1><p>{$errorMessage}</p>";
}

$stmt->close();
$conn->close();
