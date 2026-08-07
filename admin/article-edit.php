<?php
require_once __DIR__ . '/includes/admin-auth.php';

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$article = null;
if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM articles WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $article = $stmt->fetch();
    if (!$article) {
        flash_set('error', 'Нийтлэл олдсонгүй.');
        redirect(base_path('/admin/articles.php'));
    }
}

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY sort_order ASC, name ASC')->fetchAll();

$values = [
    'title' => $article['title'] ?? '',
    'slug' => $article['slug'] ?? '',
    'excerpt' => $article['excerpt'] ?? '',
    'content' => $article['content'] ?? '',
    'featured_image_url' => $article['featured_image_url'] ?? '',
    'featured_image_caption' => $article['featured_image_caption'] ?? '',
    'video_url' => $article['video_url'] ?? '',
    'category_id' => $article['category_id'] ?? '',
    'author_name' => $article['author_name'] ?? ($settings['site_name'] ?? ''),
    'status' => $article['status'] ?? 'draft',
    'scheduled_at' => to_datetime_local($article['scheduled_at'] ?? null),
    'is_featured' => (bool) ($article['is_featured'] ?? false),
    'is_breaking' => (bool) ($article['is_breaking'] ?? false),
    'is_hero' => (bool) ($article['is_hero'] ?? false),
    'seo_title' => $article['seo_title'] ?? '',
    'seo_description' => $article['seo_description'] ?? '',
    'source_name' => $article['source_name'] ?? '',
    'source_url' => $article['source_url'] ?? '',
    'tags' => '',
];
if ($id) {
    $tagStmt = $pdo->prepare('SELECT t.name FROM tags t JOIN article_tags at2 ON at2.tag_id = t.id WHERE at2.article_id = :id ORDER BY t.name');
    $tagStmt->execute([':id' => $id]);
    $values['tags'] = implode(',', array_column($tagStmt->fetchAll(), 'name'));
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();

    $values['title'] = trim((string) ($_POST['title'] ?? ''));
    $values['slug'] = trim((string) ($_POST['slug'] ?? ''));
    $values['excerpt'] = trim((string) ($_POST['excerpt'] ?? ''));
    $values['content'] = (string) ($_POST['content'] ?? '');
    $values['featured_image_url'] = trim((string) ($_POST['featured_image_url'] ?? ''));
    $values['featured_image_caption'] = trim((string) ($_POST['featured_image_caption'] ?? ''));
    $values['video_url'] = trim((string) ($_POST['video_url'] ?? ''));
    $values['category_id'] = trim((string) ($_POST['category_id'] ?? ''));
    $values['author_name'] = trim((string) ($_POST['author_name'] ?? ''));
    $values['status'] = trim((string) ($_POST['status'] ?? 'draft'));
    $values['scheduled_at'] = trim((string) ($_POST['scheduled_at'] ?? ''));
    $values['is_featured'] = !empty($_POST['is_featured']);
    $values['is_breaking'] = !empty($_POST['is_breaking']);
    $values['is_hero'] = !empty($_POST['is_hero']);
    $values['seo_title'] = trim((string) ($_POST['seo_title'] ?? ''));
    $values['seo_description'] = trim((string) ($_POST['seo_description'] ?? ''));
    $values['source_name'] = trim((string) ($_POST['source_name'] ?? ''));
    $values['source_url'] = trim((string) ($_POST['source_url'] ?? ''));
    $values['tags'] = trim((string) ($_POST['tags'] ?? ''));

    $validStatuses = ['draft', 'scheduled', 'published', 'archived'];

    if (mb_strlen($values['title']) < 3) {
        $errors[] = 'Гарчиг хамгийн багадаа 3 тэмдэгт байх ёстой.';
    }
    if (trim(strip_html_text($values['content'])) === '') {
        $errors[] = 'Агуулга хоосон байж болохгүй.';
    }
    if (!in_array($values['status'], $validStatuses, true)) {
        $errors[] = 'Төлөв буруу байна.';
    }
    if ($values['status'] === 'scheduled' && $values['scheduled_at'] === '') {
        $errors[] = 'Товлох огноог сонгоно уу.';
    }

    if (!$errors) {
        $sanitizedContent = sanitize_article_html($values['content']);
        $contentText = strip_html_text($sanitizedContent);
        $excerpt = $values['excerpt'] !== '' ? $values['excerpt'] : make_excerpt($contentText, 200);
        $readingTime = calc_reading_time($contentText);

        $baseSlug = $values['slug'] !== '' ? slugify($values['slug']) : slugify($values['title']);
        $finalSlug = unique_slug($pdo, 'articles', $baseSlug, $id ?: null);

        $categoryId = $values['category_id'] !== '' ? (int) $values['category_id'] : null;
        $scheduledAt = $values['status'] === 'scheduled' ? from_datetime_local($values['scheduled_at']) : null;

        $publishedAt = $article['published_at'] ?? null;
        if ($values['status'] === 'published' && !$publishedAt) {
            $publishedAt = date('Y-m-d H:i:s');
        }

        $params = [
            ':title' => $values['title'],
            ':slug' => $finalSlug,
            ':excerpt' => $excerpt,
            ':content' => $sanitizedContent,
            ':content_text' => $contentText,
            ':status' => $values['status'],
            ':fiu' => $values['featured_image_url'] ?: null,
            ':fic' => $values['featured_image_caption'] ?: null,
            ':video_url' => $values['video_url'] ?: null,
            ':category_id' => $categoryId,
            ':author_name' => $values['author_name'] ?: null,
            ':is_featured' => $values['is_featured'] ? 1 : 0,
            ':is_breaking' => $values['is_breaking'] ? 1 : 0,
            ':is_hero' => $values['is_hero'] ? 1 : 0,
            ':reading_time' => $readingTime,
            ':seo_title' => $values['seo_title'] ?: null,
            ':seo_description' => $values['seo_description'] ?: null,
            ':source_name' => $values['source_name'] ?: null,
            ':source_url' => $values['source_url'] ?: null,
            ':published_at' => $publishedAt,
            ':scheduled_at' => $scheduledAt,
        ];

        if ($id) {
            $params[':id'] = $id;
            $stmt = $pdo->prepare(
                'UPDATE articles SET title=:title, slug=:slug, excerpt=:excerpt, content=:content, content_text=:content_text,
                 status=:status, featured_image_url=:fiu, featured_image_caption=:fic, video_url=:video_url,
                 category_id=:category_id, author_name=:author_name, is_featured=:is_featured, is_breaking=:is_breaking,
                 is_hero=:is_hero, reading_time_min=:reading_time, seo_title=:seo_title, seo_description=:seo_description,
                 source_name=:source_name, source_url=:source_url, published_at=:published_at, scheduled_at=:scheduled_at
                 WHERE id = :id'
            );
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO articles (title, slug, excerpt, content, content_text, status, featured_image_url, featured_image_caption,
                 video_url, category_id, author_name, is_featured, is_breaking, is_hero, reading_time_min, seo_title, seo_description,
                 source_name, source_url, published_at, scheduled_at)
                 VALUES (:title, :slug, :excerpt, :content, :content_text, :status, :fiu, :fic, :video_url, :category_id, :author_name,
                 :is_featured, :is_breaking, :is_hero, :reading_time, :seo_title, :seo_description, :source_name, :source_url, :published_at, :scheduled_at)'
            );
        }
        $stmt->execute($params);
        $articleId = $id ?: (int) $pdo->lastInsertId();

        // Re-sync tags from the comma-separated list: upsert each by slug, then replace the join rows.
        $pdo->prepare('DELETE FROM article_tags WHERE article_id = :id')->execute([':id' => $articleId]);
        $tagNames = array_values(array_unique(array_filter(array_map('trim', explode(',', $values['tags'])))));
        foreach ($tagNames as $tagName) {
            $tagSlug = slugify($tagName);
            if ($tagSlug === '') {
                continue;
            }
            $tagLookup = $pdo->prepare('SELECT id FROM tags WHERE slug = :slug');
            $tagLookup->execute([':slug' => $tagSlug]);
            $tagId = $tagLookup->fetchColumn();
            if (!$tagId) {
                $pdo->prepare('INSERT INTO tags (name, slug) VALUES (:name, :slug)')->execute([':name' => $tagName, ':slug' => $tagSlug]);
                $tagId = (int) $pdo->lastInsertId();
            }
            $pdo->prepare('INSERT IGNORE INTO article_tags (article_id, tag_id) VALUES (:aid, :tid)')->execute([':aid' => $articleId, ':tid' => $tagId]);
        }

        flash_set('success', $id ? 'Нийтлэл шинэчлэгдлээ.' : 'Нийтлэл нэмэгдлээ.');
        redirect(base_path('/admin/article-edit.php?id=' . $articleId));
    }
}

$adminPageTitle = $id ? 'Нийтлэл засах' : 'Шинэ нийтлэл';
$adminActiveNav = 'articles';
$adminExtraHead = '<link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">'
    . '<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>';
require __DIR__ . '/includes/admin-layout-top.php';
?>

<?php if ($errors): ?>
  <div class="alert alert-error">
    <?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
  </div>
<?php endif; ?>

<form method="post" action="<?= e(base_path('/admin/article-edit.php' . ($id ? '?id=' . $id : ''))) ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= (int) $id ?>">

  <div class="article-edit-grid" style="display:grid;grid-template-columns:1fr;gap:1.25rem">
    <div>
      <div class="panel">
        <div class="form-group">
          <label class="form-label" for="title">Гарчиг</label>
          <input class="form-input" type="text" id="title" name="title" data-title-input required value="<?= e($values['title']) ?>">
        </div>
        <div class="form-group">
          <label class="form-label" for="slug">Слаг (URL)</label>
          <input class="form-input" type="text" id="slug" name="slug" data-slug-input value="<?= e($values['slug']) ?>">
          <p class="form-hint"><?= e(rtrim(SITE_URL, '/') . url_news($values['slug'] ?: 'таны-слаг')) ?></p>
        </div>
        <div class="form-group">
          <label class="form-label" for="excerpt">Товч агуулга</label>
          <textarea class="form-textarea" id="excerpt" name="excerpt" maxlength="400" rows="3"><?= e($values['excerpt']) ?></textarea>
          <p class="form-hint">Хоосон орхивол агуулгаас автоматаар үүсгэнэ.</p>
        </div>
        <div class="form-group" style="margin-bottom:0">
          <label class="form-label">Агуулга</label>
          <div class="editor-toolbar-mount">
            <div data-quill-mount data-upload-url="<?= e(base_path('/admin/upload.php')) ?>"></div>
          </div>
          <textarea name="content" data-quill-hidden style="display:none"><?= e($values['content']) ?></textarea>
        </div>
      </div>

      <div class="panel">
        <div class="panel-header"><div><h2>SEO ба эх сурвалж</h2></div></div>
        <div class="form-group">
          <label class="form-label" for="seo_title">SEO гарчиг</label>
          <input class="form-input" type="text" id="seo_title" name="seo_title" maxlength="160" value="<?= e($values['seo_title']) ?>">
        </div>
        <div class="form-group">
          <label class="form-label" for="seo_description">SEO тайлбар</label>
          <textarea class="form-textarea" id="seo_description" name="seo_description" maxlength="300" rows="2"><?= e($values['seo_description']) ?></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="source_name">Эх сурвалжийн нэр</label>
            <input class="form-input" type="text" id="source_name" name="source_name" value="<?= e($values['source_name']) ?>">
          </div>
          <div class="form-group">
            <label class="form-label" for="source_url">Эх сурвалжийн холбоос</label>
            <input class="form-input" type="url" id="source_url" name="source_url" value="<?= e($values['source_url']) ?>">
          </div>
        </div>
      </div>
    </div>

    <div>
      <div class="panel">
        <div class="form-group">
          <label class="form-label" for="status">Төлөв</label>
          <select class="form-select" id="status" name="status" data-status-select>
            <option value="draft" <?= $values['status'] === 'draft' ? 'selected' : '' ?>>Ноорог</option>
            <option value="scheduled" <?= $values['status'] === 'scheduled' ? 'selected' : '' ?>>Товлох</option>
            <option value="published" <?= $values['status'] === 'published' ? 'selected' : '' ?>>Нийтлэх</option>
            <option value="archived" <?= $values['status'] === 'archived' ? 'selected' : '' ?>>Архивлах</option>
          </select>
        </div>
        <div class="form-group" data-schedule-group>
          <label class="form-label" for="scheduled_at">Товлох огноо, цаг</label>
          <input class="form-input" type="datetime-local" id="scheduled_at" name="scheduled_at" value="<?= e($values['scheduled_at']) ?>">
        </div>
        <div class="form-group">
          <label class="form-label" for="category_id">Ангилал</label>
          <select class="form-select" id="category_id" name="category_id">
            <option value="">— Сонгоогүй —</option>
            <?php foreach ($categories as $c): ?>
              <option value="<?= (int) $c['id'] ?>" <?= (string) $values['category_id'] === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" for="author_name">Зохиогч</label>
          <input class="form-input" type="text" id="author_name" name="author_name" value="<?= e($values['author_name']) ?>">
        </div>
        <div class="form-group" style="margin-bottom:.75rem">
          <label class="form-label">Шошго</label>
          <div class="tag-input-wrap" data-tag-input>
            <input type="text" data-tag-text placeholder="Шошго бичээд Enter дарна уу">
          </div>
          <input type="hidden" name="tags" data-tags-hidden value="<?= e($values['tags']) ?>">
        </div>
        <div class="checkbox-grid">
          <label class="form-check"><input type="checkbox" name="is_featured" <?= $values['is_featured'] ? 'checked' : '' ?>> Онцлох</label>
          <label class="form-check"><input type="checkbox" name="is_breaking" <?= $values['is_breaking'] ? 'checked' : '' ?>> Шуурхай</label>
          <label class="form-check"><input type="checkbox" name="is_hero" <?= $values['is_hero'] ? 'checked' : '' ?>> Нүүр (Hero)</label>
        </div>
      </div>

      <div class="panel" data-image-uploader data-upload-url="<?= e(base_path('/admin/upload.php')) ?>">
        <label class="form-label">Тэргүүлэх зураг</label>
        <div class="image-preview" data-image-preview>
          <?php if ($values['featured_image_url']): ?>
            <img src="<?= e($values['featured_image_url']) ?>" alt="">
          <?php else: ?>
            Зураг сонгоогүй байна
          <?php endif; ?>
        </div>
        <input type="file" accept="image/*" data-image-file class="form-input" style="padding:.4rem">
        <div class="form-hint" data-image-status></div>
        <input type="hidden" name="featured_image_url" data-image-url value="<?= e($values['featured_image_url']) ?>">
        <div class="form-group" style="margin-top:.75rem">
          <label class="form-label" for="featured_image_caption">Зургийн тайлбар</label>
          <input class="form-input" type="text" id="featured_image_caption" name="featured_image_caption" value="<?= e($values['featured_image_caption']) ?>">
        </div>
        <div class="form-group" style="margin-bottom:0">
          <label class="form-label" for="video_url">YouTube видео холбоос</label>
          <input class="form-input" type="url" id="video_url" name="video_url" placeholder="https://www.youtube.com/watch?v=..." value="<?= e($values['video_url']) ?>">
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-block">Хадгалах</button>
    </div>
  </div>
</form>

<?php require __DIR__ . '/includes/admin-layout-bottom.php'; ?>
