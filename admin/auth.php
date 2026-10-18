<?php
/**
 * Authentication Module cho Admin Panel bằng Mật Khẩu 6 Số
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config.php';

function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function requireAdmin() {
    if (!isAdminLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function verifyAdminPin($pin) {
    // Mật khẩu cố định trong code (ở config.php). Chỉ đổi được trong file code theo yêu cầu bảo mật!
    $correctPin = defined('ADMIN_PASSWORD') ? constant('ADMIN_PASSWORD') : '123456';
    return trim((string)$pin) === trim((string)$correctPin);
}
