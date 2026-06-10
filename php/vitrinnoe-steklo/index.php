<?php
require_once __DIR__ . '/../_inc/layout.php';
require_once __DIR__ . '/../_inc/sections.php';

$page = page_load('vitrinnoe-steklo');
if (!$page) { http_response_code(404); exit('Page not found'); }

set_page_slug('vitrinnoe-steklo');

$c = city();
// Восстановление на историческом URL: canonical = старый .html
$canonical = 'https://' . $c['city_slug'] . '.' . $c['parent_site'] . '/vitrinnoe-steklo.html';
layout_head(t($page['seo']['title'] ?? ''), t($page['seo']['description'] ?? ''), $canonical);

foreach ($page['sections'] ?? [] as $section) {
    render_section($section);
}

layout_foot();
