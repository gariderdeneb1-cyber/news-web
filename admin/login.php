<?php
define('ADMIN_CONTEXT', true);
require_once __DIR__ . '/../includes/bootstrap.php';

$adminCount = (int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();
if ($adminCount === 0) {
    redirect(base_path('/install.php'));
}
if (!empty($_SESSION['admin_id'])) {
    redirect(base_path('/admin/'));
}

$maxAttempts = 5;
$lockoutMinutes = 15;
$error = '';

$nextUrl = (string) ($_GET['next'] ?? base_path('/admin/'));
if (!str_starts_with($nextUrl, '/') || str_starts_with($nextUrl, '//')) {
    $nextUrl = base_path('/admin/');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Хүсэлт хүчингүй боллоо. Хуудсаа шинэчилж дахин оролдоно уу.';
    } else {
        $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');

        $stmt = $pdo->prepare('SELECT * FROM admins WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $admin = $stmt->fetch();

        // Constant-shape dummy hash so a nonexistent account still costs a
        // real bcrypt comparison (mitigates user-enumeration via timing).
        $dummyHash = '$2y$12$0abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        if (!$admin) {
            password_verify($password, $dummyHash);
            $error = 'Имэйл эсвэл нууц үг буруу байна.';
        } elseif ($admin['locked_until'] && strtotime($admin['locked_until']) > time()) {
            $mins = max(1, (int) ceil((strtotime($admin['locked_until']) - time()) / 60));
            $error = "Хэт олон удаа буруу нууц үг оруулсан тул түр хаагдсан байна. {$mins} минутын дараа дахин оролдоно уу.";
        } elseif (!password_verify($password, $admin['password_hash'])) {
            $attempts = (int) $admin['login_attempts'] + 1;
            $locked = $attempts >= $maxAttempts;
            $pdo->prepare('UPDATE admins SET login_attempts = :a, locked_until = :l WHERE id = :id')->execute([
                ':a' => $locked ? 0 : $attempts,
                ':l' => $locked ? date('Y-m-d H:i:s', time() + $lockoutMinutes * 60) : null,
                ':id' => $admin['id'],
            ]);
            $error = $locked
                ? "Хэт олон удаа буруу оролдсон тул {$lockoutMinutes} минутын турш хаагдлаа."
                : 'Имэйл эсвэл нууц үг буруу байна.';
        } else {
            $pdo->prepare('UPDATE admins SET login_attempts = 0, locked_until = NULL, last_login_at = NOW() WHERE id = :id')
                ->execute([':id' => $admin['id']]);
            session_regenerate_id(true);
            $_SESSION['admin_id'] = $admin['id'];
            redirect($nextUrl);
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
<title>Нэвтрэх — <?= e($settings['site_name'] ?? 'Админ') ?></title>
<link rel="stylesheet" href="<?= e(asset('/assets/css/style.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('/assets/css/admin.css')) ?>">
</head>
<body>
<div class="auth-page">
  <div class="auth-card">
    <div class="auth-card__brand">
      <span class="mark">Ад</span>
      <span><?= e($settings['site_name'] ?? 'Мэдээний сайт') ?></span>
    </div>
    <h1>Админ нэвтрэх</h1>
    <p class="auth-card__hint">Удирдлагын самбарт нэвтрэхийн тулд имэйл, нууц үгээ оруулна уу.</p>

    <?php if (($_GET['installed'] ?? '') === '1'): ?>
      <div class="alert alert-success">Админ бүртгэл амжилттай үүслээ. Одоо нэвтэрнэ үү.</div>
    <?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

    <form method="post" action="<?= e(base_path('/admin/login.php?next=' . urlencode($nextUrl))) ?>">
      <?= csrf_field() ?>
      <div class="form-group">
        <label class="form-label" for="email">Имэйл</label>
        <input class="form-input" type="email" id="email" name="email" required autofocus value="<?= e($_POST['email'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label" for="password">Нууц үг</label>
        <input class="form-input" type="password" id="password" name="password" required>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Нэвтрэх</button>
    </form>
  </div>
</div>
</body>
</html>
