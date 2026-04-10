<?php
require_once '../include/config.php'; // Loads session + helper functions

// One-liner: redirects if not logged in
requireAdminLogin();

// ✅ Admin is authenticated - continue with page logic
?>

<nav style="background: #333; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
    <div style="font-size: 20px; font-weight: bold;">
        🏠 <?php echo SITE_NAME; ?>
    </div>
    <div>
        <span style="margin-right: 20px;">👋 Welcome, <strong><?php echo htmlspecialchars($_SESSION['admin_username']); ?></strong></span>
        <a href="<?php echo SITE_URL; ?>admin/admin_dashboard.php" style="color: white; margin-right: 15px; text-decoration: none; padding: 5px 10px; background: #555; border-radius: 3px;">Dashboard</a>
        <a href="<?php echo SITE_URL; ?>auth/admin_logout.php" style="color: white; text-decoration: none; padding: 5px 10px; background: #dc3545; border-radius: 3px;">Logout</a>
    </div>
</nav>