<?php
/**
 * One-time setup wizard: creates the first (and, per this project's single-
 * admin design, only) admin account. Safe to leave on the server afterward
 * — it redirects to the login page once an admin exists — but deleting it
 * post-setup is good practice and the README says so.
 */
define('ADMIN_CONTEXT', true);
require_once __DIR__ . '/includes/bootstrap.php';

$adminCount = (int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();
if ($adminCount > 0) {
    redirect(base_path('/admin/login.php'));
}

$errors = [];
$name = '';
$email = '';
$siteName = $settings['site_name'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Хүсэлт хүчингүй боллоо. Хуудсаа шинэчилж дахин оролдоно уу.';
    } else {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $siteName = trim((string) ($_POST['site_name'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['password_confirm'] ?? '');

        if (mb_strlen($name) < 2) {
            $errors[] = 'Нэрээ оруулна уу.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Имэйл хаяг буруу байна.';
        }
        if (mb_strlen($password) < 8) {
            $errors[] = 'Нууц үг хамгийн багадаа 8 тэмдэгт байх ёстой.';
        }
        if ($password !== $confirm) {
            $errors[] = 'Нууц үг хоорондоо таарахгүй байна.';
        }

        if (!$errors) {
            $pdo->prepare('INSERT INTO admins (name, email, password_hash) VALUES (:name, :email, :hash)')->execute([
                ':name' => $name,
                ':email' => mb_strtolower($email),
                ':hash' => password_hash($password, PASSWORD_BCRYPT),
            ]);
            if ($siteName !== '') {
                $pdo->prepare('UPDATE site_settings SET site_name = :n WHERE id = 1')->execute([':n' => $siteName]);
            }
            redirect(base_path('/admin/login.php?installed=1'));
        }
    }
}
?>
<!doctype html>
<html lang="mn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Анхны тохиргоо — <?= e($settings['site_name'] ?? 'Мэдээний сайт') ?></title>
<link rel="stylesheet" href="<?= e(asset('/assets/css/style.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('/assets/css/admin.css')) ?>">
</head>
<body>
<div class="auth-page">
  <div class="auth-card" style="max-width:30rem">
    <div class="auth-card__brand">
      <span class="mark">Ад</span>
      <span>Анхны тохиргоо</span>
    </div>
    <h1>Тавтай морил</h1>
    <p class="auth-card__hint">Сайтаа удирдах анхны админ бүртгэлээ үүсгэнэ үү. Энэ хуудас зөвхөн нэг удаа ажиллана.</p>

    <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>

    <form method="post" action="<?= e(base_path('/install.php')) ?>">
      <?= csrf_field() ?>
      <div class="form-group">
        <label class="form-label" for="site_name">Сайтын нэр</label>
        <input class="form-input" type="text" id="site_name" name="site_name" value="<?= e($siteName) ?>" placeholder="Монгол Мэдээ">
      </div>
      <div class="form-group">
        <label class="form-label" for="name">Таны нэр</label>
        <input class="form-input" type="text" id="name" name="name" required value="<?= e($name) ?>">
      </div>
      <div class="form-group">
        <label class="form-label" for="email">Имэйл</label>
        <input class="form-input" type="email" id="email" name="email" required value="<?= e($email) ?>">
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="password">Нууц үг</label>
          <input class="form-input" type="password" id="password" name="password" required minlength="8">
        </div>
        <div class="form-group">
          <label class="form-label" for="password_confirm">Нууц үг давтах</label>
          <input class="form-input" type="password" id="password_confirm" name="password_confirm" required minlength="8">
        </div>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Бүртгэл үүсгэх</button>
    </form>
  </div>
</div>
</body>
</html>
