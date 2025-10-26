<?php
/**
 * PHPMailer Configuration for Gmail
 *
 * IMPORTANT: To use Gmail to send emails, you need to configure your Google Account:
 * 1. Enable 2-Step Verification for your Google Account.
 * 2. Create an "App Password" for this application.
 *    - Go to your Google Account -> Security -> 2-Step Verification -> App passwords.
 *    - Generate a new password for "Mail" on "Other (Custom name)".
 *    - Use the generated 16-character password in MAIL_SMTP_PASSWORD below.
 *
 * @link https://support.google.com/accounts/answer/185833
 */

// --- Main Email Switch ---
// Set to true to enable sending emails, false to disable.
define('MAIL_SMTP_ENABLED', true);

// --- Application URL ---
// The base URL of the application. IMPORTANT: For local development, use your computer's
// local network IP address instead of 'localhost' so that links in emails are accessible
// from other devices on your network (like your phone).
// In production, this should be your actual domain name (e.g., 'https://www.onlinebizpermit.com').
// To find your local IP on Windows, open Command Prompt and type 'ipconfig'. Look for the IPv4 address.
define('APP_BASE_URL', 'http://localhost/onlinebizpermit'); // <-- IMPORTANT: REPLACE YOUR_PC_IP_ADDRESS with the IPv4 address you found.

// --- SMTP Debugging ---
// 0 = off (for production)
// 2 = client and server messages (for debugging)
define('MAIL_SMTP_DEBUG', 2);

// --- SMTP Server Settings (example for Gmail) ---
define('MAIL_SMTP_HOST', 'smtp.gmail.com');
define('MAIL_SMTP_PORT', 587); // Use 587 for TLS, or 465 for SSL
define('MAIL_SMTP_SECURE', 'tls'); // 'tls' or 'ssl'

// --- SMTP Authentication ---
define('MAIL_SMTP_USERNAME', 'atdelacruz@catsu.edu.ph'); // <-- IMPORTANT: Replace with your full Gmail address
define('MAIL_SMTP_PASSWORD', 'kmhd qeao jkij ylnt'); // <-- IMPORTANT: Replace with the 16-character App Password you generated

// --- Sender Information ---
define('MAIL_FROM_EMAIL', 'atdelacruz@catsu.edu.ph'); // Can be the same as username
define('MAIL_FROM_NAME', 'OnlineBizPermit Support'); // The name recipients will see
