<?php
/**
 * Opens the admin HTML shell: sidebar + topbar. Include after admin-auth.php
 * and after setting $adminPageTitle and $adminActiveNav
 * ('dashboard'|'articles'|'categories'|'settings'). Close with
 * admin-layout-bottom.php.
 */
$adminPageTitle = $adminPageTitle ?? 'Админ';
$adminActiveNav = $adminActiveNav ?? '';
$flashes = flash_get();

function admin_nav_class(string $key, string $active): string
{
    return $key === $active ? 'is-active' : '';
}
?><!doctype html>
<html lang="mn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?= e($adminPageTitle) ?> — Админ</title>
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<link rel="stylesheet" href="<?= e(asset('/assets/css/style.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('/assets/css/admin.css')) ?>">
<script>
(function () { try { var t = localStorage.getItem('theme'); if (t) document.documentElement.setAttribute('data-theme', t); } catch (e) {} })();
</script>
<?= $adminExtraHead ?? '' ?>
</head>
<body>
<div class="admin-body">
  <aside class="admin-sidebar" data-admin-sidebar>
    <div class="admin-sidebar__brand">
      <span class="mark">Ад</span>
      <span><?= e($settings['site_name'] ?? 'Админ') ?></span>
    </div>
    <nav class="admin-nav">
      <a href="<?= e(base_path('/admin/')) ?>" class="<?= admin_nav_class('dashboard', $adminActiveNav) ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        Хянах самбар
      </a>
      <a href="<?= e(base_path('/admin/articles.php')) ?>" class="<?= admin_nav_class('articles', $adminActiveNav) ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="16" y2="17"/></svg>
        Нийтлэл
      </a>
      <a href="<?= e(base_path('/admin/categories.php')) ?>" class="<?= admin_nav_class('categories', $adminActiveNav) ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7z"/></svg>
        Ангилал
      </a>
      <a href="<?= e(base_path('/admin/settings.php')) ?>" class="<?= admin_nav_class('settings', $adminActiveNav) ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
        Тохиргоо
      </a>
    </nav>
    <div class="admin-sidebar__footer">
      <a href="<?= e(url_home()) ?>" target="_blank" rel="noopener noreferrer">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
        Сайтыг үзэх
      </a>
      <a href="<?= e(base_path('/admin/logout.php')) ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        Гарах
      </a>
    </div>
  </aside>
  <div class="admin-sidebar-overlay" data-admin-sidebar-overlay></div>

  <div class="admin-main">
    <div class="admin-topbar">
      <button type="button" class="icon-btn admin-sidebar-toggle" data-admin-sidebar-toggle aria-label="Цэс">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
      <h1><?= e($adminPageTitle) ?></h1>
      <div class="admin-topbar__spacer"></div>
      <span style="font-size:.82rem;color:var(--foreground-muted)"><?= e($currentAdmin['name'] ?? '') ?></span>
    </div>
    <div class="admin-content">
      <?php foreach ($flashes as $f): ?>
        <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
      <?php endforeach; ?>
