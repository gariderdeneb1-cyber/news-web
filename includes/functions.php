<?php
/**
 * Shared helper functions: slugs, Mongolian date formatting, text utilities,
 * CSRF, flash messages, pagination, URL builders, and the article HTML
 * sanitizer.
 */

// ── Output / URLs ──────────────────────────────────────────────────────

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function base_path(string $path = ''): string
{
    return BASE_PATH . $path;
}

function asset(string $path): string
{
    return base_path($path);
}

function url_home(): string
{
    return base_path('/');
}

/** Passes full URLs through unchanged; prefixes BASE_PATH onto root-relative ones. */
function resolve_media_url(?string $url): string
{
    if (!$url) {
        return '';
    }
    return preg_match('#^https?://#i', $url) ? $url : base_path($url);
}

function url_news(string $slug): string
{
    return PRETTY_URLS
        ? base_path('/news/' . rawurlencode($slug))
        : base_path('/news.php?slug=' . urlencode($slug));
}

function url_category(string $slug): string
{
    return PRETTY_URLS
        ? base_path('/category/' . rawurlencode($slug))
        : base_path('/category.php?slug=' . urlencode($slug));
}

function url_search(string $query = ''): string
{
    $qs = $query !== '' ? ('?q=' . urlencode($query)) : '';
    return base_path('/search.php' . $qs);
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function current_url_with(array $overrides): string
{
    $params = $_GET;
    foreach ($overrides as $k => $v) {
        if ($v === null) {
            unset($params[$k]);
        } else {
            $params[$k] = $v;
        }
    }
    $qs = http_build_query($params);
    $self = strtok($_SERVER['REQUEST_URI'] ?? '', '?');
    return $self . ($qs ? '?' . $qs : '');
}

// ── Slugs ───────────────────────────────────────────────────────────────

/** Cyrillic-safe slug: keeps letters/numbers from any script, ASCII or not. */
function slugify(string $text): string
{
    $text = trim($text);
    $text = mb_strtolower($text, 'UTF-8');
    $text = str_replace(["'", '"', '«', '»', '„', '“', '”'], '', $text);
    $text = preg_replace('/[^\p{L}\p{N}]+/u', '-', $text);
    $text = trim($text, '-');
    $text = preg_replace('/-{2,}/', '-', $text);
    return mb_substr($text, 0, 120, 'UTF-8');
}

/** Appends -2, -3, ... until the slug is free in $table. $table is always a trusted literal, never user input. */
function unique_slug(PDO $pdo, string $table, string $baseSlug, ?int $excludeId = null): string
{
    $baseSlug = $baseSlug !== '' ? $baseSlug : 'item';
    $slug = $baseSlug;
    $i = 2;
    while (true) {
        $sql = "SELECT id FROM {$table} WHERE slug = :slug" . ($excludeId ? ' AND id != :id' : '');
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':slug', $slug);
        if ($excludeId) {
            $stmt->bindValue(':id', $excludeId, PDO::PARAM_INT);
        }
        $stmt->execute();
        if (!$stmt->fetch()) {
            return $slug;
        }
        $slug = $baseSlug . '-' . $i;
        $i++;
    }
}

// ── Mongolian date/time formatting ────────────────────────────────────

const MN_MONTHS = [
    '1-р сар', '2-р сар', '3-р сар', '4-р сар', '5-р сар', '6-р сар',
    '7-р сар', '8-р сар', '9-р сар', '10-р сар', '11-р сар', '12-р сар',
];

function format_mn_date($date): string
{
    $d = $date instanceof DateTimeInterface ? $date : new DateTime((string) $date);
    return $d->format('Y') . ' оны ' . MN_MONTHS[(int) $d->format('n') - 1] . 'ын ' . (int) $d->format('j');
}

function format_mn_datetime($date): string
{
    $d = $date instanceof DateTimeInterface ? $date : new DateTime((string) $date);
    return format_mn_date($d) . ', ' . $d->format('H:i');
}

function format_relative_mn($date): string
{
    $d = $date instanceof DateTimeInterface ? $date : new DateTime((string) $date);
    $diff = time() - $d->getTimestamp();
    $minutes = intdiv($diff, 60);

    if ($minutes < 1) {
        return 'дөнгөж сая';
    }
    if ($minutes < 60) {
        return $minutes . ' минутын өмнө';
    }
    $hours = intdiv($minutes, 60);
    if ($hours < 24) {
        return $hours . ' цагийн өмнө';
    }
    $days = intdiv($hours, 24);
    if ($days < 7) {
        return $days . ' өдрийн өмнө';
    }
    return format_mn_date($d);
}

function to_datetime_local(?string $mysqlDatetime): string
{
    if (!$mysqlDatetime) {
        return '';
    }
    try {
        return (new DateTime($mysqlDatetime))->format('Y-m-d\TH:i');
    } catch (Exception) {
        return '';
    }
}

function from_datetime_local(string $value): ?string
{
    $value = trim($value);
    if ($value === '') {
        return null;
    }
    $d = DateTime::createFromFormat('Y-m-d\TH:i', $value);
    return $d ? $d->format('Y-m-d H:i:s') : null;
}

// ── Text utilities ─────────────────────────────────────────────────────

function strip_html_text(string $html): string
{
    $text = preg_replace('/<[^>]*>/', ' ', $html);
    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    $text = preg_replace('/\s+/u', ' ', $text);
    return trim($text);
}

function calc_reading_time(string $text): int
{
    $plain = strip_html_text($text);
    $words = preg_split('/\s+/u', $plain, -1, PREG_SPLIT_NO_EMPTY);
    $count = $words ? count($words) : 0;
    return max(1, (int) round($count / 180));
}

function make_excerpt(string $text, int $maxLength = 180): string
{
    $plain = strip_html_text($text);
    if (mb_strlen($plain, 'UTF-8') <= $maxLength) {
        return $plain;
    }
    $cut = mb_substr($plain, 0, $maxLength, 'UTF-8');
    $cut = preg_replace('/\s+\S*$/u', '', $cut);
    return $cut . '…';
}

function format_view_count(int $count): string
{
    if ($count >= 1000000) {
        return rtrim(rtrim(number_format($count / 1000000, 1), '0'), '.') . 'сая';
    }
    if ($count >= 1000) {
        return rtrim(rtrim(number_format($count / 1000, 1), '0'), '.') . 'мян';
    }
    return (string) $count;
}

/** Darkens a #RRGGBB hex color for hover states. */
function darken_hex(string $hex, float $amount = 0.12): string
{
    if (!preg_match('/^#([0-9a-fA-F]{6})$/', $hex, $m)) {
        return $hex;
    }
    $num = hexdec($m[1]);
    $r = max(0, (int) round((($num >> 16) & 0xFF) * (1 - $amount)));
    $g = max(0, (int) round((($num >> 8) & 0xFF) * (1 - $amount)));
    $b = max(0, (int) round(($num & 0xFF) * (1 - $amount)));
    return sprintf('#%02x%02x%02x', $r, $g, $b);
}

function status_label(string $status): string
{
    return match ($status) {
        'draft' => 'Ноорог',
        'scheduled' => 'Товлогдсон',
        'published' => 'Нийтлэгдсэн',
        'archived' => 'Архивласан',
        default => $status,
    };
}

// ── CSRF ────────────────────────────────────────────────────────────────

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): bool
{
    return isset($_POST['csrf_token'], $_SESSION['csrf_token'])
        && is_string($_POST['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

function csrf_require(): void
{
    if (!csrf_verify()) {
        http_response_code(403);
        die('Хүчингүй хүсэлт (CSRF токен тохирохгүй байна). Хуудсаа шинэчилж дахин оролдоно уу.');
    }
}

// ── Flash messages ─────────────────────────────────────────────────────

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flash_get(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

// ── Pagination ─────────────────────────────────────────────────────────

function pagination_range(int $current, int $total, int $span = 2): array
{
    $start = max(1, $current - $span);
    $end = min($total, $current + $span);
    return range($start, $end);
}

/** Appends a {page} token to a URL, using ? or & correctly whether or not it already has a query string. */
function paginate_url(string $baseUrl): string
{
    $sep = str_contains($baseUrl, '?') ? '&' : '?';
    return $baseUrl . $sep . 'page={page}';
}

/** $urlPattern must contain the literal token {page}. */
function render_pagination(int $current, int $totalPages, string $urlPattern): string
{
    if ($totalPages <= 1) {
        return '';
    }
    $link = fn(int $p) => e(str_replace('{page}', (string) $p, $urlPattern));

    $html = '<nav class="pagination" aria-label="Хуудаслалт">';
    $html .= $current <= 1
        ? '<span class="page-btn is-disabled">‹ Өмнөх</span>'
        : '<a class="page-btn" href="' . $link($current - 1) . '">‹ Өмнөх</a>';

    foreach (pagination_range($current, $totalPages) as $p) {
        $html .= $p === $current
            ? '<span class="page-btn is-active">' . $p . '</span>'
            : '<a class="page-btn" href="' . $link($p) . '">' . $p . '</a>';
    }

    $html .= $current >= $totalPages
        ? '<span class="page-btn is-disabled">Дараах ›</span>'
        : '<a class="page-btn" href="' . $link($current + 1) . '">Дараах ›</a>';
    $html .= '</nav>';
    return $html;
}

// ── Shared article list query fragments ───────────────────────────────
// Reused by index.php, category.php, search.php, news.php (related/prev-next)
// and ajax/load-more.php so every article listing has the same shape.
// ARTICLE_LIST_SELECT covers the common case (SELECT ...FIELDS... FROM ...FROM...).
// ARTICLE_FIELDS / ARTICLE_FROM are the same two halves split apart, for
// queries that need to add computed columns (e.g. a MATCH() relevance score)
// between the field list and the FROM clause.
const ARTICLE_FIELDS = "
    a.id, a.title, a.slug, a.excerpt, a.featured_image_url, a.featured_image_caption, a.video_url,
    a.author_name, a.published_at, a.created_at, a.view_count, a.reading_time_min,
    a.is_featured, a.is_breaking, a.is_hero, a.category_id,
    c.name AS category_name, c.slug AS category_slug, c.color AS category_color
";

const ARTICLE_FROM = "FROM articles a LEFT JOIN categories c ON c.id = a.category_id";

const ARTICLE_LIST_SELECT = "
    a.id, a.title, a.slug, a.excerpt, a.featured_image_url, a.featured_image_caption, a.video_url,
    a.author_name, a.published_at, a.created_at, a.view_count, a.reading_time_min,
    a.is_featured, a.is_breaking, a.is_hero, a.category_id,
    c.name AS category_name, c.slug AS category_slug, c.color AS category_color
    FROM articles a LEFT JOIN categories c ON c.id = a.category_id
";

function youtube_embed_url(string $url): ?string
{
    $patterns = [
        '~youtube\.com/watch\?v=([\w-]{11})~',
        '~youtu\.be/([\w-]{11})~',
        '~youtube\.com/embed/([\w-]{11})~',
        '~youtube\.com/shorts/([\w-]{11})~',
    ];
    foreach ($patterns as $p) {
        if (preg_match($p, $url, $m)) {
            return 'https://www.youtube-nocookie.com/embed/' . $m[1];
        }
    }
    return null;
}

// ── SEO ─────────────────────────────────────────────────────────────────

function json_ld_script(array $data): string
{
    return '<script type="application/ld+json">'
        . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        . '</script>';
}

// ── Article HTML sanitizer (allowlist-based, mirrors the reference's
//    sanitize-html config) ────────────────────────────────────────────

function sanitize_article_html(string $html): string
{
    $html = trim($html);
    if ($html === '') {
        return '';
    }

    $allowedTags = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'a', 'ul', 'ol', 'li',
        'h2', 'h3', 'h4', 'blockquote', 'img', 'figure', 'figcaption',
        'table', 'thead', 'tbody', 'tr', 'th', 'td', 'iframe', 'hr', 'span',
    ];
    $allowedAttrs = [
        'a' => ['href', 'target', 'rel'],
        'img' => ['src', 'alt', 'width', 'height'],
        'iframe' => ['src', 'allow', 'allowfullscreen', 'frameborder', 'title'],
        'span' => ['class'],
    ];
    $allowedIframeHosts = ['youtube.com', 'www.youtube.com', 'youtube-nocookie.com', 'www.youtube-nocookie.com'];
    $allowedSchemes = ['http', 'https', 'mailto'];

    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML(
        '<?xml encoding="utf-8"?><div id="__root__">' . $html . '</div>',
        LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED
    );
    libxml_clear_errors();

    $root = $doc->getElementById('__root__');
    if (!$root) {
        return '';
    }

    sanitize_dom_node($doc, $root, $allowedTags, $allowedAttrs, $allowedIframeHosts, $allowedSchemes);

    $out = '';
    foreach (iterator_to_array($root->childNodes) as $child) {
        $out .= $doc->saveHTML($child);
    }
    return trim($out);
}

function sanitize_dom_node(DOMDocument $doc, DOMNode $node, array $allowedTags, array $allowedAttrs, array $allowedIframeHosts, array $allowedSchemes): void
{
    foreach (iterator_to_array($node->childNodes) as $child) {
        if ($child->nodeType === XML_TEXT_NODE) {
            continue;
        }
        if ($child->nodeType !== XML_ELEMENT_NODE) {
            $node->removeChild($child);
            continue;
        }

        /** @var DOMElement $child */
        $tag = strtolower($child->tagName);

        if ($tag === 'script' || $tag === 'style') {
            $node->removeChild($child);
            continue;
        }

        if (!in_array($tag, $allowedTags, true)) {
            // Unwrap disallowed tags instead of dropping their (allowed) content.
            sanitize_dom_node($doc, $child, $allowedTags, $allowedAttrs, $allowedIframeHosts, $allowedSchemes);
            while ($child->firstChild) {
                $node->insertBefore($child->firstChild, $child);
            }
            $node->removeChild($child);
            continue;
        }

        $allowed = $allowedAttrs[$tag] ?? [];
        foreach (iterator_to_array($child->attributes ?? []) as $attr) {
            $name = strtolower($attr->name);
            if (!in_array($name, $allowed, true)) {
                $child->removeAttribute($attr->name);
                continue;
            }
            if (in_array($name, ['href', 'src'], true)) {
                $value = trim($attr->value);
                if (stripos($value, 'javascript:') === 0 || stripos($value, 'data:') === 0) {
                    $child->removeAttribute($attr->name);
                    continue;
                }
                $scheme = parse_url($value, PHP_URL_SCHEME);
                if ($scheme !== null && !in_array(strtolower($scheme), $allowedSchemes, true)) {
                    $child->removeAttribute($attr->name);
                }
            }
        }

        if ($tag === 'iframe') {
            $src = $child->getAttribute('src');
            $host = strtolower((string) parse_url($src, PHP_URL_HOST));
            if ($src === '' || !in_array($host, $allowedIframeHosts, true)) {
                $node->removeChild($child);
                continue;
            }
        }

        if ($tag === 'a') {
            $child->setAttribute('rel', 'noopener noreferrer nofollow');
        }

        sanitize_dom_node($doc, $child, $allowedTags, $allowedAttrs, $allowedIframeHosts, $allowedSchemes);
    }
}
