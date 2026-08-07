<?php
/**
 * Loads (and caches for the current request) the site_settings singleton row.
 */

function load_site_settings(PDO $pdo): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $row = $pdo->query('SELECT * FROM site_settings WHERE id = 1')->fetch();

    if (!$row) {
        // Degrade gracefully if schema.sql's seed row was somehow removed.
        $row = [
            'site_name' => 'Мэдээний сайт',
            'logo_url' => null,
            'logo_dark_url' => null,
            'favicon_url' => null,
            'primary_color' => '#1d4ed8',
            'contact_email' => null,
            'contact_phone' => null,
            'contact_address' => null,
            'facebook_url' => null,
            'twitter_url' => null,
            'youtube_url' => null,
            'instagram_url' => null,
            'footer_description' => null,
            'seo_default_title' => null,
            'seo_default_description' => null,
            'analytics_id' => null,
            'articles_per_page' => 12,
            'dark_mode_enabled' => 1,
            'maintenance_mode' => 0,
            'maintenance_message' => null,
        ];
    }

    $cache = $row;
    return $cache;
}
