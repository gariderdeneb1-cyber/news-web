<?php
require_once __DIR__ . '/includes/bootstrap.php';
header('Content-Type: application/xml; charset=UTF-8');

$articles = $pdo->query(
    "SELECT slug, updated_at FROM articles WHERE status = 'published' ORDER BY published_at DESC LIMIT 5000"
)->fetchAll();
$categories = $pdo->query('SELECT slug, updated_at FROM categories WHERE is_active = 1')->fetchAll();

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url><loc><?= e(rtrim(SITE_URL, '/') . url_home()) ?></loc></url>
  <url><loc><?= e(rtrim(SITE_URL, '/') . url_search()) ?></loc></url>
<?php foreach ($categories as $c): ?>
  <url>
    <loc><?= e(rtrim(SITE_URL, '/') . url_category($c['slug'])) ?></loc>
    <lastmod><?= e(date('c', strtotime($c['updated_at']))) ?></lastmod>
  </url>
<?php endforeach; ?>
<?php foreach ($articles as $a): ?>
  <url>
    <loc><?= e(rtrim(SITE_URL, '/') . url_news($a['slug'])) ?></loc>
    <lastmod><?= e(date('c', strtotime($a['updated_at']))) ?></lastmod>
  </url>
<?php endforeach; ?>
</urlset>
