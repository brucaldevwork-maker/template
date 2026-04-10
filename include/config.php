<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'template_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Site configuration
define('SITE_URL', 'http://localhost/Template/');
define('SITE_NAME', 'Template System');

try {
    // PDO Connection
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $conn = new PDO($dsn, DB_USER, DB_PASS, $options);
    
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Set timezone
date_default_timezone_set('Asia/Manila');

// ============ ADMIN SESSION FUNCTIONS ============
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_username']) && isset($_SESSION['admin_logged_in']);
}

function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header("Location: " . SITE_URL . "auth/admin_login.php");
        exit();
    }
}

function setAdminSession($user) {
    session_regenerate_id(true);
    $_SESSION['admin_id'] = $user['id'];
    $_SESSION['admin_username'] = $user['username'];
    $_SESSION['admin_email'] = $user['email'];
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['login_time'] = time();
}

function destroyAdminSession() {
    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_username']);
    unset($_SESSION['admin_email']);
    unset($_SESSION['admin_logged_in']);
}

// ============ USER SESSION FUNCTIONS ============
function isUserLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_username']) && isset($_SESSION['user_logged_in']);
}

function requireUserLogin() {
    if (!isUserLoggedIn()) {
        header("Location: " . SITE_URL . "auth/user_login.php");
        exit();
    }
}

function setUserSession($user) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_username'] = $user['username'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_fullname'] = $user['full_name'] ?? $user['username'];
    $_SESSION['user_logged_in'] = true;
    $_SESSION['user_login_time'] = time();
}

function destroyUserSession() {
    unset($_SESSION['user_id']);
    unset($_SESSION['user_username']);
    unset($_SESSION['user_email']);
    unset($_SESSION['user_fullname']);
    unset($_SESSION['user_logged_in']);
}

// ============ COMMON FUNCTIONS ============
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function getCurrentUserType() {
    if (isAdminLoggedIn()) {
        return 'admin';
    } elseif (isUserLoggedIn()) {
        return 'user';
    }
    return 'guest';
}

// Optional: Function to get user by ID using PDO
function getUserById($id, $table = 'users') {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT * FROM $table WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch(PDOException $e) {
        return false;
    }
}

// Optional: Function to get user by email using PDO
function getUserByEmail($email, $table = 'users') {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT * FROM $table WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    } catch(PDOException $e) {
        return false;
    }
}
?>