<?php
session_start();
require './db.php';

// Authentication Check (allows both admin and staff)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// --- Fetch Recent Activity ---
$activityQuery = "(
                    SELECT 'user' as type, id, name as title, COALESCE(created_at, NOW()) as timestamp
                    FROM users
                  )
                  UNION ALL
                  (
                    SELECT 'application' as type, id, business_name as title, COALESCE(submitted_at, NOW()) as timestamp
                    FROM applications
                  )
                  ORDER BY timestamp DESC
                  LIMIT 10";
$result = $conn->query($activityQuery);
$activities = $result->fetch_all(MYSQLI_ASSOC);

header('Content-Type: application/json');
echo json_encode($activities);