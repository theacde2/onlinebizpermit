<?php
header('Content-Type: application/json');
session_start(); // Required for user context

require_once __DIR__ . '/db.php';

// Basic security check
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(400);
    echo json_encode(['error' => 'Bad Request']);
    exit;
}

// Unified session check for both applicants and staff
$is_chat_action = isset($_REQUEST['action']) && in_array($_REQUEST['action'], ['create_live_chat', 'send_message', 'get_messages', 'close_chat', 'update_typing', 'transfer_chat']);

if ($is_chat_action) {
    if (isset($_SESSION['user_id'])) {
        // User is logged in (could be applicant or staff)
        $current_user_id = $_SESSION['user_id'];
        $current_user_name = $_SESSION['name'] ?? 'User';
        $current_user_role = $_SESSION['role'] ?? 'user';
    } else {
        // Block any chat action if not logged in
        http_response_code(403);
        echo json_encode(['error' => 'Authentication required for live chat.']);
        exit;
    }
}

// --- Live Chat API Logic ---
if (isset($_POST['action']) && $_POST['action'] === 'create_live_chat') {
    try {
        $conn->begin_transaction();

        // Insert new chat session
        $stmt = $conn->prepare("INSERT INTO live_chats (user_id, status, created_at) VALUES (?, 'Pending', NOW())");
        $stmt->bind_param("i", $current_user_id);
        $stmt->execute();
        $chat_id = $stmt->insert_id;
        $stmt->close();

        // Notify staff
        $notification_message = "New live chat request from {$current_user_name}.";
        $notification_link = "../Staff-dashboard/live_chats.php"; // Link to the list of chats
        
        // This prepares an INSERT statement that sets user_id to NULL, which is intended for all staff members.
        // The column `user_id` in the `notifications` table must be configured to allow NULL values for this to work.
        $notify_stmt = $conn->prepare(
            "INSERT INTO notifications (user_id, message, link, is_read) 
             VALUES (NULL, ?, ?, 0)"
        );
        $notify_stmt->bind_param("ss", $notification_message, $notification_link); // Corrected link
        $notify_stmt->execute();
        $notify_stmt->close();

        $conn->commit();
        echo json_encode(['success' => true, 'chat_id' => $chat_id]);
    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to create chat session.']);
    }
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'send_message') {
    $chat_id = (int)$_POST['chat_id'];
    $message = trim($_POST['message']);
    $sender_role = in_array($_POST['sender_role'], ['user', 'staff']) ? $_POST['sender_role'] : 'user';
    $sender_id = (int)($_POST['sender_id'] ?? $current_user_id); // Use sender_id from POST, fallback to session user_id
    $final_message = $message;

    // Handle file upload
    if (isset($_FILES['chat_file']) && $_FILES['chat_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
        $allowed_types = ['application/pdf', 'image/jpeg', 'image/png', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $max_size = 50 * 1024 * 1024; // 50MB

        $tmp_name = $_FILES['chat_file']['tmp_name'];
        $file_type = mime_content_type($tmp_name);
        $file_size = $_FILES['chat_file']['size'];

        if (!in_array($file_type, $allowed_types)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid file type. Only PDF, DOC, DOCX, JPG, PNG are allowed.']);
            exit;
        }
        if ($file_size > $max_size) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'File is too large. Maximum size is 50MB.']);
            exit;
        }

        $original_name = basename($_FILES['chat_file']['name']);
        $file_extension = pathinfo($original_name, PATHINFO_EXTENSION);
        $unique_filename = uniqid('chat_' . $chat_id . '_', true) . '.' . $file_extension;

        if (move_uploaded_file($tmp_name, $upload_dir . $unique_filename)) {
            $file_url = '/onlinebizpermit/uploads/' . $unique_filename;
            $file_link = "<a href='" . htmlspecialchars($file_url) . "' target='_blank' rel='noopener noreferrer'>" . htmlspecialchars($original_name) . "</a>";
            $final_message = !empty($message) ? $message . "<br><br>" . $file_link : $file_link;
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to move uploaded file.']);
            exit;
        }
    }

    // Sanitize the final message before inserting to prevent XSS
    $final_message = nl2br(htmlspecialchars($final_message));

    $stmt = $conn->prepare("INSERT INTO chat_messages (chat_id, sender_id, sender_role, message) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $chat_id, $sender_id, $sender_role, $final_message);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true]);
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'get_messages') {
    $chat_id = (int)$_GET['chat_id'];
    $last_id = (int)$_GET['last_id'];

    // Fetch new messages with sender's name
    // The sender's name is in the `users` table, so we need to join based on sender_id
    $stmt = $conn->prepare(
        "SELECT 
            cm.id, 
            cm.message, 
            cm.sender_role, 
            cm.created_at, 
            u.name as sender_name 
         FROM chat_messages cm
         JOIN users u ON cm.sender_id = u.id
         WHERE cm.chat_id = ? AND cm.id > ? 
         ORDER BY cm.id ASC"
    );
    $stmt->bind_param("ii", $chat_id, $last_id);
    $stmt->execute();
    $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Fetch current chat status
    $status_stmt = $conn->prepare("SELECT status, user_is_typing, staff_is_typing FROM live_chats WHERE id = ?");
    $status_stmt->bind_param("i", $chat_id);
    $status_stmt->execute();
    $status_result = $status_stmt->get_result()->fetch_assoc();
    $status_stmt->close();

    $status_result['status'] = ucfirst($status_result['status'] ?? 'Unknown');
    $status_result['user_is_typing'] = (bool)($status_result['user_is_typing'] ?? false);
    $status_result['staff_is_typing'] = (bool)($status_result['staff_is_typing'] ?? false);

    echo json_encode(['messages' => $messages, 'status' => $status_result]);
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'close_chat') {
    $chat_id = (int)$_POST['chat_id'];

    // Ensure only staff can close a chat
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Permission denied. Only staff can close chats.']);
        exit;
    }

    try {
        $conn->begin_transaction();
        $stmt = $conn->prepare("UPDATE live_chats SET status = 'Closed', closed_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $chat_id);
        $stmt->execute();
        $stmt->close();

        // Notify the applicant that the chat has been closed by staff
        $notification_message = "Your live chat session (#{$chat_id}) has been closed by a staff member.";
        $notification_link = "live_chat.php?id={$chat_id}"; // Link back to the chat for applicant to view history
        $notify_stmt = $conn->prepare("INSERT INTO notifications (user_id, message, link, is_read) SELECT user_id, ?, ?, 0 FROM live_chats WHERE id = ?");
        $notify_stmt->bind_param("ssi", $notification_message, $notification_link, $chat_id);
        $notify_stmt->execute();
        $notify_stmt->close();

        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) { $conn->rollback(); http_response_code(500); echo json_encode(['success' => false, 'error' => 'Failed to close chat: ' . $e->getMessage()]); }
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'update_typing') {
    $chat_id = (int)$_POST['chat_id'];
    $is_typing = $_POST['is_typing'] === 'true' ? 1 : 0;
    $sender_role = $_POST['sender_role'] ?? '';

    if ($sender_role === 'user') {
        $stmt = $conn->prepare("UPDATE live_chats SET user_is_typing = ? WHERE id = ?");
    } elseif ($sender_role === 'staff') {
        $stmt = $conn->prepare("UPDATE live_chats SET staff_is_typing = ? WHERE id = ?");
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid sender role.']);
        exit;
    }
    $stmt->bind_param("ii", $is_typing, $chat_id);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => true]);
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'transfer_chat') {
    $chat_id = (int)$_POST['chat_id'];
    $new_staff_id = (int)$_POST['new_staff_id'];

    // Security: Ensure current user is staff
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Permission denied.']);
        exit;
    }

    try {
        $conn->begin_transaction();

        // Get names for notification message
        $current_staff_name = $_SESSION['name'] ?? 'A staff member';
        $new_staff_stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
        $new_staff_stmt->bind_param("i", $new_staff_id);
        $new_staff_stmt->execute();
        $new_staff_name = $new_staff_stmt->get_result()->fetch_assoc()['name'] ?? 'another staff member';
        $new_staff_stmt->close();

        // Update the chat's assigned staff_id
        $update_stmt = $conn->prepare("UPDATE live_chats SET staff_id = ? WHERE id = ?");
        $update_stmt->bind_param("ii", $new_staff_id, $chat_id);
        $update_stmt->execute();
        $update_stmt->close();

        // Add a system message to the chat log
        $transfer_message = "Chat transferred from {$current_staff_name} to {$new_staff_name}.";
        $msg_stmt = $conn->prepare("INSERT INTO chat_messages (chat_id, sender_role, message) VALUES (?, 'bot', ?)");
        $msg_stmt->bind_param("is", $chat_id, $transfer_message);
        $msg_stmt->execute();
        $msg_stmt->close();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Chat transferred successfully.']);
    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to transfer chat: ' . $e->getMessage()]);
    }
    exit;
}

// --- Original FAQ Bot Logic ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || (!isset($_POST['message']) && !isset($_POST['action']))) {
    http_response_code(400);
    echo json_encode(['error' => 'Bad Request for FAQ bot.']);
    exit;
}

require_once 'faq-data.php';

function findAnswer($userInput, $faqs) {
    $lowerCaseInput = strtolower(trim($userInput));
    if (empty($lowerCaseInput)) {
        return getFaqById('welcome', $faqs);
    }

    // First try to find by keywords
    $bestMatch = getFaqByKeywords($userInput, $faqs);
    
    if ($bestMatch) {
        return $bestMatch;
    }

    // If no match found, return welcome message
    return getFaqById('fallback', $faqs);
}

function getResponseByAction($action, $faqs) {
    $faq = getFaqById($action, $faqs);
    if ($faq) {
        return $faq;
    }
    
    // Fallback to welcome if action not found
    return getFaqById('welcome', $faqs);
}

// Handle different types of requests
if (isset($_POST['action'])) {
    // Handle choice/action selection
    $action = $_POST['action'];
    $response = getResponseByAction($action, $faqs);
} else {
    // Handle text message
    $userMessage = $_POST['message'];
    $response = findAnswer($userMessage, $faqs);
}

// Prepare response
$botResponse = [
    'reply' => $response['answer'],
    'choices' => isset($response['choices']) ? $response['choices'] : null,
    'id' => $response['id']
];

// Add a small delay to simulate "thinking"
usleep(500000); // 0.5 seconds

echo json_encode($botResponse);