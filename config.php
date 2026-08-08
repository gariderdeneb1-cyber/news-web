<?php
/**
 * Site configuration.
 * Edit these values after uploading to your cPanel hosting account.
 */

// ── Database (from cPanel → MySQL Databases) ──────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_cpanel_db_name');     // e.g. cpaneluser_news
define('DB_USER', 'your_cpanel_db_user');     // e.g. cpaneluser_newsadmin
define('DB_PASS', 'your_cpanel_db_password');

// ── Site ────────────────────────────────────────────────────────────────
define('SITE_URL', 'https://example.com');    // no trailing slash
define('SITE_ENV', 'production');              // 'production' or 'development'

// Pretty URLs (/news/slug, /category/slug) require the .htaccess mod_rewrite
// rules included in this project. If your host doesn't support mod_rewrite,
// set this to false and the whole site automatically falls back to plain
// query-string URLs (news.php?slug=..., category.php?slug=...).
// Set to false: khaan.mn's cPanel host doesn't apply the /news/... and
// /category/... rewrite rules, so pretty URLs 404 at the Apache level.
define('PRETTY_URLS', false);

// Leave empty if this site's domain/subdomain document root points directly
// at this folder (typical cPanel setup). If you installed it into a
// subfolder instead (e.g. https://example.com/news/), set this to '/news'
// so generated links/assets resolve correctly.
define('BASE_PATH', '');

// ── Uploads ─────────────────────────────────────────────────────────────
define('UPLOAD_DIR', __DIR__ . '/uploads');    // absolute filesystem path
define('UPLOAD_URL', SITE_URL . '/uploads');   // public URL to the same folder
define('MAX_UPLOAD_BYTES', 5 * 1024 * 1024);   // 5 MB

// ── Misc ────────────────────────────────────────────────────────────────
define('SITE_TIMEZONE', 'Asia/Ulaanbaatar');
date_default_timezone_set(SITE_TIMEZONE);

if (SITE_ENV === 'development') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
}
