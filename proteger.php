<?php
// Enhanced session protection

// Start session with security settings if not already started
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Session timeout (30 minutes)
$timeout = 30 * 60; // 30 minutes
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > $timeout) {
    session_destroy();
    header('Location: login.php?timeout=1');
    exit;
}

// Session hijacking protection
if (!isset($_SESSION['user_agent'])) {
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
} else {
    if ($_SESSION['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
        session_destroy();
        header('Location: login.php?security=1');
        exit;
    }
}

// Update last activity time
$_SESSION['login_time'] = time();

// Regenerate session ID periodically (every 10 minutes)
if (!isset($_SESSION['last_regeneration']) || (time() - $_SESSION['last_regeneration']) > 600) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}
?>
