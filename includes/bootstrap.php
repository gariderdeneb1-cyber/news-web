<?php
/**
 * Required at the top of every PUBLIC page (not admin/ — that has its own
 * admin-auth.php include with the same session setup but no maintenance
 * gate). Starts the session, loads settings, self-heals due scheduled
 * articles, and enforces maintenance mode.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/settings.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

$settings = load_site_settings($pdo);

// Self-healing scheduled publish: on any page view, flip due "scheduled"
// articles to "published". Keeps timing correct even without a real cron
// job; sql/../cron/publish-scheduled.php does the same thing for setups
// that do add a cPanel Cron Job for exact-minute timing.
$pdo->exec(
    "UPDATE articles SET status = 'published', published_at = COALESCE(published_at, scheduled_at)
     WHERE status = 'scheduled' AND scheduled_at IS NOT NULL AND scheduled_at <= NOW()"
);

if (!empty($settings['maintenance_mode']) && !defined('ADMIN_CONTEXT')) {
    http_response_code(503);
    require __DIR__ . '/maintenance.php';
    exit;
}
