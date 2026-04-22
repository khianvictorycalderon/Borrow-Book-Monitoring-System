<?php
session_start();

// Clear all session data
$_SESSION = [];

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to login page using the app's root directory
// __DIR__ is /path/to/htdocs/Borrow-Book-Monitoring-System/api
// We go one level up to get the app root, then build a URL from SCRIPT_NAME
$script_parts = explode('/', trim($_SERVER['SCRIPT_NAME'], '/'));
// e.g. ["Borrow-Book-Monitoring-System", "api", "logout.php"]
// The app root segment is $script_parts[0]
$app_root = '/' . $script_parts[0];

header("Location: " . $app_root . "/");
exit();