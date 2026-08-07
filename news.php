<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/article-card.php';

$slug = $_GET['slug'] ?? '';

$stmt = $pdo->prepare(
    "SELECT a.*, c.name AS category_name, c.slug AS category_slug, c.color AS category_color
     FROM articles a LEFT JOIN categories c ON c.id = a.category_id
     WHERE a.slug = :slug LIMIT 1"
);
$stmt->execute([':slug' => $slug]);
$article = $stmt->fetch();

if (!$article || $article['status'] !== 'published') {
    http_response_code(404);
    $pageTitle = 'Олдсонгүй';
    require __DIR__ . '/includes/header.php';
    ?>
    <div class="container section center-text">
      <h1 style="font-size:1.5rem;margin-bottom:.75rem">Уучлаарай, мэдээ олдсонгүй</h1>
      <p style="color:var(--foreground-muted);margin-bottom:1.5rem">Хайсан нийтлэл устсан эсвэл хаяг буруу байна.</p>
      <a class="btn btn-primary" href="<?= e(url_home()) ?>">Нүүр хуудас руу буцах</a>
    </div>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

// Session-debounced view tracking (counts once per visitor session per article).
if (!isset($_SESSION['viewed_articles'])) {
    $_SESSION['viewed_articles'] = [];
}
if (!in_array((int) $article['id'], $_SESSION['viewed_articles'], true)) {
    $pdo->prepare('INSERT INTO article_views (article_id) VALUES (:id)')->execute([':id' => $article['id']]);
    $pdo->prepare('UPDATE articles SET view_count = view_count + 1 WHERE id = :id')->execute([':id' => $article['id']]);
    $article['view_count']++;
    $_SESSION['viewed_articles'][] = (int) $article['id'];
    if (count($_SESSION['viewed_articles']) > 500) {
        array_shift($_SESSION['viewed_articles']);
    }
}

$tagStmt = $pdo->prepare(
    'SELECT t.name, t.slug FROM tags t JOIN article_tags at ON at.tag_id = t.id WHERE at.article_id = :id ORDER BY t.name'
);
$tagStmt->execute([':id' => $article['id']]);
$tags = $tagStmt->fetchAll();

if ($article['category_id']) {
    $relatedStmt = $pdo->prepare(
        "SELECT " . ARTICLE_LIST_SELECT . " WHERE a.status = 'published' AND a.category_id = :cid AND a.id != :id ORDER BY a.published_at DESC LIMIT 4"
    );
    $relatedStmt->execute([':cid' => $article['category_id'], ':id' => $article['id']]);
} else {
    $relatedStmt = $pdo->prepare(
        "SELECT " . ARTICLE_LIST_SELECT . " WHERE a.status = 'published' AND a.id != :id ORDER BY a.published_at DESC LIMIT 4"
    );
    $relatedStmt->execute([':id' => $article['id']]);
}
$related = $relatedStmt->fetchAll();

function fetch_adjacent_article(PDO $pdo, array $article, string $direction): ?array
{
    $cmp = $direction === 'prev' ? '<' : '>';
    $order = $direction === 'prev' ? 'DESC' : 'ASC';
    $sql = "SELECT slug, title FROM articles WHERE status = 'published' AND published_at {$cmp} :pub";
    $params = [':pub' => $article['published_at']];
    if ($article['category_id']) {
        $sql .= ' AND category_id = :cid';
        $params[':cid'] = $article['category_id'];
    }
    $sql .= " ORDER BY published_at {$order} LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch() ?: null;
}
$prevArticle = fetch_adjacent_article($pdo, $article, 'prev');
$nextArticle = fetch_adjacent_article($pdo, $article, 'next');

$pageTitle = $article['seo_title'] ?: $article['title'];
$pageDescription = $article['seo_description'] ?: $article['excerpt'];
$ogImage = $article['featured_image_url'] ?? null;
$ogType = 'article';
$canonicalPath = url_news($article['slug']);

$jsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'NewsArticle',
    'headline' => $article['title'],
    'description' => $article['excerpt'] ?? '',
    'datePublished' => $article['published_at'],
    'dateModified' => $article['updated_at'],
    'author' => ['@type' => 'Person', 'name' => $article['author_name'] ?: ($settings['site_name'] ?? '')],
    'publisher' => [
        '@type' => 'Organization',
        'name' => $settings['site_name'] ?? '',
        'logo' => ['@type' => 'ImageObject', 'url' => $settings['logo_url'] ?? ''],
    ],
    'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => rtrim(SITE_URL, '/') . $canonicalPath],
];
if (!empty($article['featured_image_url'])) {
    $jsonLd['image'] = [$article['featured_image_url']];
}
$extraJsonLd = json_ld_script($jsonLd);

require __DIR__ . '/includes/header.php';
?>

<div class="reading-progress" data-reading-progress></div>

<article class="section" style="padding-top:1.5rem">
  <div class="container">
    <nav class="breadcrumb">
      <a href="<?= e(url_home()) ?>">Нүүр</a>
      <span>/</span>
      <?php if ($article['category_name']): ?>
        <a href="<?= e(url_category($article['category_slug'])) ?>"><?= e($article['category_name']) ?></a>
        <span>/</span>
      <?php endif; ?>
      <span style="color:var(--foreground)"><?= e($article['title']) ?></span>
    </nav>

    <header class="article-header">
      <?php if ($article['category_name']): ?>
        <a href="<?= e(url_category($article['category_slug'])) ?>" class="badge badge-category" style="background:<?= e($article['category_color']) ?>;margin-bottom:.85rem"><?= e($article['category_name']) ?></a>
      <?php endif; ?>
      <h1 class="article-header__title"><?= e($article['title']) ?></h1>
      <div class="article-meta">
        <span><?= e($article['author_name'] ?: ($settings['site_name'] ?? '')) ?></span>
        <span class="sep">·</span>
        <span><?= e(format_mn_datetime($article['published_at'])) ?></span>
        <span class="sep">·</span>
        <span><?= (int) $article['reading_time_min'] ?> мин унших</span>
        <span class="sep">·</span>
        <span><?= e(format_view_count((int) $article['view_count'])) ?> үзсэн</span>
      </div>
    </header>

    <?php if (!empty($article['featured_image_url'])): ?>
      <figure class="article-featured-image">
        <img src="<?= e($article['featured_image_url']) ?>" alt="<?= e($article['title']) ?>">
        <?php if (!empty($article['featured_image_caption'])): ?>
          <figcaption><?= e($article['featured_image_caption']) ?></figcaption>
        <?php endif; ?>
      </figure>
    <?php endif; ?>

    <div class="article-body-wrap">
      <div class="article-body" data-article-body><?= $article['content'] ?></div>

      <?php if (!empty($article['source_name'])): ?>
        <p style="font-size:.82rem;color:var(--foreground-muted);margin-top:1.25rem">
          Эх сурвалж:
          <?php if (!empty($article['source_url'])): ?>
            <a href="<?= e($article['source_url']) ?>" target="_blank" rel="noopener noreferrer nofollow"><?= e($article['source_name']) ?></a>
          <?php else: ?>
            <?= e($article['source_name']) ?>
          <?php endif; ?>
        </p>
      <?php endif; ?>

      <?php if ($tags): ?>
        <div class="tags-row">
          <?php foreach ($tags as $t): ?>
            <a class="tag-chip" href="<?= e(url_search($t['name'])) ?>">#<?= e($t['name']) ?></a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="share-buttons">
        <span>Хуваалцах:</span>
        <a class="share-btn" href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($canonicalUrl) ?>" target="_blank" rel="noopener noreferrer" aria-label="Facebook-т хуваалцах">f</a>
        <a class="share-btn" href="https://twitter.com/intent/tweet?url=<?= urlencode($canonicalUrl) ?>&amp;text=<?= urlencode($article['title']) ?>" target="_blank" rel="noopener noreferrer" aria-label="X-д хуваалцах">X</a>
        <button type="button" class="share-btn" data-share-copy aria-label="Холбоос хуулах">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
        </button>
      </div>

      <?php if ($prevArticle || $nextArticle): ?>
        <div class="prev-next-nav">
          <?php if ($prevArticle): ?>
            <a href="<?= e(url_news($prevArticle['slug'])) ?>"><span class="label">← Өмнөх</span><div class="title line-clamp-2"><?= e($prevArticle['title']) ?></div></a>
          <?php else: ?>
            <div></div>
          <?php endif; ?>
          <?php if ($nextArticle): ?>
            <a href="<?= e(url_news($nextArticle['slug'])) ?>" class="next-col"><span class="label">Дараах →</span><div class="title line-clamp-2"><?= e($nextArticle['title']) ?></div></a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>

    <?php if ($related): ?>
      <section class="related-section">
        <div class="section__header"><h2 class="section-title"><span class="dot"></span>Холбоотой мэдээ</h2></div>
        <div class="grid cols-4">
          <?php foreach ($related as $a): ?><div><?= render_card_grid($a) ?></div><?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>
  </div>
</article>

<?php require __DIR__ . '/includes/footer.php'; ?>
