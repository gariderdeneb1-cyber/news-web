<?php
/**
 * Render helpers for the article "card" variants used across the homepage,
 * category, search, and related-articles sections. Every function takes an
 * associative array shaped like a row from `articles` LEFT JOIN `categories`
 * (expects: slug, title, excerpt, featured_image_url, category_name,
 * category_slug, category_color, author_name, published_at/created_at).
 */

function article_image_block(array $article, string $ratioClass = 'ratio-16-10', bool $hoverable = true): string
{
    $classes = 'img-wrap ' . $ratioClass . ($hoverable ? ' is-hoverable' : '');
    if (!empty($article['featured_image_url'])) {
        return '<div class="' . $classes . '"><img src="' . e($article['featured_image_url']) . '" alt="' . e($article['title']) . '" loading="lazy"></div>';
    }
    $color = $article['category_color'] ?? '#1d4ed8';
    $label = $article['category_name'] ?? 'Мэдээ';
    return '<div class="' . $classes . '"><div class="img-fallback" style="--fallback-color:' . e($color) . '">' . e($label) . '</div></div>';
}

function article_meta_date(array $article): string
{
    return e(format_relative_mn($article['published_at'] ?? $article['created_at']));
}

function render_card_hero_main(array $article): string
{
    return '<a href="' . e(url_news($article['slug'])) . '" class="card-hero-main">'
        . article_image_block($article, 'ratio-16-9')
        . '<div class="card-hero-main__overlay"><h2 class="card-hero-main__title line-clamp-2">' . e($article['title']) . '</h2></div>'
        . '</a>';
}

function render_card_hero_sub(array $article): string
{
    return '<a href="' . e(url_news($article['slug'])) . '" class="card-hero-sub">'
        . article_image_block($article, 'ratio-square', false)
        . '<div><h3 class="card-hero-sub__title line-clamp-2">' . e($article['title']) . '</h3>'
        . '<div class="meta"><span>' . article_meta_date($article) . '</span></div></div>'
        . '</a>';
}

function render_card_horizontal(array $article, bool $showExcerpt = true): string
{
    $excerpt = $showExcerpt && !empty($article['excerpt'])
        ? '<p class="card-horizontal__excerpt line-clamp-2">' . e($article['excerpt']) . '</p>'
        : '';
    $author = !empty($article['author_name']) ? '<span>' . e($article['author_name']) . '</span><span class="sep">·</span>' : '';
    return '<a href="' . e(url_news($article['slug'])) . '" class="card-horizontal">'
        . article_image_block($article, 'ratio-4-3')
        . '<div><h3 class="card-horizontal__title line-clamp-2">' . e($article['title']) . '</h3>'
        . $excerpt
        . '<div class="meta" style="margin-top:.5rem">' . $author . '<span>' . article_meta_date($article) . '</span></div>'
        . '</div></a>';
}

function render_card_grid(array $article): string
{
    return '<a href="' . e(url_news($article['slug'])) . '" class="card-grid-item">'
        . article_image_block($article, 'ratio-16-10')
        . '<h3 class="card-grid-item__title line-clamp-2">' . e($article['title']) . '</h3>'
        . '<div class="meta" style="margin-top:.5rem"><span>' . article_meta_date($article) . '</span></div>'
        . '</a>';
}

function render_card_compact(array $article, ?int $rank = null): string
{
    $rankHtml = $rank !== null
        ? '<span class="card-compact__rank' . ($rank <= 3 ? ' is-top' : '') . '">' . $rank . '</span>'
        : '';
    return '<a href="' . e(url_news($article['slug'])) . '" class="card-compact">'
        . $rankHtml
        . '<div><h3 class="card-compact__title line-clamp-2">' . e($article['title']) . '</h3>'
        . '<div class="meta"><span>' . e(format_view_count((int) ($article['view_count'] ?? 0))) . ' үзсэн</span></div></div>'
        . '</a>';
}
