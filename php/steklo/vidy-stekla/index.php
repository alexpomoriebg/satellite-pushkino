<?php
require_once __DIR__ . '/../../_inc/layout.php';
require_once __DIR__ . '/../../_inc/sections.php';

$page = page_load('vidy-stekla');
if (!$page) { http_response_code(404); exit('Page not found'); }

set_page_slug('vidy-stekla');
$GLOBALS['_cms_current_page'] = $page;
$GLOBALS['_cms_breadcrumbs'] = $page['sections'][0]['data']['items'] ?? null;

layout_head(t($page['seo']['title'] ?? ''), t($page['seo']['description'] ?? ''));

foreach ($page['sections'] ?? [] as $section) {
    render_section($section);
}

layout_foot();
