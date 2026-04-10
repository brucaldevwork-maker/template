<?php
require_once '../include/config.php';

// Destroy user session
destroyUserSession();

// Redirect to index page with logout success message
header("Location: ../index.php?logout=success");
exit();
?>