<?php
/**
 * Returns a raw HTML fragment (no <head>/<body>) of the next page of latest
 * published articles, for main.js's "load more" button on the homepage.
 * Page 1 is already rendered server-side by index.php, so this only ever
 * serves page >= 2.
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/article-card.php';

$perPage = max(4, min(48, (int) ($settings['articles_per_page'] ?? 12)));
$page = max(2, (int) ($_GET['page'] ?? 2));
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare(
    "SELECT " . ARTICLE_LIST_SELECT . " WHERE a.status = 'published' ORDER BY a.published_at DESC LIMIT :lim OFFSET :off"
);
$stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset, PDO::PARAM_INT);
$stmt->execute();
$articles = $stmt->fetchAll();

header('Content-Type: text/html; charset=UTF-8');
foreach ($articles as $a) {
    echo '<div>' . render_card_grid($a) . '</div>';
}
