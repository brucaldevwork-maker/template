<?php
require_once '../include/config.php';
requireAdminLogin(); // Uses your helper from config.php

// Optional: Validate session binding for extra security
// if (!validateSessionBinding()) { destroyAdminSession(); header("Location: admin_login.php"); exit(); }

// Initialize stats with defaults to avoid undefined variables
$stats = [
    'total_admins' => 0,
    'total_users' => 0,
    'active_users_24h' => 0, // Bonus: track recent activity
];

try {
    // Count total admins (use your actual admin table name)
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM admin_users");
    $stmt->execute();
    $stats['total_admins'] = (int) $stmt->fetchColumn();

    // Count total users
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users");
    $stmt->execute();
    $stats['total_users'] = (int) $stmt->fetchColumn();

    // Bonus: Count users active in last 24h (requires last_login column)
    // $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE last_login >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    // $stmt->execute();
    // $stats['active_users_24h'] = (int) $stmt->fetchColumn();

} catch (PDOException $e) {
    // Log error internally, don't expose to user
    error_log("Dashboard stats error: " . $e->getMessage());
    // Stats remain at default 0 - page still renders
}

// Format login time from session
$login_time = isset($_SESSION['login_time']) 
    ? date('F j, Y g:i A', (int) $_SESSION['login_time']) 
    : 'Unknown';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Admin Dashboard - <?php echo htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8'); ?></title>
    <style>
        :root {
            --primary: #667eea; --primary-hover: #5a67d8;
            --success: #22c55e; --warning: #f59e0b; --danger: #ef4444;
            --bg: #f8fafc; --card-bg: #ffffff; --text-primary: #1e293b;
            --text-secondary: #64748b; --border: #e2e8f0; --shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: var(--bg); color: var(--text-primary);
            line-height: 1.6;
        }
        .container {
            max-width: 1200px; margin: 2rem auto; padding: 0 1.5rem;
        }
        
        /* Welcome Card */
        .welcome-card {
            background: var(--card-bg); border-radius: 12px;
            padding: 2rem; box-shadow: var(--shadow);
            margin-bottom: 2rem; display: flex;
            justify-content: space-between; align-items: flex-start;
            gap: 1.5rem; flex-wrap: wrap;
        }
        .welcome-content h1 { font-size: 1.5rem; margin-bottom: 0.5rem; }
        .welcome-content p { color: var(--text-secondary); margin: 0.25rem 0; font-size: 0.95rem; }
        .welcome-actions { text-align: right; }
        
        .badge {
            display: inline-flex; align-items: center; gap: 0.375rem;
            padding: 0.375rem 0.75rem; border-radius: 9999px;
            font-size: 0.8rem; font-weight: 500;
            background: #dcfce7; color: #166534;
        }
        
        /* Stats Grid */
        .stats {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.25rem; margin-bottom: 2rem;
        }
        .stat-card {
            background: var(--card-bg); border-radius: 12px;
            padding: 1.5rem; box-shadow: var(--shadow);
            text-align: center; transition: transform 0.2s, box-shadow 0.2s;
            border: 1px solid var(--border);
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.15);
        }
        .stat-number {
            font-size: 2.25rem; font-weight: 700;
            color: var(--primary); margin: 0.5rem 0;
            line-height: 1;
        }
        .stat-label { color: var(--text-secondary); font-size: 0.9rem; }
        .stat-trend {
            font-size: 0.8rem; margin-top: 0.5rem;
            display: flex; align-items: center; justify-content: center; gap: 0.25rem;
        }
        .trend-up { color: var(--success); }
        .trend-down { color: var(--danger); }
        
        /* Quick Actions */
        .quick-actions {
            background: var(--card-bg); border-radius: 12px;
            padding: 1.5rem; box-shadow: var(--shadow);
            margin-bottom: 2rem; border: 1px solid var(--border);
        }
        .quick-actions h3 { margin-bottom: 1rem; font-size: 1.1rem; }
        .action-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 0.75rem;
        }
        .action-btn {
            display: flex; align-items: center; gap: 0.75rem;
            padding: 0.75rem 1rem; border-radius: 8px;
            text-decoration: none; color: var(--text-primary);
            background: #f1f5f9; border: 1px solid var(--border);
            font-weight: 500; font-size: 0.95rem;
            transition: all 0.2s;
        }
        .action-btn:hover {
            background: var(--primary); color: white;
            border-color: var(--primary);
        }
        .action-btn.danger:hover {
            background: var(--danger); border-color: var(--danger);
        }
        
        /* Footer Actions */
        .footer-actions {
            text-align: center; padding-top: 1.5rem;
            border-top: 1px solid var(--border);
        }
        .logout-btn {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.75rem 1.5rem; background: var(--danger);
            color: white; text-decoration: none; border-radius: 8px;
            font-weight: 600; transition: background 0.2s;
        }
        .logout-btn:hover { background: #dc2626; }
        
        /* Responsive */
        @media (max-width: 768px) {
            .welcome-card { flex-direction: column; text-align: center; }
            .welcome-actions { text-align: center; width: 100%; }
        }
    </style>
</head>
<body>
    <?php include '../include/admin_nav.php'; ?>
    
    <div class="container">
        <!-- Welcome Section -->
        <div class="welcome-card">
            <div class="welcome-content">
                <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin', ENT_QUOTES, 'UTF-8'); ?> 👋</h1>
                <p>📧 <?php echo htmlspecialchars($_SESSION['admin_email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                <p>🆔 ID: #<?php echo (int) ($_SESSION['admin_id'] ?? 0); ?></p>
                <p>🕐 Session started: <?php echo htmlspecialchars($login_time, ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <div class="welcome-actions">
                <span class="badge">🔐 Administrator</span>
            </div>
        </div>
        
        <!-- Stats Overview -->
        <div class="stats">
            <div class="stat-card">
                <div style="font-size: 1.5rem;">👥</div>
                <div class="stat-number"><?php echo number_format($stats['total_admins']); ?></div>
                <div class="stat-label">Total Admins</div>
            </div>
            <div class="stat-card">
                <div style="font-size: 1.5rem;">🧑‍💼</div>
                <div class="stat-number"><?php echo number_format($stats['total_users']); ?></div>
                <div class="stat-label">Total Users</div>
                <?php if (isset($stats['active_users_24h'])): ?>
                    <div class="stat-trend trend-up">
                        ↑ <?php echo (int) $stats['active_users_24h']; ?> active today
                    </div>
                <?php endif; ?>
            </div>
            <div class="stat-card">
                <div style="font-size: 1.5rem;">📅</div>
                <div class="stat-number"><?php echo date('Y'); ?></div>
                <div class="stat-label">Current Year</div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="quick-actions">
            <h3>⚡ Quick Actions</h3>
            <div class="action-grid">
                <a href="manage_users.php" class="action-btn">
                    👤 Manage Users
                </a>
                <a href="manage_admins.php" class="action-btn">
                    🔐 Manage Admins
                </a>
                <a href="audit_logs.php" class="action-btn">
                    📋 Audit Logs
                </a>
                <a href="settings.php" class="action-btn">
                    ⚙️ Settings
                </a>
                <a href="../auth/admin_logout.php" class="action-btn danger">
                    🚪 Logout
                </a>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer-actions">
            <a href="../auth/admin_logout.php" class="logout-btn">
                🚪 Secure Logout
            </a>
            <p style="margin-top: 1rem; color: var(--text-secondary); font-size: 0.85rem;">
                Last page load: <?php echo date('Y-m-d H:i:s'); ?> • 
                Server Time: <?php echo date('Y-m-d H:i:s'); ?>
            </p>
        </div>
    </div>

    <!-- Optional: Auto-logout warning (uncomment to enable) -->
    <!--
    <script>
        // Warn user 5 minutes before session timeout (if you implement timeout)
        const SESSION_TIMEOUT = 30 * 60 * 1000; // 30 minutes
        const WARNING_TIME = 5 * 60 * 1000; // 5 minutes warning
        
        setTimeout(() => {
            if (confirm("⚠️ Your session will expire soon. Stay logged in?")) {
                // Ping server to extend session
                fetch('../include/keepalive.php').catch(() => {});
            }
        }, SESSION_TIMEOUT - WARNING_TIME);
    </script>
    -->
</body>
</html>