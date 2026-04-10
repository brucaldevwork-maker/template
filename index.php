<?php
require_once 'include/config.php';

// Check who is logged in
$isAdmin = isAdminLoggedIn();
$isUser = isUserLoggedIn();

// Get user info
$userName = '';
$userType = '';

if ($isAdmin) {
    $userName = $_SESSION['admin_username'];
    $userType = 'Administrator';
    $userIcon = '👑';
    $userColor = '#667eea';
} elseif ($isUser) {
    $userName = $_SESSION['user_username'];
    $userType = 'Regular User';
    $userIcon = '👤';
    $userColor = '#48bb78';
} else {
    $userType = 'Guest';
    $userIcon = '👋';
    $userColor = '#a0aec0';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .container {
            background: white;
            border-radius: 20px;
            padding: 50px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 500px;
            width: 90%;
        }
        .icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 32px;
        }
        .status {
            font-size: 18px;
            color: #666;
            margin-bottom: 30px;
        }
        .user-card {
            background: <?php echo $userColor; ?>;
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin: 20px 0;
        }
        .user-name {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .user-type {
            font-size: 16px;
            opacity: 0.95;
        }
        .buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: scale(1.05);
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-secondary {
            background: #48bb78;
            color: white;
        }
        .btn-gray {
            background: #a0aec0;
            color: white;
        }
        .logout-link {
            margin-top: 20px;
        }
        .logout-link a {
            color: #dc3545;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon"><?php echo $userIcon; ?></div>
        <h1><?php echo SITE_NAME; ?></h1>
        <div class="status">Login Status</div>
        
        <div class="user-card">
            <div class="user-name">
                <?php 
                if ($isAdmin || $isUser) {
                    echo htmlspecialchars($userName);
                } else {
                    echo 'Not Logged In';
                }
                ?>
            </div>
            <div class="user-type">
                <?php echo $userType; ?>
            </div>
        </div>
        
        <div class="buttons">
            <?php if ($isAdmin): ?>
                <a href="admin/admin_dashboard.php" class="btn btn-primary">📊 Dashboard</a>
                <a href="auth/admin_logout.php" class="btn btn-danger">🚪 Logout</a>
            <?php elseif ($isUser): ?>
                <a href="auth/user_logout.php" class="btn btn-danger">🚪 Logout</a>
            <?php else: ?>
                <a href="auth/admin_login.php" class="btn btn-primary">👨‍💼 Admin Login</a>
                <a href="auth/user_login.php" class="btn btn-secondary">👤 User Login</a>
            <?php endif; ?>
        </div>
        
        <?php if (!$isAdmin && !$isUser): ?>
        <div class="logout-link" style="margin-top: 20px;">
            <small>Don't have an account? <a href="auth/admin_register.php">Register as Admin</a> | <a href="auth/user_register.php">Register as User</a></small>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>