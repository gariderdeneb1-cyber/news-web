<?php
require_once __DIR__ . '/includes/admin-auth.php';

$errors = [];
$accountErrors = [];

$formValues = [
    'site_name' => $settings['site_name'] ?? '',
    'logo_url' => $settings['logo_url'] ?? '',
    'logo_dark_url' => $settings['logo_dark_url'] ?? '',
    'favicon_url' => $settings['favicon_url'] ?? '',
    'primary_color' => $settings['primary_color'] ?? '#1d4ed8',
    'contact_email' => $settings['contact_email'] ?? '',
    'contact_phone' => $settings['contact_phone'] ?? '',
    'contact_address' => $settings['contact_address'] ?? '',
    'facebook_url' => $settings['facebook_url'] ?? '',
    'twitter_url' => $settings['twitter_url'] ?? '',
    'youtube_url' => $settings['youtube_url'] ?? '',
    'instagram_url' => $settings['instagram_url'] ?? '',
    'footer_description' => $settings['footer_description'] ?? '',
    'seo_default_title' => $settings['seo_default_title'] ?? '',
    'seo_default_description' => $settings['seo_default_description'] ?? '',
    'analytics_id' => $settings['analytics_id'] ?? '',
    'articles_per_page' => $settings['articles_per_page'] ?? 12,
    'dark_mode_enabled' => (bool) ($settings['dark_mode_enabled'] ?? true),
    'maintenance_mode' => (bool) ($settings['maintenance_mode'] ?? false),
    'maintenance_message' => $settings['maintenance_message'] ?? '',
];

$accountValues = [
    'name' => $currentAdmin['name'] ?? '',
    'email' => $currentAdmin['email'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'save_settings') {
        foreach (array_keys($formValues) as $key) {
            if (in_array($key, ['dark_mode_enabled', 'maintenance_mode'], true)) {
                $formValues[$key] = !empty($_POST[$key]);
            } elseif ($key === 'articles_per_page') {
                $formValues[$key] = max(4, min(48, (int) ($_POST[$key] ?? 12)));
            } else {
                $formValues[$key] = trim((string) ($_POST[$key] ?? ''));
            }
        }

        if (mb_strlen($formValues['site_name']) < 2) {
            $errors[] = 'Сайтын нэр хамгийн багадаа 2 тэмдэгт байх ёстой.';
        }
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $formValues['primary_color'])) {
            $errors[] = 'Үндсэн өнгө #RRGGBB форматтай байх ёстой (жишээ: #1d4ed8).';
        }
        if ($formValues['contact_email'] !== '' && !filter_var($formValues['contact_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Холбоо барих имэйл хаяг буруу байна.';
        }

        if (!$errors) {
            $pdo->prepare(
                'UPDATE site_settings SET site_name=:site_name, logo_url=:logo_url, logo_dark_url=:logo_dark_url, favicon_url=:favicon_url, primary_color=:primary_color,
                 contact_email=:contact_email, contact_phone=:contact_phone, contact_address=:contact_address,
                 facebook_url=:facebook_url, twitter_url=:twitter_url, youtube_url=:youtube_url, instagram_url=:instagram_url,
                 footer_description=:footer_description, seo_default_title=:seo_default_title, seo_default_description=:seo_default_description,
                 analytics_id=:analytics_id, articles_per_page=:articles_per_page, dark_mode_enabled=:dark_mode_enabled,
                 maintenance_mode=:maintenance_mode, maintenance_message=:maintenance_message
                 WHERE id = 1'
            )->execute([
                ':site_name' => $formValues['site_name'],
                ':logo_url' => $formValues['logo_url'] ?: null,
                ':logo_dark_url' => $formValues['logo_dark_url'] ?: null,
                ':favicon_url' => $formValues['favicon_url'] ?: null,
                ':primary_color' => $formValues['primary_color'],
                ':contact_email' => $formValues['contact_email'] ?: null,
                ':contact_phone' => $formValues['contact_phone'] ?: null,
                ':contact_address' => $formValues['contact_address'] ?: null,
                ':facebook_url' => $formValues['facebook_url'] ?: null,
                ':twitter_url' => $formValues['twitter_url'] ?: null,
                ':youtube_url' => $formValues['youtube_url'] ?: null,
                ':instagram_url' => $formValues['instagram_url'] ?: null,
                ':footer_description' => $formValues['footer_description'] ?: null,
                ':seo_default_title' => $formValues['seo_default_title'] ?: null,
                ':seo_default_description' => $formValues['seo_default_description'] ?: null,
                ':analytics_id' => $formValues['analytics_id'] ?: null,
                ':articles_per_page' => $formValues['articles_per_page'],
                ':dark_mode_enabled' => $formValues['dark_mode_enabled'] ? 1 : 0,
                ':maintenance_mode' => $formValues['maintenance_mode'] ? 1 : 0,
                ':maintenance_message' => $formValues['maintenance_message'] ?: null,
            ]);
            flash_set('success', 'Тохиргоо хадгалагдлаа.');
            redirect(base_path('/admin/settings.php'));
        }
    }

    if ($action === 'change_account') {
        $accountValues['name'] = trim((string) ($_POST['name'] ?? ''));
        $accountValues['email'] = trim((string) ($_POST['email'] ?? ''));
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $newPasswordConfirm = (string) ($_POST['new_password_confirm'] ?? '');

        $adminFull = $pdo->prepare('SELECT * FROM admins WHERE id = :id');
        $adminFull->execute([':id' => $currentAdmin['id']]);
        $adminFull = $adminFull->fetch();

        if (mb_strlen($accountValues['name']) < 2) {
            $accountErrors[] = 'Нэрээ оруулна уу.';
        }
        if (!filter_var($accountValues['email'], FILTER_VALIDATE_EMAIL)) {
            $accountErrors[] = 'Имэйл хаяг буруу байна.';
        }
        if (!password_verify($currentPassword, $adminFull['password_hash'])) {
            $accountErrors[] = 'Одоогийн нууц үг буруу байна.';
        }
        if ($newPassword !== '' && mb_strlen($newPassword) < 8) {
            $accountErrors[] = 'Шинэ нууц үг хамгийн багадаа 8 тэмдэгт байх ёстой.';
        }
        if ($newPassword !== '' && $newPassword !== $newPasswordConfirm) {
            $accountErrors[] = 'Шинэ нууц үг хоорондоо таарахгүй байна.';
        }

        if (!$accountErrors) {
            if ($newPassword !== '') {
                $pdo->prepare('UPDATE admins SET name=:name, email=:email, password_hash=:hash WHERE id=:id')->execute([
                    ':name' => $accountValues['name'],
                    ':email' => mb_strtolower($accountValues['email']),
                    ':hash' => password_hash($newPassword, PASSWORD_BCRYPT),
                    ':id' => $adminFull['id'],
                ]);
            } else {
                $pdo->prepare('UPDATE admins SET name=:name, email=:email WHERE id=:id')->execute([
                    ':name' => $accountValues['name'],
                    ':email' => mb_strtolower($accountValues['email']),
                    ':id' => $adminFull['id'],
                ]);
            }
            flash_set('success', 'Бүртгэлийн мэдээлэл шинэчлэгдлээ.');
            redirect(base_path('/admin/settings.php'));
        }
    }
}

$adminPageTitle = 'Тохиргоо';
$adminActiveNav = 'settings';
require __DIR__ . '/includes/admin-layout-top.php';
?>

<?php if ($errors): ?>
  <div class="alert alert-error"><?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?></div>
<?php endif; ?>

<form method="post" action="<?= e(base_path('/admin/settings.php')) ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="save_settings">

  <div class="panel">
    <div class="panel-header"><div><h2>Брэндинг</h2></div></div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="site_name">Сайтын нэр</label>
        <input class="form-input" type="text" id="site_name" name="site_name" required value="<?= e($formValues['site_name']) ?>">
      </div>
      <div class="form-group">
        <label class="form-label" for="primary_color_text">Үндсэн өнгө</label>
        <div style="display:flex;gap:.5rem;align-items:center">
          <input type="color" data-color-pair="primary-color" value="<?= e($formValues['primary_color']) ?>" style="width:2.6rem;height:2.4rem;padding:.15rem;border:1px solid var(--border);border-radius:.5rem;background:var(--surface)">
          <input class="form-input" type="text" id="primary_color_text" name="primary_color" data-color-text="primary-color" value="<?= e($formValues['primary_color']) ?>" pattern="^#[0-9a-fA-F]{6}$">
        </div>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group" data-image-uploader data-upload-url="<?= e(base_path('/admin/upload.php')) ?>" style="background:#f7f8fa;padding:.75rem;border-radius:.6rem">
        <label class="form-label">Лого (цайвар горим)</label>
        <div class="image-preview" data-image-preview style="max-width:12rem;aspect-ratio:3/1">
          <?php if ($formValues['logo_url']): ?><img src="<?= e(resolve_media_url($formValues['logo_url'])) ?>" alt=""><?php else: ?>Логогүй<?php endif; ?>
        </div>
        <input type="file" accept="image/*" data-image-file class="form-input" style="padding:.4rem">
        <div class="form-hint" data-image-status></div>
        <input type="hidden" name="logo_url" data-image-url value="<?= e($formValues['logo_url']) ?>">
        <p class="form-hint">Цайвар дэвсгэр дээр харагдах лого.</p>
      </div>
      <div class="form-group" data-image-uploader data-upload-url="<?= e(base_path('/admin/upload.php')) ?>" style="background:#12151c;padding:.75rem;border-radius:.6rem">
        <label class="form-label" style="color:#eef1f6">Лого (бараан горим)</label>
        <div class="image-preview" data-image-preview style="max-width:12rem;aspect-ratio:3/1;background:#171b23">
          <?php if ($formValues['logo_dark_url']): ?><img src="<?= e(resolve_media_url($formValues['logo_dark_url'])) ?>" alt=""><?php else: ?>Логогүй<?php endif; ?>
        </div>
        <input type="file" accept="image/*" data-image-file class="form-input" style="padding:.4rem">
        <div class="form-hint" data-image-status></div>
        <input type="hidden" name="logo_dark_url" data-image-url value="<?= e($formValues['logo_dark_url']) ?>">
        <p class="form-hint" style="color:#9aa3b2">Бараан дэвсгэр дээр харагдах лого. Хоосон орхивол цайвар лого хоёуланд нь ашиглагдана.</p>
      </div>
    </div>
    <div class="form-group" data-image-uploader data-upload-url="<?= e(base_path('/admin/upload.php')) ?>" style="max-width:14rem">
      <label class="form-label">Favicon</label>
      <div class="image-preview" data-image-preview style="max-width:6rem;aspect-ratio:1/1">
        <?php if ($formValues['favicon_url']): ?><img src="<?= e(resolve_media_url($formValues['favicon_url'])) ?>" alt=""><?php else: ?>—<?php endif; ?>
      </div>
      <input type="file" accept="image/*" data-image-file class="form-input" style="padding:.4rem">
      <div class="form-hint" data-image-status></div>
      <input type="hidden" name="favicon_url" data-image-url value="<?= e($formValues['favicon_url']) ?>">
    </div>

    <div class="form-group">
      <label class="form-label" for="footer_description">Хөл хэсгийн танилцуулга</label>
      <textarea class="form-textarea" id="footer_description" name="footer_description" rows="2"><?= e($formValues['footer_description']) ?></textarea>
    </div>
  </div>

  <div class="panel">
    <div class="panel-header"><div><h2>Холбоо барих</h2></div></div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="contact_email">Имэйл</label>
        <input class="form-input" type="email" id="contact_email" name="contact_email" value="<?= e($formValues['contact_email']) ?>">
      </div>
      <div class="form-group">
        <label class="form-label" for="contact_phone">Утас</label>
        <input class="form-input" type="text" id="contact_phone" name="contact_phone" value="<?= e($formValues['contact_phone']) ?>">
      </div>
    </div>
    <div class="form-group">
      <label class="form-label" for="contact_address">Хаяг</label>
      <input class="form-input" type="text" id="contact_address" name="contact_address" value="<?= e($formValues['contact_address']) ?>">
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="facebook_url">Facebook холбоос</label>
        <input class="form-input" type="url" id="facebook_url" name="facebook_url" value="<?= e($formValues['facebook_url']) ?>">
      </div>
      <div class="form-group">
        <label class="form-label" for="twitter_url">X (Twitter) холбоос</label>
        <input class="form-input" type="url" id="twitter_url" name="twitter_url" value="<?= e($formValues['twitter_url']) ?>">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="youtube_url">YouTube холбоос</label>
        <input class="form-input" type="url" id="youtube_url" name="youtube_url" value="<?= e($formValues['youtube_url']) ?>">
      </div>
      <div class="form-group">
        <label class="form-label" for="instagram_url">Instagram холбоос</label>
        <input class="form-input" type="url" id="instagram_url" name="instagram_url" value="<?= e($formValues['instagram_url']) ?>">
      </div>
    </div>
  </div>

  <div class="panel">
    <div class="panel-header"><div><h2>SEO ба сайтын тохиргоо</h2></div></div>
    <div class="form-group">
      <label class="form-label" for="seo_default_title">Үндсэн SEO гарчиг</label>
      <input class="form-input" type="text" id="seo_default_title" name="seo_default_title" maxlength="200" value="<?= e($formValues['seo_default_title']) ?>">
    </div>
    <div class="form-group">
      <label class="form-label" for="seo_default_description">Үндсэн SEO тайлбар</label>
      <textarea class="form-textarea" id="seo_default_description" name="seo_default_description" maxlength="320" rows="2"><?= e($formValues['seo_default_description']) ?></textarea>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="analytics_id">Google Analytics ID</label>
        <input class="form-input" type="text" id="analytics_id" name="analytics_id" placeholder="G-XXXXXXXXXX" value="<?= e($formValues['analytics_id']) ?>">
      </div>
      <div class="form-group">
        <label class="form-label" for="articles_per_page">Хуудас тутамд харуулах нийтлэлийн тоо</label>
        <input class="form-input" type="number" id="articles_per_page" name="articles_per_page" min="4" max="48" value="<?= (int) $formValues['articles_per_page'] ?>">
      </div>
    </div>
    <label class="form-check" style="margin-bottom:.75rem"><input type="checkbox" name="dark_mode_enabled" <?= $formValues['dark_mode_enabled'] ? 'checked' : '' ?>> Бараан горим (dark mode) сэлгэгчийг харуулах</label>
  </div>

  <div class="panel">
    <div class="panel-header"><div><h2>Засвар үйлчилгээний горим</h2><p>Идэвхжүүлбэл сайт зөвхөн засвар үйлчилгээний мэдэгдлийг харуулна. Админ хэсэг хэвийн ажиллана.</p></div></div>
    <label class="form-check" style="margin-bottom:.75rem"><input type="checkbox" name="maintenance_mode" <?= $formValues['maintenance_mode'] ? 'checked' : '' ?>> Засвар үйлчилгээний горим идэвхтэй</label>
    <div class="form-group" style="margin-bottom:0">
      <label class="form-label" for="maintenance_message">Мэдэгдэл</label>
      <textarea class="form-textarea" id="maintenance_message" name="maintenance_message" rows="2"><?= e($formValues['maintenance_message']) ?></textarea>
    </div>
  </div>

  <button type="submit" class="btn btn-primary">Бүх тохиргоог хадгалах</button>
</form>

<hr class="section-divider">

<?php if ($accountErrors): ?>
  <div class="alert alert-error"><?php foreach ($accountErrors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?></div>
<?php endif; ?>

<div class="panel">
  <div class="panel-header"><div><h2>Миний бүртгэл</h2><p>Нэвтрэх имэйл болон нууц үгээ солих</p></div></div>
  <form method="post" action="<?= e(base_path('/admin/settings.php')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="change_account">
    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="acc_name">Нэр</label>
        <input class="form-input" type="text" id="acc_name" name="name" required value="<?= e($accountValues['name']) ?>">
      </div>
      <div class="form-group">
        <label class="form-label" for="acc_email">Имэйл</label>
        <input class="form-input" type="email" id="acc_email" name="email" required value="<?= e($accountValues['email']) ?>">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="new_password">Шинэ нууц үг</label>
        <input class="form-input" type="password" id="new_password" name="new_password" minlength="8" placeholder="Хоосон орхивол хэвээр үлдэнэ">
      </div>
      <div class="form-group">
        <label class="form-label" for="new_password_confirm">Шинэ нууц үг давтах</label>
        <input class="form-input" type="password" id="new_password_confirm" name="new_password_confirm" minlength="8">
      </div>
    </div>
    <div class="form-group">
      <label class="form-label" for="current_password">Одоогийн нууц үг (баталгаажуулахад шаардлагатай)</label>
      <input class="form-input" type="password" id="current_password" name="current_password" required>
    </div>
    <button type="submit" class="btn btn-outline">Бүртгэл шинэчлэх</button>
  </form>
</div>

<?php require __DIR__ . '/includes/admin-layout-bottom.php'; ?>
