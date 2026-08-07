<?php
require_once __DIR__ . '/includes/admin-auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM articles WHERE category_id = :id');
        $countStmt->execute([':id' => $id]);
        $articleCount = (int) $countStmt->fetchColumn();
        if ($articleCount > 0) {
            flash_set('error', "Энэ ангилалд {$articleCount} нийтлэл холбоотой тул устгах боломжгүй. Эхлээд тэдгээр нийтлэлийн ангиллыг өөрчилнө үү.");
        } else {
            $pdo->prepare('DELETE FROM categories WHERE id = :id')->execute([':id' => $id]);
            flash_set('success', 'Ангилал устгагдлаа.');
        }
        redirect(base_path('/admin/categories.php'));
    }

    if ($action === 'move') {
        $id = (int) ($_POST['id'] ?? 0);
        $dir = (string) ($_POST['dir'] ?? '');
        $all = $pdo->query('SELECT id, sort_order FROM categories ORDER BY sort_order ASC, id ASC')->fetchAll();
        $index = null;
        foreach ($all as $i => $c) {
            if ((int) $c['id'] === $id) {
                $index = $i;
                break;
            }
        }
        if ($index !== null) {
            $swapWith = $dir === 'up' ? $index - 1 : $index + 1;
            if (isset($all[$swapWith])) {
                $a = $all[$index];
                $b = $all[$swapWith];
                $pdo->prepare('UPDATE categories SET sort_order = :o WHERE id = :id')->execute([':o' => $b['sort_order'], ':id' => $a['id']]);
                $pdo->prepare('UPDATE categories SET sort_order = :o WHERE id = :id')->execute([':o' => $a['sort_order'], ':id' => $b['id']]);
            }
        }
        redirect(base_path('/admin/categories.php'));
    }

    if ($action === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $slug = trim((string) ($_POST['slug'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $color = trim((string) ($_POST['color'] ?? '#1d4ed8'));
        $isActive = !empty($_POST['is_active']);

        $errors = [];
        if (mb_strlen($name) < 2) {
            $errors[] = 'Ангиллын нэр хамгийн багадаа 2 тэмдэгт байх ёстой.';
        }
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            $errors[] = 'Өнгө #RRGGBB форматтай байх ёстой (жишээ: #1d4ed8).';
        }

        if ($errors) {
            foreach ($errors as $err) {
                flash_set('error', $err);
            }
            redirect(base_path('/admin/categories.php' . ($id ? '?edit=' . $id : '')));
        }

        $baseSlug = $slug !== '' ? slugify($slug) : slugify($name);
        $finalSlug = unique_slug($pdo, 'categories', $baseSlug, $id ?: null);

        if ($id) {
            $pdo->prepare('UPDATE categories SET name=:name, slug=:slug, description=:desc, color=:color, is_active=:active WHERE id=:id')
                ->execute([':name' => $name, ':slug' => $finalSlug, ':desc' => $description ?: null, ':color' => $color, ':active' => $isActive ? 1 : 0, ':id' => $id]);
            flash_set('success', 'Ангилал шинэчлэгдлээ.');
        } else {
            $nextOrder = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM categories')->fetchColumn();
            $pdo->prepare('INSERT INTO categories (name, slug, description, color, is_active, sort_order) VALUES (:name,:slug,:desc,:color,:active,:sort)')
                ->execute([':name' => $name, ':slug' => $finalSlug, ':desc' => $description ?: null, ':color' => $color, ':active' => $isActive ? 1 : 0, ':sort' => $nextOrder]);
            flash_set('success', 'Ангилал нэмэгдлээ.');
        }
        redirect(base_path('/admin/categories.php'));
    }
}

$editId = (int) ($_GET['edit'] ?? 0);
$editCategory = null;
if ($editId) {
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = :id');
    $stmt->execute([':id' => $editId]);
    $editCategory = $stmt->fetch();
}
$formValues = [
    'name' => $editCategory['name'] ?? '',
    'slug' => $editCategory['slug'] ?? '',
    'description' => $editCategory['description'] ?? '',
    'color' => $editCategory['color'] ?? '#1d4ed8',
    'is_active' => (bool) ($editCategory['is_active'] ?? true),
];

$categories = $pdo->query(
    "SELECT c.*, (SELECT COUNT(*) FROM articles a WHERE a.category_id = c.id) AS article_count
     FROM categories c ORDER BY c.sort_order ASC, c.name ASC"
)->fetchAll();

$adminPageTitle = 'Ангилал';
$adminActiveNav = 'categories';
require __DIR__ . '/includes/admin-layout-top.php';
?>

<div class="panel">
  <div class="panel-header">
    <div><h2><?= $editCategory ? 'Ангилал засах' : 'Шинэ ангилал нэмэх' ?></h2></div>
    <?php if ($editCategory): ?><a class="btn btn-sm" href="<?= e(base_path('/admin/categories.php')) ?>">Цуцлах</a><?php endif; ?>
  </div>
  <form method="post" action="<?= e(base_path('/admin/categories.php')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= (int) $editId ?>">
    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="cat_name">Нэр</label>
        <input class="form-input" type="text" id="cat_name" name="name" data-title-input required value="<?= e($formValues['name']) ?>">
      </div>
      <div class="form-group">
        <label class="form-label" for="cat_slug">Слаг (URL)</label>
        <input class="form-input" type="text" id="cat_slug" name="slug" data-slug-input value="<?= e($formValues['slug']) ?>">
      </div>
    </div>
    <div class="form-group">
      <label class="form-label" for="cat_desc">Тайлбар</label>
      <textarea class="form-textarea" id="cat_desc" name="description" rows="2"><?= e($formValues['description']) ?></textarea>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="cat_color_text">Өнгө</label>
        <div style="display:flex;gap:.5rem;align-items:center">
          <input type="color" data-color-pair="cat-color" value="<?= e($formValues['color']) ?>" style="width:2.6rem;height:2.4rem;padding:.15rem;border:1px solid var(--border);border-radius:.5rem;background:var(--surface)">
          <input class="form-input" type="text" id="cat_color_text" name="color" data-color-text="cat-color" value="<?= e($formValues['color']) ?>" pattern="^#[0-9a-fA-F]{6}$">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Идэвхтэй эсэх</label>
        <label class="form-check" style="margin-top:.5rem"><input type="checkbox" name="is_active" <?= $formValues['is_active'] ? 'checked' : '' ?>> Сайт дээр харагдана</label>
      </div>
    </div>
    <button type="submit" class="btn btn-primary"><?= $editCategory ? 'Хадгалах' : 'Нэмэх' ?></button>
  </form>
</div>

<div class="table-wrap">
  <table class="data-table">
    <thead><tr><th>Дараалал</th><th>Нэр</th><th>Слаг</th><th>Нийтлэл</th><th>Идэвхтэй</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($categories as $i => $c): ?>
        <tr>
          <td>
            <div class="reorder-btns">
              <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="move"><input type="hidden" name="id" value="<?= (int) $c['id'] ?>"><input type="hidden" name="dir" value="up"><button type="submit" <?= $i === 0 ? 'disabled' : '' ?> aria-label="Дээш">▲</button></form>
              <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="move"><input type="hidden" name="id" value="<?= (int) $c['id'] ?>"><input type="hidden" name="dir" value="down"><button type="submit" <?= $i === count($categories) - 1 ? 'disabled' : '' ?> aria-label="Доош">▼</button></form>
            </div>
          </td>
          <td><span class="color-swatch" style="background:<?= e($c['color']) ?>"></span> <strong><?= e($c['name']) ?></strong></td>
          <td style="color:var(--foreground-muted)"><?= e($c['slug']) ?></td>
          <td><?= (int) $c['article_count'] ?></td>
          <td><?= $c['is_active'] ? 'Тийм' : 'Үгүй' ?></td>
          <td class="row-actions">
            <a class="btn btn-sm btn-outline" href="<?= e(base_path('/admin/categories.php?edit=' . $c['id'])) ?>">Засах</a>
            <form method="post" data-confirm="«<?= e($c['name']) ?>» ангиллыг устгах уу?" style="display:inline">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
              <button type="submit" class="btn btn-sm btn-danger" <?= $c['article_count'] > 0 ? 'title="Нийтлэлтэй тул устгах боломжгүй"' : '' ?>>Устгах</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$categories): ?>
        <tr><td colspan="6" style="text-align:center;color:var(--foreground-muted);padding:2rem">Ангилал алга байна</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/includes/admin-layout-bottom.php'; ?>
