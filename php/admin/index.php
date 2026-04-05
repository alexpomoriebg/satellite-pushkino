<?php
/**
 * Admin — login / logout / password setup.
 * After login redirects to the site where inline editor appears.
 */
session_start();
require_once __DIR__ . '/../_inc/helpers.php';

$authFile = base_path() . '/_data/.auth.json';

// ── Logout ──────────────────────────────────────────────────────────
if (($_GET['action'] ?? '') === 'logout') {
    $_SESSION = [];
    session_destroy();
    header('Location: /admin/');
    exit;
}

// ── Already logged in → dashboard ───────────────────────────────────
if (!empty($_SESSION['admin_auth'])) {

    // Handle SEO save
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'save_seo') {
        $token = $_POST['_csrf'] ?? '';
        if ($token && hash_equals($_SESSION['_csrf'] ?? '', $token)) {
            $slug = preg_replace('/[^a-z0-9_\-]/i', '', $_POST['slug'] ?? '');
            $page = page_load($slug);
            if ($page) {
                $page['seo']['title']       = trim($_POST['seo_title'] ?? '');
                $page['seo']['description'] = trim($_POST['seo_desc'] ?? '');
                page_save($slug, $page);
                $seoSaved = $slug;
            }
        }
    }

    // Load all pages
    $pagesDir = base_path() . '/_data/pages/';
    $pageFiles = glob($pagesDir . '*.json');
    $pages = [];
    $slugLabels = [
        'index' => 'Главная',
        'steklo' => 'Стекло',
        'produkciya' => 'Продукция',
        'zerkala' => 'Зеркала',
        'uslugi' => 'Услуги',
        'steklopakety' => 'Стеклопакеты',
        'zvukoizolyaciya' => 'Звукоизоляция',
        'online-raschet' => 'Онлайн-расчёт',
        'dostavka' => 'Доставка',
        'kontakty' => 'Контакты',
        // Articles
        'vidy-stekla' => 'Статья: Виды стекла',
        'zakalennoe-vs-obychnoe' => 'Статья: Закалённое vs обычное',
        'kak-vybrat-steklopaket' => 'Статья: Как выбрать стеклопакет',
    ];
    // Article slug → URL (nested under parent)
    $articleUrls = [
        'vidy-stekla' => '/steklo/vidy-stekla/',
        'zakalennoe-vs-obychnoe' => '/steklo/zakalennoe-vs-obychnoe/',
        'kak-vybrat-steklopaket' => '/steklopakety/kak-vybrat/',
    ];
    foreach ($pageFiles as $f) {
        $slug = basename($f, '.json');
        $data = json_load($f);
        if (isset($articleUrls[$slug])) {
            $url = $articleUrls[$slug];
        } elseif ($slug === 'index') {
            $url = '/';
        } else {
            $url = '/' . $slug . '/';
        }
        $pages[] = [
            'slug'  => $slug,
            'label' => $slugLabels[$slug] ?? $slug,
            'url'   => $url,
            'title' => $data['seo']['title'] ?? '',
            'desc'  => $data['seo']['description'] ?? '',
        ];
    }

    $csrf = csrf_token();
    ?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Панель управления</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{background:#0f0f23;font-family:-apple-system,system-ui,'Segoe UI',sans-serif;color:#e0e0e0;padding:1.5rem;line-height:1.5}
.top{display:flex;align-items:center;justify-content:space-between;max-width:960px;margin:0 auto 2rem;flex-wrap:wrap;gap:.75rem}
.top h1{font-size:1.3rem;font-weight:700;color:#fff}
.top-links{display:flex;gap:12px}
.top-links a{color:#aaa;text-decoration:none;font-size:.9rem;padding:6px 14px;border-radius:6px;transition:all .15s}
.top-links a:hover{color:#fff;background:rgba(255,255,255,.08)}
.top-links a.active{color:#e94560;background:rgba(233,69,96,.1)}
.hint{max-width:960px;margin:0 auto 1.5rem;background:#1a1a2e;padding:1rem 1.25rem;border-radius:10px;font-size:.9rem;color:#aaa;border-left:3px solid #e94560}
.card{max-width:960px;margin:0 auto 1rem;background:#1a1a2e;border-radius:10px;border:1px solid #2a2a4a;transition:border-color .15s}
.card:hover{border-color:#3a3a5a}
.card-head{display:flex;align-items:center;justify-content:space-between;padding:.85rem 1.25rem;cursor:pointer;user-select:none;list-style:none}
.card-head::-webkit-details-marker{display:none}
.card-head::after{content:'\\25BC';color:#666;font-size:.8rem;transition:transform .2s}
.card[open] .card-head::after{transform:rotate(180deg)}
.card-head h2{font-size:1rem;font-weight:600;color:#fff}
.card-head .url{font-size:.8rem;color:#888;font-weight:400}
.card-body{padding:0 1.25rem 1.25rem;border-top:1px solid #2a2a4a}
label{display:block;font-size:.8rem;color:#888;margin-bottom:.3rem;margin-top:.85rem}
input[type=text],textarea{width:100%;padding:10px 14px;background:#16213e;border:1px solid #333;border-radius:8px;color:#fff;font:inherit;font-size:.92rem;outline:none;transition:border-color .15s}
input[type=text]:focus,textarea:focus{border-color:#e94560}
textarea{resize:vertical;min-height:60px}
.char-count{font-size:.75rem;color:#666;text-align:right;margin-top:.2rem}
.save-row{display:flex;align-items:center;gap:12px;margin-top:1rem}
.save-btn{padding:8px 22px;background:#00b894;color:#fff;border:none;border-radius:6px;font:inherit;font-weight:600;font-size:.9rem;cursor:pointer;transition:background .15s}
.save-btn:hover{background:#00a381}
.saved-msg{font-size:.85rem;color:#00b894}
</style>
</head>
<body>

<div class="top">
  <h1>Панель управления</h1>
  <div class="top-links">
    <a href="/" target="_blank">Открыть сайт</a>
    <a href="?action=logout">Выйти</a>
  </div>
</div>

<div class="hint">
  Для редактирования текста на страницах — перейдите на сайт и нажмите «Редактировать» в верхней панели.<br>
  Здесь можно управлять SEO-заголовками и описаниями каждой страницы.
</div>

<?php foreach ($pages as $p): ?>
<details class="card" open>
  <summary class="card-head">
    <h2><?= e($p['label']) ?> <span class="url"><?= e($p['url']) ?></span></h2>
  </summary>
  <div class="card-body" style="padding-top:1rem;">
    <form method="POST">
      <input type="hidden" name="_action" value="save_seo">
      <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
      <input type="hidden" name="slug" value="<?= e($p['slug']) ?>">

      <label>Title (заголовок страницы в поиске)</label>
      <input type="text" name="seo_title" value="<?= e($p['title']) ?>" maxlength="120"
             oninput="this.nextElementSibling.textContent=this.value.length+'/70'">
      <div class="char-count"><?= mb_strlen($p['title']) ?>/70</div>

      <label>Description (описание в поиске)</label>
      <textarea name="seo_desc" maxlength="300"
                oninput="this.nextElementSibling.textContent=this.value.length+'/160'"><?= e($p['desc']) ?></textarea>
      <div class="char-count"><?= mb_strlen($p['desc']) ?>/160</div>

      <div class="save-row">
        <button type="submit" class="save-btn">Сохранить</button>
        <?php if (isset($seoSaved) && $seoSaved === $p['slug']): ?>
        <span class="saved-msg">Сохранено!</span>
        <?php endif; ?>
      </div>
    </form>
  </div>
</details>
<?php endforeach; ?>

</body>
</html>
    <?php
    exit;
}

// ── Password setup (first time) ─────────────────────────────────────
$auth     = json_load($authFile);
$hasPass  = !empty($auth['password_hash']);
$isSetup  = !$hasPass;

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($isSetup) {
        // Setting new password
        $pw  = $_POST['password'] ?? '';
        $pw2 = $_POST['password2'] ?? '';
        if (strlen($pw) < 6) {
            $error = 'Минимум 6 символов';
        } elseif ($pw !== $pw2) {
            $error = 'Пароли не совпадают';
        } else {
            json_save($authFile, ['password_hash' => password_hash($pw, PASSWORD_BCRYPT)]);
            $_SESSION['admin_auth'] = true;
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
            header('Location: /admin/');
            exit;
        }
    } else {
        // Login
        $pw = $_POST['password'] ?? '';
        if (password_verify($pw, $auth['password_hash'])) {
            $_SESSION['admin_auth'] = true;
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
            header('Location: /admin/');
            exit;
        } else {
            $error = 'Неверный пароль';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $isSetup ? 'Установка пароля' : 'Вход' ?></title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{min-height:100vh;display:flex;align-items:center;justify-content:center;background:#0f0f23;font-family:-apple-system,system-ui,'Segoe UI',sans-serif;color:#fff}
.login{background:#1a1a2e;padding:2.5rem;border-radius:12px;width:100%;max-width:380px;box-shadow:0 20px 60px rgba(0,0,0,.5)}
.login h1{font-size:1.3rem;font-weight:600;margin-bottom:.5rem;text-align:center}
.login .sub{color:#888;text-align:center;font-size:.85rem;margin-bottom:1.5rem}
.login input{width:100%;padding:12px 16px;border:1px solid #333;border-radius:8px;background:#16213e;color:#fff;font-size:1rem;outline:none;transition:border-color .2s;margin-bottom:.7rem}
.login input:focus{border-color:#e94560}
.login button{width:100%;padding:12px;margin-top:.5rem;background:#e94560;color:#fff;border:none;border-radius:8px;font-size:1rem;font-weight:600;cursor:pointer;transition:background .2s}
.login button:hover{background:#c81e45}
.error{background:rgba(233,69,96,.15);color:#e94560;padding:10px;border-radius:6px;text-align:center;margin-bottom:1rem;font-size:.9rem}
</style>
</head>
<body>
<form class="login" method="POST">
    <h1><?= $isSetup ? 'Установка пароля' : 'Вход в редактор' ?></h1>
    <div class="sub"><?= $isSetup ? 'Придумайте пароль для редактирования сайта' : 'Введите пароль администратора' ?></div>
    <?php if ($error): ?><div class="error"><?= e($error) ?></div><?php endif; ?>
    <input type="password" name="password" placeholder="Пароль" autofocus required>
    <?php if ($isSetup): ?>
    <input type="password" name="password2" placeholder="Повторите пароль" required>
    <?php endif; ?>
    <button type="submit"><?= $isSetup ? 'Установить' : 'Войти' ?></button>
</form>
</body>
</html>
