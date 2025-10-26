<?php
// Page-specific variables
$page_title = 'Submit Feedback';
$current_page = 'feedback';

// Include Header
require_once __DIR__ . '/applicant_header.php';

$message = '';

// --- Handle Feedback Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    $feedback_message = trim($_POST['feedback_message']);

    if (empty($feedback_message)) {
        $message = '<div class="message error">Feedback message cannot be empty.</div>';
    } else {
        $conn->begin_transaction();
        try {
            // 1. Insert the feedback
            $stmt = $conn->prepare("INSERT INTO feedback (user_id, message) VALUES (?, ?)");
            $stmt->bind_param("is", $current_user_id, $feedback_message);
            $stmt->execute();
            $stmt->close();

            // 2. Create a notification for staff
            $notification_message = "New feedback was submitted by " . htmlspecialchars($current_user_name);
            $notification_link = "feedback.php";
            $notify_stmt = $conn->prepare("INSERT INTO notifications (user_id, message, link) VALUES (NULL, ?, ?)");
            $notify_stmt->bind_param("ss", $notification_message, $notification_link);
            $notify_stmt->execute();
            $notify_stmt->close();

            $conn->commit();
            $message = '<div class="message success">Thank you! Your feedback has been submitted successfully.</div>';
        } catch (Exception $e) {
            $conn->rollback();
            $message = '<div class="message error">An error occurred. Please try again.</div>';
            // For debugging, you can log the error: error_log($e->getMessage());
        }
    }
}

// Include Sidebar
require_once __DIR__ . '/applicant_sidebar.php';
?>

<!-- Main Content -->
<div class="main">
    <header class="header">
        <h1>Submit Feedback</h1>
    </header>

    <div class="feedback-container">
        <h3>We value your opinion</h3>
        <p>Please let us know if you have any questions, suggestions, or if you've encountered an issue. Your feedback helps us improve our service.</p>
        
        <?php if ($message) echo $message; ?>

        <form action="applicant_feedback.php" method="POST">
            <div class="form-group">
                <label for="feedback_message">Your Message</label>
                <textarea id="feedback_message" name="feedback_message" rows="8" placeholder="Enter your feedback here..." required></textarea>
            </div>
            <button type="submit" name="submit_feedback" class="btn">Submit Feedback</button>
        </form>
    </div>
</div>

<!-- Custom Styles for Feedback Page -->
<style>
    .feedback-container {
        max-width: 800px;
        margin: auto;
        background: #fff;
        padding: 30px 40px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        border: 1px solid #e9ecef;
    }
    .feedback-container h3 { font-size: 1.5rem; color: #232a3b; margin-bottom: 10px; }
    .feedback-container p { font-size: 1rem; color: #5a6a7b; line-height: 1.6; margin-bottom: 25px; }
    .form-group label { display: block; font-weight: 600; color: #5a6a7b; margin-bottom: 8px; font-size: 14px; }
    .form-group textarea {
        width: 100%;
        padding: 12px;
        border: 1px solid #ced4da;
        border-radius: 8px;
        font-size: 1rem;
        color: #343a40;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
        resize: vertical;
    }
    .form-group textarea:focus {
        border-color: #4a69bd;
        outline: none;
        box-shadow: 0 0 0 3px rgba(74, 105, 189, 0.2);
    }
    .btn { display: inline-block; width: auto; margin-top: 10px; }
</style>

<?php
// Include Footer
require_once __DIR__ . '/applicant_footer.php';
?>