<?php
/**
 * Optional. The site already self-heals scheduled publishing on every page
 * view (see includes/bootstrap.php), so this script is only useful if you
 * want articles to flip to "published" at the exact scheduled minute even
 * when nobody is visiting the site. Point a cPanel Cron Job at it, e.g.
 * every 5 minutes:
 *
 *   php /home/USERNAME/public_html/cron/publish-scheduled.php
 *
 * Safe to run as often as you like — it's just an idempotent UPDATE.
 */
require_once __DIR__ . '/../includes/db.php';

$stmt = $pdo->prepare(
    "UPDATE articles SET status = 'published', published_at = COALESCE(published_at, scheduled_at)
     WHERE status = 'scheduled' AND scheduled_at IS NOT NULL AND scheduled_at <= NOW()"
);
$stmt->execute();

echo date('Y-m-d H:i:s') . ' — published ' . $stmt->rowCount() . " scheduled article(s).\n";
