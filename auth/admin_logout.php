<?php
require_once '../include/config.php';

// Destroy admin session
destroyAdminSession();

// Redirect to login page with logout success message
header("Location: admin_login.php?logout=success");
exit();
?>