<?php
declare(strict_types=1);

/**
 * Git-sync — отправка правок CMS в репозиторий GitHub (единая схема деплоя:
 * правка → репо → CI → FTP на прод). Без этого правки жили бы только на сервере
 * и затирались бы следующим деплоем той же страницы.
 *
 * Как работает:
 *  - json_save() (helpers.php) после записи файла под _data/ (кроме dot-файлов) кладёт его
 *    относительный путь в очередь _data/.git-queue.json;
 *  - git_flush() проходит очередь и для каждого файла делает PUT в GitHub Contents API
 *    (один коммит на файл), успешные убирает из очереди; неуспешные остаются — повтор
 *    при следующем сохранении или кнопкой в /admin/.
 *
 * Настройки:
 *  - токен: _data/.auth.json → github_token (fine-grained PAT, Contents: Read and write,
 *    только на репозитории сателлитов). Файл вне git, закрыт .htaccess (RewriteRule ^_data/ - [F]);
 *  - репозиторий: _data/city.json → github_repo ("owner/name"), ветка — github_branch (по умолчанию main);
 *  - путь в репо: файлы сайта лежат в каталоге php/ репозитория → префикс GIT_REPO_PREFIX.
 */

const GIT_REPO_PREFIX = 'php/';
const GIT_API         = 'https://api.github.com';
const GIT_TIMEOUT     = 15;

function git_auth_file(): string  { return base_path() . '/_data/.auth.json'; }
function git_queue_file(): string { return base_path() . '/_data/.git-queue.json'; }

/** Настройки синка: token/repo/branch + флаг configured. */
function git_cfg(): array
{
    $auth = json_load(git_auth_file());
    $city = city();
    $token  = isset($auth['github_token']) ? trim((string)$auth['github_token']) : '';
    $repo   = isset($city['github_repo']) ? trim((string)$city['github_repo']) : '';
    $branch = !empty($city['github_branch']) ? (string)$city['github_branch'] : 'main';
    return [
        'token'      => $token,
        'repo'       => $repo,
        'branch'     => $branch,
        'configured' => $token !== '' && preg_match('#^[\w.-]+/[\w.-]+$#', $repo) === 1,
    ];
}

/** Сохранить токен (из панели /admin/). Пустая строка — удалить. */
function git_set_token(string $token): bool
{
    $auth = json_load(git_auth_file());
    if ($token === '') { unset($auth['github_token']); } else { $auth['github_token'] = $token; }
    return json_save(git_auth_file(), $auth);
}

/** Относительный путь файла в репозитории или null, если файл не подлежит синку. */
function git_repo_path(string $absPath): ?string
{
    $base = str_replace('\\', '/', base_path());
    $abs  = str_replace('\\', '/', $absPath);
    if (strpos($abs, $base . '/_data/') !== 0) { return null; }        // только _data/
    if (strpos(basename($abs), '.') === 0) { return null; }             // dot-файлы (auth, очередь) — не в репо
    return GIT_REPO_PREFIX . substr($abs, strlen($base) + 1);           // php/_data/pages/x.json
}

function git_queue(): array
{
    $q = json_load(git_queue_file());
    return isset($q['files']) && is_array($q['files']) ? $q : ['files' => [], 'last_error' => '', 'last_ok' => ''];
}

function git_queue_save(array $q): void
{
    json_save(git_queue_file(), $q);
}

/** Поставить файл в очередь на коммит. */
function git_enqueue(string $absPath): void
{
    $rel = git_repo_path($absPath);
    if ($rel === null) { return; }
    $q = git_queue();
    if (!in_array($rel, $q['files'], true)) { $q['files'][] = $rel; }
    git_queue_save($q);
}

/** HTTP к GitHub API. Возвращает [code, decoded_json|null, error]. */
function git_api(string $method, string $url, string $token, ?array $body = null): array
{
    $headers = [
        'Authorization: Bearer ' . $token,
        'Accept: application/vnd.github+json',
        'X-GitHub-Api-Version: 2022-11-28',
        'User-Agent: steklotrade-satellite-cms',
        'Content-Type: application/json',
    ];
    $payload = $body === null ? null : json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => GIT_TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => 8,
        ]);
        if ($payload !== null) { curl_setopt($ch, CURLOPT_POSTFIELDS, $payload); }
        $raw  = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);
    } else {
        $ctx = stream_context_create(['http' => [
            'method' => $method, 'header' => implode("\r\n", $headers),
            'content' => $payload ?? '', 'timeout' => GIT_TIMEOUT, 'ignore_errors' => true,
        ]]);
        $raw  = @file_get_contents($url, false, $ctx);
        $code = 0; $err = '';
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) { $code = (int)$m[1]; }
        if ($raw === false) { $err = 'network error'; }
    }
    $json = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;
    return [$code, is_array($json) ? $json : null, $err];
}

/** Закоммитить один файл через Contents API. Возвращает '' при успехе или текст ошибки. */
function git_commit_file(array $cfg, string $relPath, string $message): string
{
    $abs = base_path() . '/' . substr($relPath, strlen(GIT_REPO_PREFIX));
    if (!is_file($abs)) { return "нет файла $relPath"; }
    $content = file_get_contents($abs);
    if ($content === false) { return "не прочитан $relPath"; }

    $url = GIT_API . '/repos/' . $cfg['repo'] . '/contents/' . implode('/', array_map('rawurlencode', explode('/', $relPath)));

    // sha текущей версии (если файл уже есть в репо)
    [$code, $json, $err] = git_api('GET', $url . '?ref=' . rawurlencode($cfg['branch']), $cfg['token']);
    if ($code === 401 || $code === 403) { return 'GitHub отклонил токен (' . $code . ')'; }
    if ($code !== 200 && $code !== 404) { return 'GitHub GET ' . $code . ($err ? " ($err)" : ''); }
    $sha = ($code === 200 && isset($json['sha'])) ? (string)$json['sha'] : null;

    // если содержимое в репо уже такое же — коммит не нужен
    if ($sha !== null && isset($json['content']) && base64_decode(str_replace("\n", '', (string)$json['content'])) === $content) {
        return '';
    }

    $body = ['message' => $message, 'content' => base64_encode($content), 'branch' => $cfg['branch']];
    if ($sha !== null) { $body['sha'] = $sha; }
    [$code, $json, $err] = git_api('PUT', $url, $cfg['token'], $body);
    if ($code === 200 || $code === 201) { return ''; }
    $msg = isset($json['message']) ? (string)$json['message'] : $err;
    return 'GitHub PUT ' . $code . ($msg ? " ($msg)" : '');
}

/**
 * Отправить всё из очереди. Возвращает сводку:
 * ['configured'=>bool,'committed'=>int,'pending'=>int,'error'=>string]
 */
function git_flush(): array
{
    $cfg = git_cfg();
    $q   = git_queue();
    $out = ['configured' => $cfg['configured'], 'committed' => 0, 'pending' => count($q['files']), 'error' => ''];
    if (!$cfg['configured']) {
        $out['error'] = $out['pending'] > 0 ? 'GitHub-токен не задан — правки сохранены только на сайте' : '';
        return $out;
    }
    if (empty($q['files'])) { return $out; }

    $city = city();
    $cityName = isset($city['city_name']) ? (string)$city['city_name'] : '';
    $remaining = [];
    foreach ($q['files'] as $rel) {
        $slug = basename((string)$rel, '.json');
        $err  = git_commit_file($cfg, (string)$rel, 'CMS: правка ' . $slug . ($cityName ? " ($cityName)" : ''));
        if ($err === '') { $out['committed']++; }
        else { $remaining[] = $rel; $out['error'] = $err; }
    }
    $q['files'] = $remaining;
    if ($out['error'] !== '') { $q['last_error'] = date('c') . ' ' . $out['error']; }
    if ($out['committed'] > 0) { $q['last_ok'] = date('c'); }
    git_queue_save($q);
    $out['pending'] = count($remaining);
    return $out;
}
