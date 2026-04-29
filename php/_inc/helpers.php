<?php
declare(strict_types=1);

/**
 * Helpers — flat-file CMS utility functions.
 */

// ── Escaping & encoding ──────────────────────────────────────────────

function e(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// ── JSON I/O ─────────────────────────────────────────────────────────

function json_load(string $path): array
{
    if (!is_file($path)) {
        return [];
    }
    $raw = file_get_contents($path);
    if ($raw === false) {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function json_save(string $path, array $data): bool
{
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return file_put_contents($path, $json) !== false;
}

// ── Base path ────────────────────────────────────────────────────────

function base_path(): string
{
    // Prefer DOCUMENT_ROOT when running behind a web server;
    // fall back to the php/ directory (one level up from _inc/).
    static $root = null;
    if ($root === null) {
        $root = !empty($_SERVER['DOCUMENT_ROOT'])
            ? rtrim($_SERVER['DOCUMENT_ROOT'], '/\\')
            : dirname(__DIR__);
    }
    return $root;
}

// ── City data (singleton) ────────────────────────────────────────────

function city(): array
{
    static $city = null;
    if ($city === null) {
        $city = json_load(base_path() . '/_data/city.json');
    }
    return $city;
}

// ── Template placeholder replacement ─────────────────────────────────

function t(string $str): string
{
    $c = city();

    $map = [
        '{city_name}'           => $c['city_name'] ?? '',
        '{city_accusative}'     => $c['city_accusative'] ?? '',
        '{city_genitive}'       => $c['city_genitive'] ?? '',
        '{city_dative}'         => $c['city_dative'] ?? '',
        '{city_instrumental}'   => $c['city_instrumental'] ?? '',
        '{city_prepositional}'  => $c['city_prepositional'] ?? '',
        '{city_region}'         => $c['city_region'] ?? '',
        '{city_slug}'           => $c['city_slug'] ?? '',
        '{phone}'               => $c['phone'] ?? '',
        '{phone_secondary}'     => $c['phone_secondary'] ?? '',
        '{phone_raw}'           => $c['phone_raw'] ?? '',
        '{email}'               => $c['email'] ?? '',
        '{address_production}'  => $c['address_production'] ?? '',
        '{address_old}'         => $c['address_old'] ?? '',
        '{parent_site}'         => $c['parent_site'] ?? '',
        '{parent_site_url}'     => $c['parent_site_url'] ?? '',
        '{company_name}'        => $c['company_name'] ?? '',
        '{company_legal}'       => $c['company_legal'] ?? '',
        '{company_brand}'       => $c['company_brand'] ?? '',
        '{company_since}'       => (string) ($c['company_since'] ?? ''),
        '{company_inn}'         => $c['company_inn'] ?? '',
        '{company_kpp}'         => $c['company_kpp'] ?? '',
        '{company_ogrn}'        => $c['company_ogrn'] ?? '',
        '{company_legal_address}' => $c['company_legal_address'] ?? '',
        '{company_bank}'        => $c['company_bank'] ?? '',
        '{address_production_full}' => $c['address_production_full'] ?? '',
        '{address_masterskaya_full}' => $c['address_masterskaya_full'] ?? '',
        '{work_hours}'          => $c['work_hours'] ?? '',
        '{delivery_note}'       => $c['delivery_note'] ?? '',
        '{local_note}'          => $c['local_note'] ?? '',
        '{office_notice}'       => $c['office_notice'] ?? '',
    ];

    return strtr($str, $map);
}

// ── CSRF ─────────────────────────────────────────────────────────────

function csrf_token(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_check(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $token = $_POST['_csrf'] ?? '';
    if (!hash_equals($_SESSION['_csrf'] ?? '', $token)) {
        http_response_code(403);
        die('CSRF token mismatch.');
    }
}

// ── Page JSON I/O ────────────────────────────────────────────────────

function page_load(string $slug): array
{
    $slug = preg_replace('/[^a-z0-9_\-]/i', '', $slug);
    return json_load(base_path() . "/_data/pages/{$slug}.json");
}

function page_save(string $slug, array $data): bool
{
    $slug = preg_replace('/[^a-z0-9_\-]/i', '', $slug);
    return json_save(base_path() . "/_data/pages/{$slug}.json", $data);
}
