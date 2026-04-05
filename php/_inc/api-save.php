<?php
/**
 * AJAX API — saves inline-edited regions to page JSON files.
 * Protected: requires admin session + CSRF token.
 */
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=UTF-8');

session_start();
if (empty($_SESSION['admin_auth'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !is_array($input)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

// CSRF check
$token = $input['_csrf'] ?? '';
if (!$token || !hash_equals($_SESSION['_csrf'] ?? '', $token)) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

$regions = $input['regions'] ?? [];
if (empty($regions)) {
    echo json_encode(['ok' => true, 'changed' => 0]);
    exit;
}

$changed = 0;
$errors  = [];

foreach ($regions as $regionName => $html) {
    // Region format: "pageSlug:sectionIndex:fieldPath"
    $parts = explode(':', $regionName, 3);
    if (count($parts) < 3) {
        $errors[] = "Invalid region: $regionName";
        continue;
    }

    $pageSlug     = preg_replace('/[^a-z0-9_\-]/i', '', $parts[0]);
    $sectionIndex = (int) $parts[1];
    $fieldPath    = $parts[2];

    $page = page_load($pageSlug);
    if (!$page || !isset($page['sections'][$sectionIndex])) {
        $errors[] = "Not found: $pageSlug:$sectionIndex";
        continue;
    }

    // Strip all tags → plain text (safe for all fields)
    $cleanText = trim(strip_tags($html));

    // Navigate nested path like "cards.0.title"
    $section  = &$page['sections'][$sectionIndex]['data'];
    $pathBits = explode('.', $fieldPath);
    $lastKey  = array_pop($pathBits);
    $target   = &$section;

    foreach ($pathBits as $key) {
        if (is_numeric($key)) {
            $key = (int) $key;
        }
        if (!isset($target[$key])) {
            $errors[] = "Path not found: $fieldPath";
            continue 2;
        }
        $target = &$target[$key];
    }

    $target[$lastKey] = $cleanText;
    unset($target, $section);

    page_save($pageSlug, $page);
    $changed++;
}

echo json_encode([
    'ok'      => empty($errors),
    'changed' => $changed,
    'errors'  => $errors,
]);
