<?php
require_once __DIR__ . '/includes/bootstrap.php';
header('Content-Type: text/plain; charset=UTF-8');
?>
User-agent: *
Allow: /
Disallow: /admin/
Disallow: /ajax/
Disallow: /includes/
Disallow: /sql/

Sitemap: <?= rtrim(SITE_URL, '/') ?>/sitemap.php
