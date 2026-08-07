<?php
/**
 * Session guard — require_once this at the very top of every protected
 * admin page (before any output). Defines ADMIN_CONTEXT so bootstrap.php's
 * maintenance-mode gate doesn't lock the admin out of their own site.
 */

define('ADMIN_CONTEXT', true);
require_once __DIR__ . '/../../includes/bootstrap.php';

if (empty($_SESSION['admin_id'])) {
    $next = urlencode($_SERVER['REQUEST_URI'] ?? '');
    redirect(base_path('/admin/login.php?next=' . $next));
}

$stmt = $pdo->prepare('SELECT id, name, email FROM admins WHERE id = :id');
$stmt->execute([':id' => $_SESSION['admin_id']]);
$currentAdmin = $stmt->fetch();

if (!$currentAdmin) {
    $_SESSION = [];
    session_destroy();
    redirect(base_path('/admin/login.php'));
}
