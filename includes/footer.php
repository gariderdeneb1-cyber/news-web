</main>

<footer class="site-footer">
  <div class="container footer-grid">
    <div class="footer-col">
      <h4><?= e($siteName) ?></h4>
      <?php if (!empty($settings['footer_description'])): ?>
        <p><?= nl2br(e($settings['footer_description'])) ?></p>
      <?php endif; ?>
      <?php
        $socials = [
            'facebook_url'  => 'f',
            'twitter_url'   => 'X',
            'youtube_url'   => 'YT',
            'instagram_url' => 'IG',
        ];
        $hasSocial = false;
        foreach ($socials as $key => $label) {
            if (!empty($settings[$key])) { $hasSocial = true; break; }
        }
      ?>
      <?php if ($hasSocial): ?>
      <div class="social-links">
        <?php foreach ($socials as $key => $label): ?>
          <?php if (!empty($settings[$key])): ?>
            <a href="<?= e($settings[$key]) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= e($label) ?>"><?= e($label) ?></a>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <?php if (!empty($activeCategories)): ?>
    <div class="footer-col">
      <h4>Ангилал</h4>
      <ul>
        <?php foreach (array_slice($activeCategories, 0, 8) as $cat): ?>
          <li><a href="<?= e(url_category($cat['slug'])) ?>"><?= e($cat['name']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

    <?php if (!empty($settings['contact_email']) || !empty($settings['contact_phone']) || !empty($settings['contact_address'])): ?>
    <div class="footer-col">
      <h4>Холбоо барих</h4>
      <ul>
        <?php if (!empty($settings['contact_email'])): ?><li><a href="mailto:<?= e($settings['contact_email']) ?>"><?= e($settings['contact_email']) ?></a></li><?php endif; ?>
        <?php if (!empty($settings['contact_phone'])): ?><li><?= e($settings['contact_phone']) ?></li><?php endif; ?>
        <?php if (!empty($settings['contact_address'])): ?><li><?= e($settings['contact_address']) ?></li><?php endif; ?>
      </ul>
    </div>
    <?php endif; ?>

    <div class="footer-col">
      <h4>Холбоос</h4>
      <ul>
        <li><a href="<?= e(url_home()) ?>">Нүүр</a></li>
        <li><a href="<?= e(url_search()) ?>">Хайх</a></li>
      </ul>
    </div>
  </div>

  <div class="footer-bottom">
    &copy; <?= date('Y') ?> <?= e($siteName) ?>. Бүх эрх хуулиар хамгаалагдсан.
  </div>
</footer>

<script src="<?= e(asset('/assets/js/main.js')) ?>" defer></script>
</body>
</html>
