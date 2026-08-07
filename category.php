<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/article-card.php';

$slug = $_GET['slug'] ?? '';
$catStmt = $pdo->prepare('SELECT * FROM categories WHERE slug = :slug AND is_active = 1 LIMIT 1');
$catStmt->execute([':slug' => $slug]);
$category = $catStmt->fetch();

if (!$category) {
    http_response_code(404);
    $pageTitle = 'Ангилал олдсонгүй';
    require __DIR__ . '/includes/header.php';
    ?>
    <div class="container section center-text">
      <h1 style="font-size:1.5rem;margin-bottom:.75rem">Ангилал олдсонгүй</h1>
      <a class="btn btn-primary" href="<?= e(url_home()) ?>">Нүүр хуудас руу буцах</a>
    </div>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

$perPage = max(4, min(48, (int) ($settings['articles_per_page'] ?? 12)));
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM articles WHERE status = 'published' AND category_id = :cid");
$countStmt->execute([':cid' => $category['id']]);
$total = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));

$listStmt = $pdo->prepare(
    "SELECT " . ARTICLE_LIST_SELECT . " WHERE a.status = 'published' AND a.category_id = :cid ORDER BY a.published_at DESC LIMIT :lim OFFSET :off"
);
$listStmt->bindValue(':cid', $category['id'], PDO::PARAM_INT);
$listStmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
$listStmt->bindValue(':off', $offset, PDO::PARAM_INT);
$listStmt->execute();
$articles = $listStmt->fetchAll();

$pageTitle = $category['name'];
$pageDescription = $category['description'] ?: ($settings['seo_default_description'] ?? '');
$canonicalPath = url_category($category['slug']) . ($page > 1 ? '?page=' . $page : '');
require __DIR__ . '/includes/header.php';
?>

<div class="container">
  <div class="page-header">
    <h1 class="page-header__title"><span class="page-header__badge" style="background:<?= e($category['color']) ?>"></span><?= e($category['name']) ?></h1>
    <?php if (!empty($category['description'])): ?><p class="page-header__desc"><?= e($category['description']) ?></p><?php endif; ?>
  </div>

  <?php if ($articles): ?>
    <div class="grid cols-4">
      <?php foreach ($articles as $a): ?><div><?= render_card_grid($a) ?></div><?php endforeach; ?>
    </div>
    <?= render_pagination($page, $totalPages, paginate_url(url_category($category['slug']))) ?>
  <?php else: ?>
    <div class="empty-state">
      <h3>Энэ ангилалд одоогоор мэдээ алга байна</h3>
      <p>Удахгүй шинэ мэдээ нэмэгдэнэ.</p>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
