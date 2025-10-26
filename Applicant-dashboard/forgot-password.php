<?php
session_start();
require_once 'db.php';

// If user is already logged in, redirect them
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'user') {
        header("Location: applicant_dashboard.php");
    } else {
        header("Location: login.php");
    }
    exit;
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (empty($email)) {
        $error_message = "Please enter your email address.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } else {
        // Check if email exists and is an applicant
        $stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ? AND role = 'user'");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            // Generate reset token
            $reset_token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store reset token in database
            $stmt = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE token = ?, expires_at = ?");
            $stmt->bind_param("sssss", $email, $reset_token, $expires_at, $reset_token, $expires_at);
            
            if ($stmt->execute()) {
                // In a real application, you would send an email here
                // For demo purposes, we'll show the reset link
                $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/reset-password.php?token=" . $reset_token;
                
                $success_message = "Password reset instructions have been sent to your email address. Check your inbox and follow the link to reset your password.";
                
                // For demo purposes, show the link (remove in production)
                $success_message .= "<br><br><strong>Demo Reset Link:</strong><br><a href='$reset_link' target='_blank'>$reset_link</a>";
            } else {
                $error_message = "Failed to process reset request. Please try again.";
            }
        } else {
            // Don't reveal if email exists or not for security
            $success_message = "If an account with that email exists, password reset instructions have been sent.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - OnlineBizPermit</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="auth_style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="logo">
                    <i class="fas fa-building"></i>
                    <h1>OnlineBizPermit</h1>
                </div>
                <h2>Forgot Password?</h2>
                <p>No worries! Enter your email and we'll send you reset instructions.</p>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= $error_message ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i>
                    <?= $success_message ?>
                </div>
            <?php endif; ?>

            <form action="forgot-password.php" method="POST" class="auth-form">
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i>
                        Email Address
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        value="<?= htmlspecialchars($email ?? '') ?>"
                        required 
                        autocomplete="email"
                        placeholder="Enter your email address"
                    >
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i>
                    Send Reset Instructions
                </button>
            </form>

            <div class="auth-footer">
                <p>Remember your password? <a href="login.php">Sign in here</a></p>
                <p>Don't have an account? <a href="register.php">Create one here</a></p>
            </div>
        </div>

        <div class="auth-info">
            <div class="info-content">
                <h3>Secure Password Recovery</h3>
                <p>We take your security seriously. Our password reset process is designed to keep your account safe.</p>
                
                <div class="features">
                    <div class="feature">
                        <i class="fas fa-shield-alt"></i>
                        <div>
                            <h4>Secure Process</h4>
                            <p>Reset links expire after 1 hour and can only be used once for maximum security.</p>
                        </div>
                    </div>
                    
                    <div class="feature">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <h4>Email Verification</h4>
                            <p>We'll send reset instructions only to the email address associated with your account.</p>
                        </div>
                    </div>
                    
                    <div class="feature">
                        <i class="fas fa-clock"></i>
                        <div>
                            <h4>Quick Recovery</h4>
                            <p>Get back to managing your business permits in just a few minutes.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-hide messages after 10 seconds
        setTimeout(() => {
            const messages = document.querySelectorAll('.message');
            messages.forEach(message => {
                message.style.opacity = '0';
                setTimeout(() => message.remove(), 300);
            });
        }, 10000);

        // Form validation
        document.querySelector('.auth-form').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            
            if (!email) {
                e.preventDefault();
                alert('Please enter your email address.');
                return;
            }
            
            // Basic email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address.');
                return;
            }
        });
    </script>
</body>
</html>
