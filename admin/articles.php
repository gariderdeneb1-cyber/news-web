<?php
require_once __DIR__ . '/includes/admin-auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    csrf_require();
    $delId = (int) ($_POST['id'] ?? 0);
    if ($delId) {
        $pdo->prepare('DELETE FROM articles WHERE id = :id')->execute([':id' => $delId]);
        flash_set('success', 'Нийтлэл устгагдлаа.');
    }
    $returnQs = (string) ($_POST['return_qs'] ?? '');
    redirect(base_path('/admin/articles.php' . ($returnQs !== '' ? '?' . $returnQs : '')));
}

$q = trim((string) ($_GET['q'] ?? ''));
$statusFilter = trim((string) ($_GET['status'] ?? ''));
$categoryFilter = (int) ($_GET['category'] ?? 0);
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$where = ['1=1'];
$params = [];
if ($q !== '') {
    $where[] = 'a.title LIKE :q';
    $params[':q'] = '%' . $q . '%';
}
if ($statusFilter !== '') {
    $where[] = 'a.status = :status';
    $params[':status'] = $statusFilter;
}
if ($categoryFilter) {
    $where[] = 'a.category_id = :cid';
    $params[':cid'] = $categoryFilter;
}
$whereSql = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM articles a WHERE {$whereSql}");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));

$listStmt = $pdo->prepare(
    "SELECT a.id, a.title, a.slug, a.status, a.featured_image_url, a.updated_at,
            c.name AS category_name
     FROM articles a LEFT JOIN categories c ON c.id = a.category_id
     WHERE {$whereSql}
     ORDER BY a.updated_at DESC LIMIT :lim OFFSET :off"
);
foreach ($params as $k => $v) {
    $listStmt->bindValue($k, $v);
}
$listStmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
$listStmt->bindValue(':off', $offset, PDO::PARAM_INT);
$listStmt->execute();
$articles = $listStmt->fetchAll();

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY sort_order ASC, name ASC')->fetchAll();

$adminPageTitle = 'Нийтлэл';
$adminActiveNav = 'articles';
require __DIR__ . '/includes/admin-layout-top.php';
?>

<div class="panel-header" style="margin-bottom:1.25rem">
  <div><h2 style="font-size:1.1rem;font-weight:800">Бүх нийтлэл (<?= (int) $total ?>)</h2></div>
  <a class="btn btn-primary" href="<?= e(base_path('/admin/article-edit.php')) ?>">+ Шинэ нийтлэл</a>
</div>

<form class="panel" method="get" style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end">
  <div class="form-group" style="flex:1;min-width:12rem;margin-bottom:0">
    <label class="form-label">Хайх</label>
    <input class="form-input" type="text" name="q" value="<?= e($q) ?>" placeholder="Гарчгаар хайх...">
  </div>
  <div class="form-group" style="margin-bottom:0">
    <label class="form-label">Төлөв</label>
    <select class="form-select" name="status">
      <option value="">Бүгд</option>
      <option value="draft" <?= $statusFilter === 'draft' ? 'selected' : '' ?>>Ноорог</option>
      <option value="scheduled" <?= $statusFilter === 'scheduled' ? 'selected' : '' ?>>Товлогдсон</option>
      <option value="published" <?= $statusFilter === 'published' ? 'selected' : '' ?>>Нийтлэгдсэн</option>
      <option value="archived" <?= $statusFilter === 'archived' ? 'selected' : '' ?>>Архивласан</option>
    </select>
  </div>
  <div class="form-group" style="margin-bottom:0">
    <label class="form-label">Ангилал</label>
    <select class="form-select" name="category">
      <option value="">Бүгд</option>
      <?php foreach ($categories as $c): ?>
        <option value="<?= (int) $c['id'] ?>" <?= $categoryFilter === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <button class="btn btn-outline" type="submit">Шүүх</button>
  <?php if ($q !== '' || $statusFilter !== '' || $categoryFilter): ?>
    <a class="btn" href="<?= e(base_path('/admin/articles.php')) ?>">Цэвэрлэх</a>
  <?php endif; ?>
</form>

<div class="table-wrap">
  <table class="data-table">
    <thead><tr><th>Нийтлэл</th><th>Ангилал</th><th>Төлөв</th><th>Шинэчлэгдсэн</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($articles as $a): ?>
        <tr>
          <td>
            <div class="title-cell">
              <?php if (!empty($a['featured_image_url'])): ?>
                <img class="thumb" src="<?= e($a['featured_image_url']) ?>" alt="">
              <?php else: ?>
                <span class="thumb" style="display:flex;align-items:center;justify-content:center;font-size:.7rem;color:var(--foreground-muted)">—</span>
              <?php endif; ?>
              <strong><?= e($a['title']) ?></strong>
            </div>
          </td>
          <td><?= e($a['category_name'] ?? '—') ?></td>
          <td><span class="badge badge-status badge-status-<?= e($a['status']) ?>"><?= e(status_label($a['status'])) ?></span></td>
          <td><?= e(format_relative_mn($a['updated_at'])) ?></td>
          <td class="row-actions">
            <a class="btn btn-sm btn-outline" href="<?= e(base_path('/admin/article-edit.php?id=' . $a['id'])) ?>">Засах</a>
            <?php if ($a['status'] === 'published'): ?>
              <a class="btn btn-sm btn-outline" href="<?= e(url_news($a['slug'])) ?>" target="_blank" rel="noopener noreferrer">Үзэх</a>
            <?php endif; ?>
            <form method="post" data-confirm="«<?= e($a['title']) ?>» нийтлэлийг устгах уу? Энэ үйлдлийг буцаах боломжгүй." style="display:inline">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
              <input type="hidden" name="return_qs" value="<?= e(http_build_query($_GET)) ?>">
              <button type="submit" class="btn btn-sm btn-danger">Устгах</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$articles): ?>
        <tr><td colspan="6" style="text-align:center;color:var(--foreground-muted);padding:2rem">Илэрц олдсонгүй</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?= render_pagination(
    $page,
    $totalPages,
    paginate_url(base_path('/admin/articles.php?' . http_build_query(array_filter([
        'q' => $q !== '' ? $q : null,
        'status' => $statusFilter !== '' ? $statusFilter : null,
        'category' => $categoryFilter ?: null,
    ]))))
) ?>

<?php require __DIR__ . '/includes/admin-layout-bottom.php'; ?>
