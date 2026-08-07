# Khaan.mn — News CMS (PHP / MySQL, cPanel-ready)

A news website with a full admin panel (articles, categories, site-wide
settings), built as plain **PHP 8 + MySQL + HTML/CSS/vanilla JS** — no
Node.js, no Composer, no build step. Upload it to any cPanel shared-hosting
account and it runs.

## Requirements

Virtually every cPanel host satisfies these by default:

- PHP **8.1+** (cPanel → *MultiPHP Manager* / *Select PHP Version* to set it per-domain)
- PHP extensions: `pdo_mysql`, `mbstring`, `dom` (all enabled by default on cPanel)
- MySQL or MariaDB database

## Deploying to cPanel

### 1. Create the database

In cPanel → **MySQL® Databases**:
1. Create a database (e.g. `news`) — cPanel will prefix it, giving something like `cpaneluser_news`.
2. Create a database user with a strong password.
3. Add that user to the database with **All Privileges**.

### 2. Import the schema

cPanel → **phpMyAdmin** → select the new database → **Import** → choose
[`sql/schema.sql`](sql/schema.sql) → Go.

This creates all tables and seeds 8 categories, ~15 demo articles, and the
`Khaan.mn` branding (site name + logo). No admin account is created here on
purpose — you'll create it in step 6. Demo articles are ordinary rows; edit
or delete them from the admin panel whenever you like.

### 3. Upload the files

Two options:
- **File Manager**: zip this whole folder, upload the zip into `public_html`
  (or your subdomain's document root), then extract it there.
- **FTP/SFTP**: upload everything the same way.

Either way, `config.php`, `index.php`, `admin/`, etc. should end up directly
inside `public_html/` (not inside an extra nested folder).

### 4. Configure `config.php`

Edit it directly in cPanel's File Manager (or locally, then re-upload) and fill in:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'cpaneluser_news');       // from step 1
define('DB_USER', 'cpaneluser_newsuser');   // from step 1
define('DB_PASS', 'the-password-you-set');

define('SITE_URL', 'https://yourdomain.com'); // no trailing slash
define('SITE_ENV', 'production');
define('PRETTY_URLS', true);  // set false only if mod_rewrite isn't available
define('BASE_PATH', '');      // set e.g. '/news' only if installed in a subfolder
```

### 5. Make `uploads/` writable

The admin panel writes images to `uploads/YYYY/MM/`. Right-click `uploads/`
in File Manager → **Permissions** → `755` (or `775` if `755` gets a
permission error — depends on your host's PHP execution mode).

### 6. Create your admin account

Visit `https://yourdomain.com/install.php`. It's a one-time setup screen —
fill in your name, email, and password. It refuses to run again once an
admin account exists (and just redirects to the login page), but deleting
`install.php` afterward is good practice.

### 7. Log in and take a look

`https://yourdomain.com/admin/login.php` → you'll land on the dashboard.
From there: **Ангилал** (Categories), **Нийтлэл** (Articles), **Тохиргоо**
(Settings) — the branding, logo, colors, and social links are all editable
from Settings.

### 8. Optional: exact-time scheduled publishing

The site already self-heals scheduled articles on every page view (checked
in `includes/bootstrap.php`), so this step is optional. For publishing at
the *exact* scheduled minute even with zero traffic, add a cPanel **Cron
Job**:

```
*/5 * * * * php /home/USERNAME/public_html/cron/publish-scheduled.php
```

## Security notes

- Delete `install.php` after creating your admin account (it self-locks
  regardless, but one less file is one less thing to think about).
- Always run over HTTPS in production (cPanel → **SSL/TLS Status** → AutoSSL).
- `uploads/.htaccess` blocks PHP execution inside the uploads folder — don't
  remove it.
- Change the admin password from **Тохиргоо → Миний бүртгэл** periodically.

## Troubleshooting

- **Pretty URLs 404 (`/news/...`, `/category/...`)** — your host may not have
  `mod_rewrite` enabled. Set `PRETTY_URLS` to `false` in `config.php`; the
  site automatically falls back to `news.php?slug=...` style links everywhere.
- **500 error on every page** — almost always wrong DB credentials in
  `config.php`, or a PHP version below 8.1. Check cPanel's **Errors** log
  or temporarily set `SITE_ENV` to `'development'` to see the real error
  (switch it back to `'production'` afterward).
- **Uploaded images fail** — check `uploads/` folder permissions (step 5).

## Project structure

```
config.php              Edit this after uploading (DB creds, site URL)
install.php              One-time admin account setup
sql/schema.sql            Database schema + demo seed data
includes/                Shared PHP: db connection, helpers, header/footer, article cards
index.php, news.php, category.php, search.php, sitemap.php, robots.php   Public pages
ajax/                     load-more + newsletter signup endpoints
admin/                    Admin panel (login, dashboard, articles, categories, settings)
assets/css, assets/js     Styles and vanilla-JS behavior (public + admin)
uploads/                  Admin-uploaded images (writable)
cron/                     Optional scheduled-publishing cron script
```

## Local development (optional)

If you want to run this on your own machine before uploading:

1. Install PHP 8.1+ and MySQL/MariaDB (e.g. via [Laragon](https://laragon.org/)
   or [XAMPP](https://www.apachefriends.org/) on Windows).
2. Create a database and import `sql/schema.sql`.
3. Edit `config.php` with your local DB credentials, set `SITE_URL` to
   `http://localhost:8000`, and set `PRETTY_URLS` to `false` (PHP's built-in
   server doesn't read `.htaccess`, so pretty URLs only work under Apache).
4. From the project folder: `php -S localhost:8000`
5. Visit `http://localhost:8000/install.php`.
