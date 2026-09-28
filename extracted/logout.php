<?php
require_once __DIR__ . '/includes/functions.php';
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', (bool)$p['secure'], (bool)$p['httponly']);
}
if (session_status() === PHP_SESSION_ACTIVE) session_destroy();
header('Location: /');
exit;
