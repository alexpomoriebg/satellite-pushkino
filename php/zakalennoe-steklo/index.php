<?php
require_once __DIR__ . '/../_inc/layout.php';
require_once __DIR__ . '/../_inc/sections.php';

$page = page_load('zakalennoe-steklo');
if (!$page) { http_response_code(404); exit('Page not found'); }

set_page_slug('zakalennoe-steklo');

$c = city();
$canonical = 'https://' . $c['city_slug'] . '.' . $c['parent_site'] . '/zakalennoe-steklo.html';
layout_head(t($page['seo']['title'] ?? ''), t($page['seo']['description'] ?? ''), $canonical);

foreach ($page['sections'] ?? [] as $section) {
    render_section($section);
}

layout_foot();
