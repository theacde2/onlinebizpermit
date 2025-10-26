<?php
session_start();
require_once __DIR__ . '/db.php';

// Authentication Check (allows both admin and staff)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header("Location: ./admin_login.php");
    exit;
}

$current_user_id = $_SESSION['user_id'];
$current_user_role = $_SESSION['role'];

// Fetch Current User Info
$stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$user_info = $stmt->get_result()->fetch_assoc();
$current_user_name = $user_info['name'] ?? 'User';
$stmt->close();

// --- Fetch unread notification count for admin ---
$unread_notifications_count = 0;
$count_stmt = $conn->prepare("SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = 0");
$count_stmt->bind_param("i", $current_user_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result()->fetch_assoc();
$unread_notifications_count = $count_result['unread_count'] ?? 0;
$count_stmt->close();


?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($page_title ?? 'Admin Panel') ?> - OnlineBizPermit</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="admin_style.css">
</head>
<body>
  <div class="wrapper">