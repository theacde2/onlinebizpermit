<?php
session_start();
// Set a high error reporting level for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include mail config first to define constants
require_once __DIR__ . '/config_mail.php';
// We need the email function from the Staff dashboard
require_once __DIR__ . '/Staff-dashboard/email_functions.php';

$message = '';
$recipient_email = '';

// --- Security Check: Ensure default credentials are changed ---
if (MAIL_SMTP_USERNAME === 'your.actual.email@gmail.com' || MAIL_SMTP_PASSWORD === 'xxxxxxxxxxxxxxxx') {
    $message = '<div class="message error"><strong>Configuration Needed!</strong><br>Please update your SMTP credentials in <code>config_mail.php</code> before testing.</div>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($message)) {
    $recipient_email = trim($_POST['recipient_email'] ?? '');

    if (empty($recipient_email) || !filter_var($recipient_email, FILTER_VALIDATE_EMAIL)) {
        $message = '<div class="message error">Please enter a valid recipient email address.</div>';
    } else {
        try {
            // --- Tip for debugging ---
            if (defined('MAIL_SMTP_DEBUG') && MAIL_SMTP_DEBUG == 0) {
                 $message .= '<div class="message info"><strong>Tip:</strong> For detailed SMTP logs, set <code>MAIL_SMTP_DEBUG</code> to <code>2</code> in <code>config_mail.php</code> and resend.</div>';
            }

            // --- Call the email function with test data ---
            $test_subject = "PHPMailer Test from OnlineBizPermit";
            $test_body = "This is a test email to verify that your SMTP configuration is working correctly.\n\nIf you received this, congratulations!";
            
            sendApplicationEmail(
                $recipient_email,
                'Test Recipient',
                $test_subject,
                $test_body
            );

            $message .= '<div class="message success"><strong>Success!</strong><br>The test email has been sent to ' . htmlspecialchars($recipient_email) . '. Please check the inbox (and spam folder).</div>';

        } catch (Exception $e) {
            // Catch exceptions from PHPMailer
            $message .= '<div class="message error"><strong>Email Sending Failed!</strong><br>Mailer Error: ' . nl2br(htmlspecialchars($e->getMessage())) . '</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Email Sending Test</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="Admin-dashboard/admin_style.css"> <!-- Re-use admin styles for consistency -->
</head>
<body>
    <div class="main" style="padding: 20px;">
        <h1>Email Configuration Test</h1>
        <p class="form-subtitle">This script will use the settings in <code>config_mail.php</code> to send a test email.</p>

        <?php if (!empty($message)) echo $message; ?>

        <?php if (MAIL_SMTP_USERNAME !== 'your.actual.email@gmail.com' && MAIL_SMTP_PASSWORD !== 'xxxxxxxxxxxxxxxx'): ?>
        <form method="POST" class="table-container" style="max-width: 600px; padding: 20px;">
            <div class="form-group"><label for="recipient_email"><strong>Recipient Email Address:</strong></label><input type="email" id="recipient_email" name="recipient_email" placeholder="Enter an email to send the test to" value="<?= htmlspecialchars($recipient_email) ?>" required></div>
            <button type="submit" class="btn">Send Test Email</button>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>