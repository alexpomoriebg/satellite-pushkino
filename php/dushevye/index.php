<?php
require_once __DIR__ . '/../_inc/layout.php';
require_once __DIR__ . '/../_inc/sections.php';

$page = page_load('dushevye');
if (!$page) { http_response_code(404); exit('Page not found'); }

set_page_slug('dushevye');
layout_head(t($page['seo']['title'] ?? ''), t($page['seo']['description'] ?? ''));

foreach ($page['sections'] ?? [] as $section) {
    render_section($section);
}

layout_foot();
