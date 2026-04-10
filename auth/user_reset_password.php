<?php
require_once '../include/config.php';

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

// Validate token presence
if (empty($token)) {
    header("Location: user_login.php");
    exit();
}

// ⚠️ Schema note: Run this migration ONCE via CLI or setup script, NOT on every page load
// ALTER TABLE users ADD COLUMN reset_token VARCHAR(255) DEFAULT NULL, ADD COLUMN reset_expires DATETIME DEFAULT NULL;

try {
    // Verify token: use hashed token comparison in production (see senior notes below)
    $stmt = $conn->prepare("SELECT id, email, username FROM users WHERE reset_token = ? AND reset_expires > NOW() AND reset_token IS NOT NULL");
    $stmt->execute([$token]);
    $user = $stmt->fetch(); // PDO::FETCH_ASSOC is default from config.php

    if (!$user) {
        $error = "Invalid or expired reset token. Please request a new password reset.";
    } else {
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Basic CSRF check (add real tokens in production)
            if (!hash_equals($_SESSION['reset_csrf'] ?? '', $_POST['csrf_token'] ?? '')) {
                $error = "Invalid request. Please try again.";
            } else {
                $password = $_POST['password'] ?? '';
                $confirm_password = $_POST['confirm_password'] ?? '';
                
                // Validation
                if (strlen($password) < 8) {
                    $error = "Password must be at least 8 characters";
                } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
                    $error = "Password must include uppercase letter and number";
                } elseif ($password !== $confirm_password) {
                    $error = "Passwords do not match";
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Update password and invalidate token
                    $update = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL, updated_at = NOW() WHERE id = ?");
                    $success = $update->execute([$hashed_password, $user['id']]);
                    
                    if ($success) {
                        // Optional: Log the password reset event
                        // error_log("Password reset for user ID: {$user['id']}");
                        
                        // Redirect to login with success message (PRG pattern)
                        $_SESSION['login_success'] = "Password reset successful! Please login with your new password.";
                        header("Location: user_login.php");
                        exit();
                    } else {
                        $error = "Failed to reset password. Please try again.";
                    }
                }
            }
        } else {
            // Generate CSRF token for the form on GET request
            if (empty($_SESSION['reset_csrf'])) {
                $_SESSION['reset_csrf'] = bin2hex(random_bytes(32));
            }
        }
    }
} catch (PDOException $e) {
    // Log error internally, show generic message to user
    error_log("Reset password error: " . $e->getMessage());
    $error = "An unexpected error occurred. Please try again later.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Reset Password - <?php echo htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8'); ?></title>
    <style>
        :root {
            --primary: #667eea;
            --primary-hover: #5a67d8;
            --error-bg: #f8d7da; --error-text: #721c24;
            --success-bg: #d4edda; --success-text: #155724;
            --input-border: #ddd; --text-primary: #333; --text-secondary: #666;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex; justify-content: center; align-items: center;
            padding: 20px; color: var(--text-primary);
        }
        .container {
            background: white; border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            width: 100%; max-width: 480px; padding: 2.5rem;
        }
        h2 { text-align: center; margin-bottom: 0.5rem; }
        .user-type { text-align: center; color: var(--primary); font-size: 0.9rem; margin-bottom: 1rem; }
        .user-info { text-align: center; color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 1.5rem; }
        
        .alert { padding: 0.875rem 1rem; border-radius: 8px; margin-bottom: 1.25rem; text-align: center; font-size: 0.95rem; }
        .alert.error { background: var(--error-bg); color: var(--error-text); }
        .alert.success { background: var(--success-bg); color: var(--success-text); }
        
        .form-group { margin-bottom: 1.25rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: #444; }
        input {
            width: 100%; padding: 0.75rem 1rem;
            border: 1px solid var(--input-border); border-radius: 8px;
            font-size: 1rem; transition: border-color 0.2s;
        }
        input:focus { outline: none; border-color: var(--primary); }
        
        .password-hint { font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.25rem; }
        
        button {
            width: 100%; padding: 0.875rem;
            background: var(--primary); color: white; border: none;
            border-radius: 8px; font-size: 1rem; font-weight: 600;
            cursor: pointer; transition: background 0.2s;
        }
        button:hover { background: var(--primary-hover); }
        button:disabled { opacity: 0.7; cursor: not-allowed; }
        
        .links { text-align: center; margin-top: 1.5rem; }
        .links a { color: var(--primary); text-decoration: none; font-weight: 500; }
        .links a:hover { text-decoration: underline; }
        
        .back-link { text-align: center; margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid #eee; }
        .back-link a { color: var(--text-secondary); text-decoration: none; font-size: 0.9rem; }
        .back-link a:hover { color: var(--primary); }
        
        /* Password strength indicator */
        .strength-meter { height: 4px; background: #eee; border-radius: 2px; margin-top: 0.5rem; overflow: hidden; }
        .strength-fill { height: 100%; width: 0%; transition: all 0.3s; }
        .strength-weak { background: #ef4444; width: 33%; }
        .strength-medium { background: #f59e0b; width: 66%; }
        .strength-strong { background: #22c55e; width: 100%; }
    </style>
</head>
<body>
    <div class="container">
        <h2>🔐 Reset Password</h2>
        <div class="user-type">User Account</div>
        
        <?php if (isset($user) && !$success): ?>
            <div class="user-info">
                Resetting password for: <strong><?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></strong>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="links">
                <a href="user_login.php">→ Click here to login ←</a>
            </div>
        <?php else: ?>
            <form method="POST" action="" id="resetForm">
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['reset_csrf'] ?? ''; ?>">
                
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password">
                    <div class="password-hint">Min. 8 chars, 1 uppercase, 1 number</div>
                    <div class="strength-meter"><div class="strength-fill" id="strengthFill"></div></div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password">
                </div>
                
                <button type="submit" id="submitBtn">Reset Password</button>
            </form>
            
            <div class="links">
                <a href="user_login.php">← Back to Login</a>
            </div>
        <?php endif; ?>
        
        <div class="back-link">
            <a href="../index.php">← Back to Home</a>
        </div>
    </div>

    <script>
        // Password strength meter (client-side UX only)
        const passwordInput = document.getElementById('password');
        const strengthFill = document.getElementById('strengthFill');
        
        passwordInput?.addEventListener('input', function() {
            const val = this.value;
            let strength = 0;
            if (val.length >= 8) strength++;
            if (/[A-Z]/.test(val) && /[0-9]/.test(val)) strength++;
            if (/[^A-Za-z0-9]/.test(val)) strength++;
            
            strengthFill.className = 'strength-fill';
            if (strength === 1) strengthFill.classList.add('strength-weak');
            else if (strength === 2) strengthFill.classList.add('strength-medium');
            else if (strength >= 3) strengthFill.classList.add('strength-strong');
        });
        
        // Prevent form resubmission on back/refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>