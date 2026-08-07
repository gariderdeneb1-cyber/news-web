<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/article-card.php';

function search_articles(PDO $pdo, string $q, ?int $categoryId, int $limit, int $offset): array
{
    $catClause = $categoryId ? ' AND a.category_id = :cid' : '';

    $probeSql = "SELECT COUNT(*) FROM articles a
                 WHERE a.status = 'published' AND MATCH(a.title, a.excerpt, a.content_text) AGAINST (:q IN NATURAL LANGUAGE MODE)"
        . $catClause;
    $probe = $pdo->prepare($probeSql);
    $probe->bindValue(':q', $q);
    if ($categoryId) {
        $probe->bindValue(':cid', $categoryId, PDO::PARAM_INT);
    }
    $probe->execute();
    $useFulltext = (int) $probe->fetchColumn() > 0;

    if ($useFulltext) {
        $countSql = "SELECT COUNT(*) " . ARTICLE_FROM . "
                     WHERE a.status = 'published' AND MATCH(a.title, a.excerpt, a.content_text) AGAINST (:q IN NATURAL LANGUAGE MODE)" . $catClause;
        $listSql = "SELECT " . ARTICLE_FIELDS . ",
                           MATCH(a.title, a.excerpt, a.content_text) AGAINST (:q IN NATURAL LANGUAGE MODE) AS relevance
                    " . ARTICLE_FROM . "
                    WHERE a.status = 'published' AND MATCH(a.title, a.excerpt, a.content_text) AGAINST (:q IN NATURAL LANGUAGE MODE)" . $catClause . "
                    ORDER BY relevance DESC LIMIT :lim OFFSET :off";
    } else {
        $countSql = "SELECT COUNT(*) " . ARTICLE_FROM . "
                     WHERE a.status = 'published' AND (a.title LIKE :like OR a.excerpt LIKE :like OR a.content_text LIKE :like)" . $catClause;
        $listSql = "SELECT " . ARTICLE_FIELDS . " " . ARTICLE_FROM . "
                    WHERE a.status = 'published' AND (a.title LIKE :like OR a.excerpt LIKE :like OR a.content_text LIKE :like)" . $catClause . "
                    ORDER BY a.published_at DESC LIMIT :lim OFFSET :off";
    }

    $countStmt = $pdo->prepare($countSql);
    $listStmt = $pdo->prepare($listSql);
    foreach ([$countStmt, $listStmt] as $stmt) {
        if ($useFulltext) {
            $stmt->bindValue(':q', $q);
        } else {
            $stmt->bindValue(':like', '%' . $q . '%');
        }
        if ($categoryId) {
            $stmt->bindValue(':cid', $categoryId, PDO::PARAM_INT);
        }
    }
    $listStmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $listStmt->bindValue(':off', $offset, PDO::PARAM_INT);

    $countStmt->execute();
    $total = (int) $countStmt->fetchColumn();
    $listStmt->execute();

    return [$listStmt->fetchAll(), $total];
}

$q = trim((string) ($_GET['q'] ?? ''));
$categorySlug = trim((string) ($_GET['category'] ?? ''));
$perPage = max(4, min(48, (int) ($settings['articles_per_page'] ?? 12)));
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$categories = $pdo->query('SELECT id, name, slug FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC')->fetchAll();
$categoryId = null;
foreach ($categories as $c) {
    if ($c['slug'] === $categorySlug) {
        $categoryId = (int) $c['id'];
        break;
    }
}

$articles = [];
$total = 0;
$totalPages = 1;
if ($q !== '') {
    [$articles, $total] = search_articles($pdo, $q, $categoryId, $perPage, $offset);
    $totalPages = max(1, (int) ceil($total / $perPage));
}

$pageTitle = $q !== '' ? 'Хайлт: ' . $q : 'Хайх';
$canonicalPath = '/search.php' . ($q !== '' ? '?q=' . urlencode($q) : '');
require __DIR__ . '/includes/header.php';
?>

<div class="container">
  <div class="page-header">
    <h1 class="page-header__title">Мэдээ хайх</h1>
  </div>

  <form class="search-form" action="<?= e(base_path('/search.php')) ?>" method="get">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Түлхүүр үг оруулна уу..." required>
    <?php if ($categoryId): ?><input type="hidden" name="category" value="<?= e($categorySlug) ?>"><?php endif; ?>
    <button type="submit" class="btn btn-primary">Хайх</button>
  </form>

  <?php if ($categories): ?>
    <div class="filter-bar">
      <a class="filter-chip<?= $categoryId === null ? ' is-active' : '' ?>" href="<?= e(url_search($q)) ?>">Бүгд</a>
      <?php foreach ($categories as $c): ?>
        <?php $qs = http_build_query(array_filter(['q' => $q, 'category' => $c['slug']])); ?>
        <a class="filter-chip<?= $categoryId === (int) $c['id'] ? ' is-active' : '' ?>" href="<?= e(base_path('/search.php?' . $qs)) ?>"><?= e($c['name']) ?></a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($q === ''): ?>
    <div class="empty-state">
      <h3>Юу хайхыг хүсэж байгаагаа бичнэ үү</h3>
      <p>Гарчиг, агуулгаас түлхүүр үгээр хайх боломжтой.</p>
    </div>
  <?php elseif ($articles): ?>
    <p style="color:var(--foreground-muted);font-size:.85rem;margin-bottom:1rem"><?= (int) $total ?> үр дүн олдлоо</p>
    <div class="stack">
      <?php foreach ($articles as $a): ?><?= render_card_horizontal($a) ?><?php endforeach; ?>
    </div>
    <?= render_pagination($page, $totalPages, paginate_url(base_path('/search.php?' . http_build_query(array_filter(['q' => $q, 'category' => $categorySlug]))))) ?>
  <?php else: ?>
    <div class="empty-state">
      <h3>"<?= e($q) ?>" гэсэн үр дүн олдсонгүй</h3>
      <p>Өөр түлхүүр үгээр хайж үзнэ үү.</p>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
