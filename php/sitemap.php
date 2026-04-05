<?php
/**
 * Dynamic sitemap.xml generator.
 * Reads page slugs from _data/pages/ directory.
 */
require_once __DIR__ . '/_inc/helpers.php';

$city = city();
$base = 'https://' . $city['city_slug'] . '.' . $city['parent_site'];

header('Content-Type: application/xml; charset=UTF-8');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

$pages_dir = base_path() . '/_data/pages';
$slugs = [];
foreach (glob($pages_dir . '/*.json') as $file) {
    $slugs[] = basename($file, '.json');
}

$priorities = [
    'index' => '1.0',
    'steklo' => '0.9',
    'produkciya' => '0.9',
    'steklopakety' => '0.9',
    'dushevye' => '0.9',
    'zerkala' => '0.8',
    'uslugi' => '0.8',
    'dostavka' => '0.7',
    'online-raschet' => '0.7',
    'kontakty' => '0.6',
];

// Slugs to exclude from sitemap (greenhouse pages not used on this satellite)
$excludeSlugs = ['teplicy', 'steklo-ili-polikarbonat', 'kakoe-steklo-dlya-teplicy', 'polikarbonat-problemy', 'teplica-iz-stekla-svoimi-rukami', 'stoimost-steklyannoj-teplicy', 'osteklenie-balkona', 'teploe-ili-holodnoe', 'pochemu-poteet-balkon', 'uteplenie-balkona'];

// Article slug → URL mapping (parent/article/)
$articlePaths = [
    'vidy-stekla' => '/steklo/vidy-stekla/',
    'zakalennoe-vs-obychnoe' => '/steklo/zakalennoe-vs-obychnoe/',
    'kak-vybrat-steklopaket' => '/steklopakety/kak-vybrat/',
    'steklo-dlya-dushevoj' => '/dushevye/steklo-dlya-dushevoj/',
    'dushevaya-iz-stekla-svoimi-rukami' => '/dushevye/dushevaya-iz-stekla-svoimi-rukami/',
    'zamena-steklopaketa' => '/steklopakety/zamena-steklopaketa/',
];

foreach ($slugs as $slug) {
    if (in_array($slug, $excludeSlugs)) continue;
    if (isset($articlePaths[$slug])) {
        $path = $articlePaths[$slug];
        $priority = '0.7';
        $freq = 'monthly';
    } elseif ($slug === 'index') {
        $path = '/';
        $priority = '1.0';
        $freq = 'weekly';
    } else {
        $path = "/$slug/";
        $priority = $priorities[$slug] ?? '0.5';
        $freq = 'monthly';
    }
    echo "  <url>\n";
    echo "    <loc>{$base}{$path}</loc>\n";
    echo "    <changefreq>{$freq}</changefreq>\n";
    echo "    <priority>{$priority}</priority>\n";
    echo "  </url>\n";
}

echo '</urlset>';
