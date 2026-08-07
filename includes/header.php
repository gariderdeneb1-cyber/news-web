<?php
/**
 * Public site chrome: <head>, sticky header/nav, breaking bar, mobile menu.
 * Include after bootstrap.php. Pages may set $pageTitle, $pageDescription,
 * $canonicalPath, and $extraHead / $extraJsonLd before including this file.
 */

$siteName = $settings['site_name'] ?? 'Мэдээний сайт';
$pageTitle = $pageTitle ?? $siteName;
$fullTitle = $pageTitle === $siteName ? $siteName : ($pageTitle . ' — ' . $siteName);
$pageDescription = $pageDescription ?? ($settings['seo_default_description'] ?? '');
$canonicalPath = $canonicalPath ?? ($_SERVER['REQUEST_URI'] ?? '/');
$canonicalUrl = rtrim(SITE_URL, '/') . $canonicalPath;

$activeCategories = $pdo->query(
    'SELECT id, name, slug, color FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC'
)->fetchAll();

$breakingArticles = $pdo->query(
    "SELECT id, title, slug FROM articles WHERE status = 'published' AND is_breaking = 1 ORDER BY published_at DESC LIMIT 8"
)->fetchAll();

$currentPath = strtok($_SERVER['REQUEST_URI'] ?? '', '?');
?><!doctype html>
<html lang="mn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($fullTitle) ?></title>
<?php if ($pageDescription): ?><meta name="description" content="<?= e($pageDescription) ?>"><?php endif; ?>
<link rel="canonical" href="<?= e($canonicalUrl) ?>">
<?php if (!empty($settings['favicon_url'])): ?><link rel="icon" href="<?= e($settings['favicon_url']) ?>"><?php endif; ?>

<meta property="og:site_name" content="<?= e($siteName) ?>">
<meta property="og:title" content="<?= e($pageTitle) ?>">
<?php if ($pageDescription): ?><meta property="og:description" content="<?= e($pageDescription) ?>"><?php endif; ?>
<meta property="og:type" content="<?= e($ogType ?? 'website') ?>">
<meta property="og:url" content="<?= e($canonicalUrl) ?>">
<?php if (!empty($ogImage)): ?><meta property="og:image" content="<?= e($ogImage) ?>"><?php endif; ?>
<meta name="twitter:card" content="<?= !empty($ogImage) ? 'summary_large_image' : 'summary' ?>">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;500;600;700;800&family=Noto+Sans+Mongolian:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset('/assets/css/style.css')) ?>">
<?php if (!empty($settings['primary_color'])): ?>
<style>
  :root, :root[data-theme="dark"] { --primary: <?= e($settings['primary_color']) ?>; --primary-hover: <?= e(darken_hex($settings['primary_color'])) ?>; }
</style>
<?php endif; ?>
<script>
(function () {
  try {
    var t = localStorage.getItem('theme');
    if (t) document.documentElement.setAttribute('data-theme', t);
  } catch (e) {}
})();
</script>
<?php if (!empty($settings['analytics_id'])): ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($settings['analytics_id']) ?>"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', '<?= e($settings['analytics_id']) ?>');
</script>
<?php endif; ?>
<?= $extraHead ?? '' ?>
<?= $extraJsonLd ?? '' ?>
</head>
<body>
<a href="#main-content" class="skip-link">Үндсэн агуулга руу шилжих</a>

<?php if ($breakingArticles): ?>
<div class="breaking-bar">
  <div class="container breaking-bar__inner">
    <span class="breaking-bar__label">Шуурхай</span>
    <div class="breaking-bar__viewport">
      <div class="breaking-bar__track">
        <?php foreach (array_merge($breakingArticles, $breakingArticles) as $b): ?>
          <a href="<?= e(url_news($b['slug'])) ?>"><?= e($b['title']) ?></a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<header class="site-header">
  <div class="container site-header__inner">
    <button type="button" class="icon-btn mobile-menu-btn" data-menu-open aria-label="Цэс нээх">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>

    <a href="<?= e(url_home()) ?>" class="logo">
      <?php if (!empty($settings['logo_url']) || !empty($settings['logo_dark_url'])): ?>
        <?php $logoLight = $settings['logo_url'] ?? ''; $logoDarkVariant = $settings['logo_dark_url'] ?? ''; ?>
        <img src="<?= e(resolve_media_url($logoLight ?: $logoDarkVariant)) ?>" alt="<?= e($siteName) ?>" class="logo-light">
        <img src="<?= e(resolve_media_url($logoDarkVariant ?: $logoLight)) ?>" alt="<?= e($siteName) ?>" class="logo-dark">
      <?php else: ?>
        <span style="font-weight:800;font-size:1.15rem;color:var(--foreground)"><?= e($siteName) ?></span>
      <?php endif; ?>
    </a>

    <nav class="main-nav no-scrollbar" aria-label="Ангилал">
      <?php foreach ($activeCategories as $cat): ?>
        <a href="<?= e(url_category($cat['slug'])) ?>" class="<?= str_starts_with($currentPath, url_category($cat['slug'])) ? 'is-active' : '' ?>"><?= e($cat['name']) ?></a>
      <?php endforeach; ?>
    </nav>

    <div class="header-actions">
      <?php if ($breakingArticles): ?>
        <span class="badge badge-breaking" style="margin-right:.4rem"><span class="dot"></span>Шуурхай</span>
      <?php endif; ?>
      <span class="header-date"><?= e(format_mn_date(new DateTime())) ?></span>
      <a href="<?= e(url_search()) ?>" class="icon-btn" aria-label="Нийтлэл хайх">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      </a>
      <?php if (!empty($settings['dark_mode_enabled'])): ?>
      <button type="button" class="icon-btn" data-theme-toggle aria-label="Өнгө солих">
        <svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
        <svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
      </button>
      <?php endif; ?>
    </div>
  </div>
</header>

<div class="mobile-menu-overlay" data-menu-overlay></div>
<nav class="mobile-menu" data-menu aria-label="Гар утасны цэс">
  <button type="button" class="icon-btn mobile-menu__close" data-menu-close aria-label="Цэс хаах">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
  </button>
  <a href="<?= e(url_home()) ?>">Нүүр</a>
  <?php foreach ($activeCategories as $cat): ?>
    <a href="<?= e(url_category($cat['slug'])) ?>"><?= e($cat['name']) ?></a>
  <?php endforeach; ?>
  <a href="<?= e(url_search()) ?>">Хайх</a>
</nav>

<main id="main-content">
