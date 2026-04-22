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

// This file lives at: <app-root>/api/logout.php
// dirname(__FILE__)      => <app-root>/api
// dirname(dirname(...))  => <app-root>
// Subtract DOCUMENT_ROOT to get the URL-relative app path.
$doc_root  = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
$app_root  = rtrim(str_replace('\\', '/', dirname(dirname(__FILE__))), '/');
$app_path  = str_replace($doc_root, '', $app_root); // e.g. "/Borrow-Book-Monitoring-System"

header("Location: " . $app_path . "/");
exit();