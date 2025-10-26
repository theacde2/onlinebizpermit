<?php
// c:\xampp\htdocs\onlinebizpermit\Staff-dashboard\email_functions.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Make sure composer autoloader is included.
require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Sends an application-related email.
 *
 * @param string $to_email      Recipient's email address.
 * @param string $to_name       Recipient's name.
 * @param string $subject       The email subject.
 * @param string $body          The HTML email body.
 * @param array  $attachments   An array of attachments. Each attachment is an array with 'string', 'filename', and 'type'.
 * @return bool                 True on success.
 * @throws Exception            Throws PHPMailer exception on failure.
 */
function sendApplicationEmail(string $to_email, string $to_name, string $subject, string $body, array $attachments = []): bool {
    // First, check if email sending is globally disabled in the config
    if (!defined('MAIL_SMTP_ENABLED') || MAIL_SMTP_ENABLED !== true) {
        error_log("Email sending is disabled in config_mail.php. Email to {$to_email} was not sent.");
        return true; // Return true to not break the application flow
    }

    $mail = new PHPMailer(true);

    try {
        // --- SERVER SETTINGS ---
        // Use the debug level from the config file
        $mail->SMTPDebug = defined('MAIL_SMTP_DEBUG') ? MAIL_SMTP_DEBUG : 0;

        // Direct debug output to the error log instead of echoing it
        $mail->Debugoutput = 'error_log';

        $mail->isSMTP();

        // Use settings from config_mail.php
        $mail->Host       = MAIL_SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_SMTP_USERNAME;
        $mail->Password   = MAIL_SMTP_PASSWORD;
        $mail->SMTPSecure = MAIL_SMTP_SECURE;
        $mail->Port       = MAIL_SMTP_PORT;

        // --- RECIPIENTS ---
        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        $mail->addAddress($to_email, $to_name);

        // --- ATTACHMENTS ---
        if (is_array($attachments)) {
            foreach ($attachments as $attachment) {
                if (isset($attachment['string']) && isset($attachment['filename'])) {
                    $mail->addStringAttachment(
                        $attachment['string'],
                        $attachment['filename'],
                        'base64',
                        $attachment['type'] ?? 'application/pdf'
                    );
                }
            }
        }

        // --- CONTENT ---
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body); // Plain text version for non-HTML clients

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log the error for debugging, but don't expose details to the user.
        error_log("Email could not be sent to {$to_email}. Mailer Error: {$mail->ErrorInfo}");
        // Re-throw the exception so the calling script can handle it (e.g., rollback a transaction).
        throw $e;
    }
}
