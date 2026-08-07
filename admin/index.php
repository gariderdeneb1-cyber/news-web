<?php
require_once __DIR__ . '/includes/admin-auth.php';

$stats = [
    'total' => (int) $pdo->query('SELECT COUNT(*) FROM articles')->fetchColumn(),
    'published' => (int) $pdo->query("SELECT COUNT(*) FROM articles WHERE status = 'published'")->fetchColumn(),
    'draft' => (int) $pdo->query("SELECT COUNT(*) FROM articles WHERE status = 'draft'")->fetchColumn(),
    'scheduled' => (int) $pdo->query("SELECT COUNT(*) FROM articles WHERE status = 'scheduled'")->fetchColumn(),
    'categories' => (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn(),
    'views' => (int) $pdo->query('SELECT COALESCE(SUM(view_count), 0) FROM articles')->fetchColumn(),
];

$recent = $pdo->query(
    "SELECT a.id, a.title, a.slug, a.status, a.updated_at, a.view_count, c.name AS category_name
     FROM articles a LEFT JOIN categories c ON c.id = a.category_id
     ORDER BY a.updated_at DESC LIMIT 8"
)->fetchAll();

$adminPageTitle = 'Хянах самбар';
$adminActiveNav = 'dashboard';
require __DIR__ . '/includes/admin-layout-top.php';
?>

<div class="stat-grid">
  <div class="stat-card"><div class="stat-card__label">Нийт нийтлэл</div><div class="stat-card__value"><?= $stats['total'] ?></div></div>
  <div class="stat-card"><div class="stat-card__label">Нийтлэгдсэн</div><div class="stat-card__value"><?= $stats['published'] ?></div></div>
  <div class="stat-card"><div class="stat-card__label">Ноорог</div><div class="stat-card__value"><?= $stats['draft'] ?></div></div>
  <div class="stat-card"><div class="stat-card__label">Товлогдсон</div><div class="stat-card__value"><?= $stats['scheduled'] ?></div></div>
  <div class="stat-card"><div class="stat-card__label">Ангилал</div><div class="stat-card__value"><?= $stats['categories'] ?></div></div>
  <div class="stat-card"><div class="stat-card__label">Нийт үзэлт</div><div class="stat-card__value"><?= e(format_view_count($stats['views'])) ?></div></div>
</div>

<div class="panel">
  <div class="panel-header">
    <div>
      <h2>Сүүлд шинэчлэгдсэн</h2>
      <p>Хамгийн сүүлд засварласан нийтлэлүүд</p>
    </div>
    <a class="btn btn-primary btn-sm" href="<?= e(base_path('/admin/article-edit.php')) ?>">+ Шинэ нийтлэл</a>
  </div>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Гарчиг</th><th>Ангилал</th><th>Төлөв</th><th>Шинэчлэгдсэн</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($recent as $a): ?>
          <tr>
            <td><div class="title-cell"><strong><?= e($a['title']) ?></strong></div></td>
            <td><?= e($a['category_name'] ?? '—') ?></td>
            <td><span class="badge badge-status badge-status-<?= e($a['status']) ?>"><?= e(status_label($a['status'])) ?></span></td>
            <td><?= e(format_relative_mn($a['updated_at'])) ?></td>
            <td class="row-actions"><a class="btn btn-sm btn-outline" href="<?= e(base_path('/admin/article-edit.php?id=' . $a['id'])) ?>">Засах</a></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$recent): ?>
          <tr><td colspan="6" style="text-align:center;color:var(--foreground-muted);padding:2rem">Нийтлэл алга байна. Эхний нийтлэлээ нэмнэ үү.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/includes/admin-layout-bottom.php'; ?>
