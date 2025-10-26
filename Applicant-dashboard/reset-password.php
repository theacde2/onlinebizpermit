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
$token = $_GET['token'] ?? '';

// Validate token
if (empty($token)) {
    $error_message = "Invalid or missing reset token.";
} else {
    // Check if token exists and is not expired
    $stmt = $conn->prepare("SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result->num_rows) {
        $error_message = "Invalid or expired reset token. Please request a new password reset.";
    } else {
        $reset_data = $result->fetch_assoc();
        $email = $reset_data['email'];
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error_message) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($password) || empty($confirm_password)) {
        $error_message = "Please fill in all fields.";
    } elseif (strlen($password) < 8) {
        $error_message = "Password must be at least 8 characters long.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } else {
        // Hash new password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Update user password
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ? AND role = 'user'");
        $stmt->bind_param("ss", $hashed_password, $email);
        
        if ($stmt->execute()) {
            // Delete the reset token
            $stmt = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            
            // Redirect to login with success message
            header("Location: login.php?status=reset_success");
            exit;
        } else {
            $error_message = "Failed to update password. Please try again.";
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
    <title>Reset Password - OnlineBizPermit</title>
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
                <h2>Reset Password</h2>
                <p>Enter your new password below</p>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i>
                    <?= htmlspecialchars($success_message) ?>
                </div>
            <?php endif; ?>

            <?php if (!$error_message): ?>
            <form action="reset-password.php?token=<?= htmlspecialchars($token) ?>" method="POST" class="auth-form" id="resetForm">
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i>
                        New Password
                    </label>
                    <div class="password-input">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required 
                            autocomplete="new-password"
                            placeholder="Enter your new password"
                            minlength="8"
                        >
                        <button type="button" class="password-toggle" onclick="togglePassword('password')">
                            <i class="fas fa-eye" id="password-icon"></i>
                        </button>
                    </div>
                    <div class="password-strength" id="passwordStrength">
                        <div class="strength-bar">
                            <div class="strength-fill" id="strengthFill"></div>
                        </div>
                        <span class="strength-text" id="strengthText">Password strength</span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">
                        <i class="fas fa-lock"></i>
                        Confirm New Password
                    </label>
                    <div class="password-input">
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            required 
                            autocomplete="new-password"
                            placeholder="Confirm your new password"
                        >
                        <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                            <i class="fas fa-eye" id="confirm-password-icon"></i>
                        </button>
                    </div>
                    <div class="password-match" id="passwordMatch"></div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Update Password
                </button>
            </form>
            <?php else: ?>
            <div class="auth-footer">
                <a href="forgot-password.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i>
                    Request New Reset Link
                </a>
            </div>
            <?php endif; ?>

            <div class="auth-footer">
                <p>Remember your password? <a href="login.php">Sign in here</a></p>
            </div>
        </div>

        <div class="auth-info">
            <div class="info-content">
                <h3>Create a Strong Password</h3>
                <p>Your password is your first line of defense. Make it strong and unique.</p>
                
                <div class="features">
                    <div class="feature">
                        <i class="fas fa-key"></i>
                        <div>
                            <h4>Use a Strong Password</h4>
                            <p>Include uppercase, lowercase, numbers, and special characters for maximum security.</p>
                        </div>
                    </div>
                    
                    <div class="feature">
                        <i class="fas fa-unique"></i>
                        <div>
                            <h4>Make it Unique</h4>
                            <p>Don't reuse passwords from other accounts. Each account should have its own password.</p>
                        </div>
                    </div>
                    
                    <div class="feature">
                        <i class="fas fa-lock"></i>
                        <div>
                            <h4>Keep it Secure</h4>
                            <p>Never share your password with anyone. We'll never ask for it via email or phone.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(fieldId) {
            const passwordInput = document.getElementById(fieldId);
            const iconId = fieldId === 'password' ? 'password-icon' : 'confirm-password-icon';
            const passwordIcon = document.getElementById(iconId);
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.classList.remove('fa-eye');
                passwordIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                passwordIcon.classList.remove('fa-eye-slash');
                passwordIcon.classList.add('fa-eye');
            }
        }

        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthFill = document.getElementById('strengthFill');
            const strengthText = document.getElementById('strengthText');
            
            let strength = 0;
            let strengthLabel = '';
            let strengthColor = '';
            
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            switch (strength) {
                case 0:
                case 1:
                    strengthLabel = 'Very Weak';
                    strengthColor = '#e74c3c';
                    break;
                case 2:
                    strengthLabel = 'Weak';
                    strengthColor = '#f39c12';
                    break;
                case 3:
                    strengthLabel = 'Fair';
                    strengthColor = '#f1c40f';
                    break;
                case 4:
                    strengthLabel = 'Good';
                    strengthColor = '#2ecc71';
                    break;
                case 5:
                    strengthLabel = 'Strong';
                    strengthColor = '#27ae60';
                    break;
            }
            
            strengthFill.style.width = (strength * 20) + '%';
            strengthFill.style.backgroundColor = strengthColor;
            strengthText.textContent = strengthLabel;
            strengthText.style.color = strengthColor;
        }

        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchDiv = document.getElementById('passwordMatch');
            
            if (confirmPassword === '') {
                matchDiv.innerHTML = '';
                return;
            }
            
            if (password === confirmPassword) {
                matchDiv.innerHTML = '<i class="fas fa-check-circle" style="color: #2ecc71;"></i> Passwords match';
            } else {
                matchDiv.innerHTML = '<i class="fas fa-times-circle" style="color: #e74c3c;"></i> Passwords do not match';
            }
        }

        // Event listeners
        document.getElementById('password').addEventListener('input', checkPasswordStrength);
        document.getElementById('confirm_password').addEventListener('input', checkPasswordMatch);

        // Form validation
        document.getElementById('resetForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match.');
                return;
            }
            
            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long.');
                return;
            }
        });

        // Auto-hide messages
        setTimeout(() => {
            const messages = document.querySelectorAll('.message');
            messages.forEach(message => {
                message.style.opacity = '0';
                setTimeout(() => message.remove(), 300);
            });
        }, 5000);
    </script>

    <style>
        /* Password Strength Indicator */
        .password-strength {
            margin-top: 8px;
        }

        .strength-bar {
            width: 100%;
            height: 4px;
            background: #e9ecef;
            border-radius: 2px;
            overflow: hidden;
            margin-bottom: 5px;
        }

        .strength-fill {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
        }

        .strength-text {
            font-size: 0.85rem;
            font-weight: 500;
        }

        .password-match {
            margin-top: 8px;
            font-size: 0.85rem;
            font-weight: 500;
        }
    </style>
</body>
</html>
