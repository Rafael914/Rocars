<?php
require_once 'config.php';
require_once 'function.php';

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_httponly', 1);
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$currentPage = basename($_SERVER['PHP_SELF']);
$publicPages = ['login.php', 'register.php'];

if (!isLoggedIn() && !in_array($currentPage, $publicPages)) {
    header("Location: login.php");
    exit();
}
