<?php
require_once __DIR__ . '/bootstrap.php';

function is_admin_logged_in(): bool {
    return !empty($_SESSION['admin_logged_in']);
}

function require_admin_login(): void {
    if (!is_admin_logged_in()) {
        $redirect = $_SERVER['REQUEST_URI'] ?? 'admin.php';
        header('Location: admin_login.php?redirect=' . urlencode($redirect));
        exit;
    }
}

function do_admin_login(string $password): bool {
    if (password_verify($password, ADMIN_PASS_HASH)) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_logged_in_at'] = time();
        return true;
    }
    return false;
}

function do_admin_logout(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"] ?? '',
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}
