# Deploying Khaan.mn to cPanel — full walkthrough

This covers every step from "I have a cPanel login" to "the site is live and I can log into the admin panel," including exactly what to click.

---

## Before you start

You need:
- A cPanel hosting account with a domain (or subdomain) already pointed at it
- The project files on your computer (this folder)
- Your cPanel username/password (from your host)

---

## Step 1 — Create the MySQL database

1. Log into cPanel.
2. Find **MySQL® Databases** (usually under a "Databases" section).
3. Under **Create New Database**, type a name — e.g. `news` — and click **Create Database**.
   - cPanel will actually name it `youraccountname_news`. Write that full name down.
4. Scroll down to **MySQL Users → Add New User**. Enter a username (e.g. `newsadmin`) and a strong password (use the **Password Generator** button, then copy it somewhere safe). Click **Create User**.
   - The real username will be `youraccountname_newsadmin`. Write it down too.
5. Scroll to **Add User To Database**. Select the user and the database you just made, click **Add**.
6. On the next screen ("Manage User Privileges"), check **ALL PRIVILEGES**, then click **Make Changes**.

You now have three things written down: the full database name, the full username, and the password. You'll need all three in Step 5.

---

## Step 2 — Import the database schema

1. Back in cPanel, open **phpMyAdmin**.
2. In the left sidebar, click the database you just created (`youraccountname_news`).
3. Click the **Import** tab along the top.
4. Click **Choose File**, and select `sql/schema.sql` from this project folder.
5. Scroll down and click **Go**.
6. You should see a green success message. If you click **Structure** or **Browse** on tables like `categories` or `articles` in the left sidebar, you'll see the demo data already sitting there (8 categories, ~15 demo articles, and the Khaan.mn branding/logo already set).

No admin login is created by this step — that's deliberate, and happens in Step 7.

---

## Step 3 — Upload the site files

**Option A — File Manager (easiest, no extra software):**

1. On your computer, select everything *inside* this project folder (`config.php`, `index.php`, `admin/`, `includes/`, etc. — not the folder itself) and compress it into a single `.zip`.
2. In cPanel, open **File Manager**.
3. Navigate to `public_html` (or, if this site is going on a subdomain, that subdomain's folder — check **Domains** in cPanel to see which folder it maps to).
4. Click **Upload** (top toolbar), drag your zip in, wait for it to finish, then go back to File Manager.
5. Right-click the uploaded `.zip` → **Extract** → confirm it extracts into the current folder.
6. Delete the `.zip` file afterward (right-click → Delete) to keep things tidy.
7. Confirm `config.php` is now directly inside `public_html/` — not inside a nested subfolder like `public_html/News-paper/`. If it did nest, select everything inside that inner folder, cut, and paste it up one level, then delete the empty folder.

**Option B — FTP/SFTP (if you prefer an FTP client like FileZilla):**

1. In cPanel, find **FTP Accounts** to get host/username/password (or use your main cPanel login over SFTP on port 22).
2. Connect, navigate to `public_html`, and drag every file/folder from this project into it.

Either way, when you're done, browsing to `public_html` in File Manager should show `config.php`, `index.php`, `admin/`, `includes/`, `assets/`, `uploads/`, `sql/`, `ajax/`, `cron/`, `.htaccess`, `install.php`, etc. all at the top level.

---

## Step 4 — Set the PHP version

1. In cPanel, find **MultiPHP Manager** (or **Select PHP Version**).
2. Find your domain in the list and set it to **PHP 8.1** or newer.
3. Click **Apply**.

---

## Step 5 — Edit `config.php`

1. In File Manager, right-click `config.php` → **Edit**.
2. Fill in the three database values from Step 1:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'youraccountname_news');
   define('DB_USER', 'youraccountname_newsadmin');
   define('DB_PASS', 'the-password-you-generated');
   ```
3. Set your real domain:
   ```php
   define('SITE_URL', 'https://yourdomain.com');   // no trailing slash
   ```
4. Make sure these two are set for production:
   ```php
   define('SITE_ENV', 'production');
   define('PRETTY_URLS', true);
   ```
5. Leave `BASE_PATH` as `''` unless you uploaded into a subfolder instead of the domain root (see the comment above it in the file).
6. Click **Save Changes** (top right of the editor).

---

## Step 6 — Make the uploads folder writable

1. In File Manager, right-click the `uploads` folder → **Permissions** (or **Change Permissions**).
2. Set it to **755**. If image uploads still fail once the site is running, try **775** instead — some hosts run PHP in a mode that needs the extra write bit.

---

## Step 7 — Create your admin account

1. Visit `https://yourdomain.com/install.php` in your browser.
2. Fill in the site name (already defaults to "Khaan.mn"), your name, your email, and a password (8+ characters).
3. Submit. You'll be redirected to the login page with a success message.
4. This page permanently refuses to run again once an admin exists — so it's safe to leave, but deleting `install.php` from File Manager afterward is good practice.

---

## Step 8 — Log in and verify

1. Go to `https://yourdomain.com/admin/login.php`, log in with what you just created.
2. You should land on **Хянах самбар** (Dashboard) showing stats matching the demo data (15 articles etc.).
3. Open the homepage (`https://yourdomain.com/`) in another tab — you should see the hero section, category sections, breaking news bar, and the Khaan.mn logo.
4. Click an article to confirm the article page, then click a category, then try the search box.

If pretty URLs (`/news/...`) 404, set `PRETTY_URLS` to `false` in `config.php` — see the Troubleshooting section in [README.md](README.md).

---

## Step 9 — Optional: exact-time scheduled publishing

The site already publishes due "scheduled" articles automatically on the next page view, so this is optional. For exact-minute timing even with no traffic:

1. cPanel → **Cron Jobs**.
2. Add a new cron job, common settings: **Every 5 minutes**.
3. Command:
   ```
   php /home/youraccountname/public_html/cron/publish-scheduled.php
   ```
   (Adjust the path to match where you uploaded the files — check File Manager's address bar for the exact absolute path.)

---

## Step 10 — Lock it down

- Delete `install.php` (Step 7).
- cPanel → **SSL/TLS Status** → enable AutoSSL so the site runs on HTTPS.
- Change your admin password occasionally from **Тохиргоо (Settings) → Миний бүртгэл (My account)** in the admin panel.

---

That's the whole path from zip file to a live, working site. [README.md](README.md) has a shorter version of these same steps plus a project structure overview and local-development instructions, in case you want to test changes on your own computer before re-uploading.
