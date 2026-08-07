<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/article-card.php';

$perPage = max(4, min(48, (int) ($settings['articles_per_page'] ?? 12)));

$heroArticles = $pdo->query(
    "SELECT " . ARTICLE_LIST_SELECT . "
     WHERE a.status = 'published'
     ORDER BY a.is_hero DESC, a.is_featured DESC, a.published_at DESC
     LIMIT 5"
)->fetchAll();
$heroMain = $heroArticles[0] ?? null;
$heroSubs = array_slice($heroArticles, 1, 4);

$categories = $pdo->query('SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC')->fetchAll();
$categorySections = [];
$catStmt = $pdo->prepare(
    "SELECT " . ARTICLE_LIST_SELECT . "
     WHERE a.status = 'published' AND a.category_id = :cid
     ORDER BY a.published_at DESC LIMIT 4"
);
foreach ($categories as $cat) {
    $catStmt->execute([':cid' => $cat['id']]);
    $rows = $catStmt->fetchAll();
    if ($rows) {
        $categorySections[] = ['category' => $cat, 'articles' => $rows];
    }
}

$latest = $pdo->prepare(
    "SELECT " . ARTICLE_LIST_SELECT . "
     WHERE a.status = 'published'
     ORDER BY a.published_at DESC LIMIT :lim"
);
$latest->bindValue(':lim', $perPage, PDO::PARAM_INT);
$latest->execute();
$latest = $latest->fetchAll();

$mostReadAll = $pdo->query(
    "SELECT " . ARTICLE_LIST_SELECT . " WHERE a.status = 'published' ORDER BY a.view_count DESC, a.published_at DESC LIMIT 6"
)->fetchAll();

$mostReadWeek = $pdo->query(
    "SELECT " . ARTICLE_LIST_SELECT . "
     JOIN article_views v ON v.article_id = a.id
     WHERE a.status = 'published' AND v.viewed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     GROUP BY a.id, c.id
     ORDER BY COUNT(v.id) DESC LIMIT 6"
)->fetchAll();

$mostReadToday = $pdo->query(
    "SELECT " . ARTICLE_LIST_SELECT . "
     JOIN article_views v ON v.article_id = a.id
     WHERE a.status = 'published' AND v.viewed_at >= CURDATE()
     GROUP BY a.id, c.id
     ORDER BY COUNT(v.id) DESC LIMIT 6"
)->fetchAll();

$videoArticles = $pdo->query(
    "SELECT " . ARTICLE_LIST_SELECT . "
     WHERE a.status = 'published' AND a.video_url IS NOT NULL AND a.video_url <> ''
     ORDER BY a.published_at DESC LIMIT 3"
)->fetchAll();

$pageDescription = $settings['seo_default_description'] ?? '';
require __DIR__ . '/includes/header.php';
?>

<section class="section" style="padding-top:1.75rem">
  <div class="container">
    <?php if ($heroMain): ?>
      <div class="hero-grid">
        <div><?= render_card_hero_main($heroMain) ?></div>
        <?php if ($heroSubs): ?>
          <div class="hero-sub-list">
            <?php foreach ($heroSubs as $a): ?><?= render_card_hero_sub($a) ?><?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="empty-state">
        <h3>Одоогоор нийтлэгдсэн мэдээ алга байна</h3>
        <p>Админ хэсгээс эхний мэдээгээ нэмнэ үү.</p>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php foreach ($categorySections as $section): $cat = $section['category']; ?>
  <section class="section">
    <div class="container">
      <div class="section__header">
        <h2 class="section-title"><span class="dot" style="background:<?= e($cat['color']) ?>"></span><?= e($cat['name']) ?></h2>
        <a class="section__link" href="<?= e(url_category($cat['slug'])) ?>">Бүгдийг үзэх →</a>
      </div>
      <div class="grid cols-4">
        <?php foreach ($section['articles'] as $a): ?><div><?= render_card_grid($a) ?></div><?php endforeach; ?>
      </div>
    </div>
  </section>
<?php endforeach; ?>

<?php if ($latest): ?>
<section class="section" id="latest">
  <div class="container">
    <div class="section__header">
      <h2 class="section-title"><span class="dot"></span>Сүүлийн мэдээ</h2>
    </div>
    <div class="grid cols-4" data-latest-container>
      <?php foreach ($latest as $a): ?><div><?= render_card_grid($a) ?></div><?php endforeach; ?>
    </div>
    <?php if (count($latest) >= $perPage): ?>
      <div class="load-more-wrap">
        <button type="button" class="btn btn-outline" data-load-more="<?= e(base_path('/ajax/load-more.php?page=2')) ?>" data-target="[data-latest-container]" data-page="2" data-per-page="<?= (int) $perPage ?>">Цааш үзэх</button>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($mostReadAll): ?>
<section class="section">
  <div class="container">
    <div class="section__header">
      <h2 class="section-title"><span class="dot"></span>Их уншсан</h2>
      <div class="tabs">
        <button type="button" class="tab-btn is-active" data-tab-btn="today">Өнөөдөр</button>
        <button type="button" class="tab-btn" data-tab-btn="week">7 хоног</button>
        <button type="button" class="tab-btn" data-tab-btn="all">Бүх цаг үе</button>
      </div>
    </div>

    <div class="tab-panel is-active" data-tab-panel="today">
      <div class="stack">
        <?php if ($mostReadToday): foreach ($mostReadToday as $i => $a): ?><?= render_card_compact($a, $i + 1) ?><?php endforeach; else: ?>
          <p class="empty-state">Өнөөдөр үзсэн мэдээ одоогоор алга байна.</p>
        <?php endif; ?>
      </div>
    </div>
    <div class="tab-panel" data-tab-panel="week">
      <div class="stack">
        <?php if ($mostReadWeek): foreach ($mostReadWeek as $i => $a): ?><?= render_card_compact($a, $i + 1) ?><?php endforeach; else: ?>
          <p class="empty-state">Энэ долоо хоногт үзсэн мэдээ алга байна.</p>
        <?php endif; ?>
      </div>
    </div>
    <div class="tab-panel" data-tab-panel="all">
      <div class="stack">
        <?php foreach ($mostReadAll as $i => $a): ?><?= render_card_compact($a, $i + 1) ?><?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($videoArticles): ?>
<section class="section">
  <div class="container">
    <div class="section__header">
      <h2 class="section-title"><span class="dot"></span>Видео мэдээ</h2>
    </div>
    <div class="grid cols-3">
      <?php foreach ($videoArticles as $a): $embed = youtube_embed_url((string) $a['video_url']); ?>
        <div>
          <div class="video-card__frame">
            <?php if ($embed): ?>
              <iframe src="<?= e($embed) ?>" title="<?= e($a['title']) ?>" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>
            <?php else: ?>
              <a href="<?= e(url_news($a['slug'])) ?>"><?= article_image_block($a, 'ratio-16-9', false) ?></a>
            <?php endif; ?>
          </div>
          <a href="<?= e(url_news($a['slug'])) ?>" class="video-card__title line-clamp-2"><?= e($a['title']) ?></a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section">
  <div class="container">
    <div class="newsletter-box">
      <h3>Мэдээллийн товхимол авах</h3>
      <p>Өдөр бүрийн онцлох мэдээ, нийтлэлүүдийг цахим шуудангаараа хамгийн түрүүнд аваарай.</p>
      <?php if (($_GET['subscribed'] ?? '') === '1'): ?>
        <p style="margin-top:1rem;font-weight:700">Бүртгэл амжилттай! Баярлалаа.</p>
      <?php else: ?>
        <?php if (($_GET['subscribed'] ?? '') === '0'): ?>
          <p style="margin-top:1rem;font-weight:700">Имэйл хаяг буруу байна. Дахин оролдоно уу.</p>
        <?php endif; ?>
        <form class="newsletter-form" action="<?= e(base_path('/ajax/newsletter.php')) ?>" method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="redirect_to" value="<?= e(url_home()) ?>">
          <input type="email" name="email" placeholder="Имэйл хаяг" required>
          <button type="submit" class="btn" style="background:#fff;color:var(--primary)">Бүртгүүлэх</button>
        </form>
        <small>Би имэйлээр мэдээ хүлээн авахыг зөвшөөрч, нууцлалын бодлоготой танилцлаа.</small>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
