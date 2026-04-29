<?php
declare(strict_types=1);

/**
 * Layout — header, footer, inline editor for admins.
 */

require_once __DIR__ . '/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Head + Header + Nav + <main> ─────────────────────────────────────

function layout_head(string $title, string $description, ?string $canonical = null): void
{
    $c = city();

    $companyName = $c['company_name'] ?? '';
    $cityName    = $c['city_name'] ?? '';
    $titleResolved = t($title);
    // Don't append brand suffix if title already contains company name
    if (stripos($titleResolved, $companyName) !== false) {
        $fullTitle = e($titleResolved);
    } else {
        $fullTitle = e($titleResolved) . ' | ' . e($companyName) . ' ' . e($cityName);
    }

    $citySlug   = $c['city_slug'] ?? '';
    $parentSite = $c['parent_site'] ?? '';
    $siteUrl    = 'https://' . $citySlug . '.' . $parentSite;

    if ($canonical) {
        $pageUrl = $canonical;
    } else {
        $pageUrl = $siteUrl . ($_SERVER['REQUEST_URI'] ?? '/');
    }

    $descEsc = e(t($description));

    // Schema.org LocalBusiness
    $localBusiness = json_encode([
        '@context'     => 'https://schema.org',
        '@type'        => 'LocalBusiness',
        'name'         => ($c['company_name'] ?? '') . ' — ' . ($c['city_name'] ?? ''),
        'description'  => t($description),
        'url'          => $siteUrl,
        'telephone'    => $c['phone'] ?? '',
        'email'        => $c['email'] ?? '',
        'address'      => [
            '@type'           => 'PostalAddress',
            'addressLocality' => $c['city_name'] ?? '',
            'addressRegion'   => $c['city_region'] ?? '',
            'addressCountry'  => 'RU',
        ],
        'geo' => [
            '@type'     => 'GeoCoordinates',
            'latitude'  => $c['coords']['lat'] ?? 0,
            'longitude' => $c['coords']['lng'] ?? 0,
        ],
        'openingHours' => 'Mo-Fr 09:00-18:00, Sa 10:00-15:00',
        'priceRange'   => '$$',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    // Schema.org BreadcrumbList (supports 2 or 3 levels for articles)
    $bcItems = $GLOBALS['_cms_breadcrumbs'] ?? null;
    if ($bcItems) {
        $bcList = [];
        foreach ($bcItems as $pos => $item) {
            $entry = ['@type' => 'ListItem', 'position' => $pos + 1, 'name' => t($item['label'] ?? '')];
            if (!empty($item['href'])) $entry['item'] = $siteUrl . $item['href'];
            $bcList[] = $entry;
        }
    } else {
        $bcList = [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Главная', 'item' => $siteUrl],
            ['@type' => 'ListItem', 'position' => 2, 'name' => $titleResolved],
        ];
    }
    $breadcrumbs = json_encode([
        '@context'        => 'https://schema.org',
        '@type'           => 'BreadcrumbList',
        'itemListElement' => $bcList,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    // Schema.org Article (for blog/article pages)
    $articleSchema = '';
    $pageData = $GLOBALS['_cms_current_page'] ?? null;
    if ($pageData && !empty($pageData['article'])) {
        $articleSchema = json_encode([
            '@context'      => 'https://schema.org',
            '@type'         => 'Article',
            'headline'      => $titleResolved,
            'description'   => t($description),
            'datePublished' => $pageData['article']['published'] ?? '',
            'dateModified'  => $pageData['article']['updated'] ?? '',
            'publisher'     => ['@type' => 'Organization', 'name' => $companyName],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    // Navigation with optional dropdowns
    $nav = [
        ['href' => '/steklo/',          'label' => 'Стекло', 'children' => [
            ['href' => '/steklo/vidy-stekla/',            'label' => 'Виды стекла: гид по выбору'],
            ['href' => '/steklo/zakalennoe-vs-obychnoe/', 'label' => 'Закалённое vs обычное стекло'],
        ]],
        ['href' => '/produkciya/',      'label' => 'Продукция'],
        ['href' => '/zerkala/',         'label' => 'Зеркала'],
        ['href' => '/uslugi/',          'label' => 'Услуги'],
        ['href' => '/steklopakety/',    'label' => 'Стеклопакеты', 'children' => [
            ['href' => '/steklopakety/kak-vybrat/',       'label' => 'Как выбрать стеклопакет'],
            ['href' => '/steklopakety/zamena-steklopaketa/', 'label' => 'Замена стеклопакета'],
        ]],
        ['href' => '/dushevye/', 'label' => 'Душевые', 'children' => [
            ['href' => '/dushevye/steklo-dlya-dushevoj/', 'label' => 'Какое стекло для душевой'],
            ['href' => '/dushevye/dushevaya-iz-stekla-svoimi-rukami/', 'label' => 'Душевая своими руками'],
        ]],
        ['href' => '/online-raschet/',  'label' => 'Онлайн-расчёт'],
        ['href' => '/dostavka/',        'label' => 'Доставка'],
        ['href' => '/kontakty/',        'label' => 'Контакты'],
    ];

    $phoneRaw = e($c['phone_raw'] ?? '');
    $phone    = e($c['phone'] ?? '');

    ?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $fullTitle ?></title>
  <meta name="description" content="<?= $descEsc ?>">
<?php $robots = $GLOBALS['_cms_robots'] ?? ''; if ($robots): ?>
  <meta name="robots" content="<?= e($robots) ?>">
<?php endif; ?>
  <link rel="canonical" href="<?= e($pageUrl) ?>">
  <link rel="icon" type="image/svg+xml" href="/favicon.svg">
  <link rel="icon" type="image/x-icon" href="/favicon.ico">
  <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="180x180" href="/favicon-180.png">

  <!-- Open Graph -->
  <meta property="og:title" content="<?= $fullTitle ?>">
  <meta property="og:description" content="<?= $descEsc ?>">
  <meta property="og:type" content="website">
  <meta property="og:url" content="<?= e($pageUrl) ?>">
  <meta property="og:locale" content="ru_RU">

  <!-- Schema.org LocalBusiness -->
  <script type="application/ld+json"><?= $localBusiness ?></script>

  <!-- BreadcrumbList -->
  <script type="application/ld+json"><?= $breadcrumbs ?></script>
<?php if ($articleSchema): ?>
  <!-- Article -->
  <script type="application/ld+json"><?= $articleSchema ?></script>
<?php endif; ?>

  <link rel="stylesheet" href="/css/global.css">
<?php if (!empty($_SESSION['admin_auth'])): ?>
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<?php endif; ?>

<?php $gaId = $c['ga_id'] ?? ''; if ($gaId): ?>
  <!-- Google Analytics (GA4) -->
  <script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($gaId) ?>"></script>
  <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','<?= e($gaId) ?>');</script>
<?php endif; ?>
</head>
<body<?php if (!empty($_SESSION['admin_auth'])): ?> class="has-cms-bar"<?php endif; ?>>
  <!-- Header -->
  <header class="header">
    <div class="header__inner">
      <a href="/" class="header__logo"><?= e($companyName) ?> <span>| <?= e($cityName) ?></span></a>
      <a href="tel:<?= $phoneRaw ?>" class="header__phone"><?= $phone ?></a>
    </div>
  </header>

  <!-- Navigation -->
  <nav class="nav" aria-label="Основная навигация">
    <button class="nav__burger" type="button" aria-label="Меню" aria-expanded="false">&#9776; Меню</button>
    <div class="nav__inner">
      <a href="/">Главная</a>
<?php foreach ($nav as $n): ?>
<?php   if (!empty($n['children'])): ?>
      <div class="nav__dropdown">
        <a href="<?= e($n['href']) ?>" class="nav__dropdown-toggle"><?= e($n['label']) ?> <span class="nav__arrow">&#9662;</span></a>
        <div class="nav__dropdown-menu">
          <a href="<?= e($n['href']) ?>">— <?= e($n['label']) ?> (все)</a>
<?php     foreach ($n['children'] as $child): ?>
          <a href="<?= e($child['href']) ?>"><?= e($child['label']) ?></a>
<?php     endforeach; ?>
        </div>
      </div>
<?php   else: ?>
      <a href="<?= e($n['href']) ?>"><?= e($n['label']) ?></a>
<?php   endif; ?>
<?php endforeach; ?>
    </div>
  </nav>
  <script>
  (function(){
    var b=document.querySelector('.nav__burger'),n=document.querySelector('.nav__inner');
    if(b&&n)b.addEventListener('click',function(){var o=n.classList.toggle('open');b.setAttribute('aria-expanded',o?'true':'false');});
    var t=document.querySelectorAll('.nav__dropdown-toggle');
    for(var i=0;i<t.length;i++)t[i].addEventListener('click',function(e){
      if(window.innerWidth>600)return;
      var d=this.parentElement;
      if(!d.classList.contains('open')){e.preventDefault();
        var a=document.querySelectorAll('.nav__dropdown.open');
        for(var j=0;j<a.length;j++)a[j].classList.remove('open');
        d.classList.add('open');
      }
    });
  })();
  </script>

  <!-- Content -->
  <main>
<?php
}

// ── Footer + closing tags ────────────────────────────────────────────

function layout_foot(): void
{
    $c = city();

    $companyName  = e($c['company_name'] ?? '');
    $cityPrepositional = e($c['city_prepositional'] ?? '');
    $deliveryNote = e($c['delivery_note'] ?? '');
    $phoneRaw     = e($c['phone_raw'] ?? '');
    $phone        = e($c['phone'] ?? '');
    $phoneSecRaw  = e(str_replace([' ', '(', ')', '-'], '', $c['phone_secondary'] ?? ''));
    $phoneSec     = e($c['phone_secondary'] ?? '');
    $email        = e($c['email'] ?? '');
    $workHours    = e($c['work_hours'] ?? '');
    $parentSite   = e($c['parent_site'] ?? '');
    $parentUrl    = e($c['parent_site_url'] ?? '');
    $companyLegal = e($c['company_legal'] ?? '');
    $companyInn      = e($c['company_inn'] ?? '');
    $companyKpp      = e($c['company_kpp'] ?? '');
    $companyOgrn     = e($c['company_ogrn'] ?? '');
    $companyLegalAddr= e($c['company_legal_address'] ?? '');
    $companyBank     = e($c['company_bank'] ?? '');
    $companyAcct     = e($c['company_bank_account'] ?? '');
    $companyBik      = e($c['company_bank_bik'] ?? '');
    $companyCorr     = e($c['company_bank_corr'] ?? '');
    $addressOld      = e($c['address_old'] ?? '');
    $cityName        = e($c['city_name'] ?? '');
    $year            = date('Y');

    ?>
  </main>

  <!-- Footer -->
  <footer class="footer">
    <div class="container">
      <div class="footer__grid">
        <div>
          <div class="footer__title"><?= $companyName ?></div>
          <p>Стекло, зеркала, стеклопакеты, тепличное стекло в&nbsp;<?= $cityPrepositional ?>.</p>
          <p style="margin-top:.4rem;"><?= $deliveryNote ?>.</p>
        </div>
        <div>
          <div class="footer__title">Продукция</div>
          <div style="display:flex;flex-direction:column;gap:.35rem;">
            <a href="/steklo/">Листовое стекло</a>
            <a href="/produkciya/">Стеклопакеты</a>
            <a href="/zerkala/">Зеркала</a>
            <a href="/uslugi/">Закалённое стекло</a>
            <a href="/uslugi/">Триплекс</a>
          </div>
        </div>
        <div>
          <div class="footer__title">Сервисы</div>
          <div style="display:flex;flex-direction:column;gap:.35rem;">
            <a href="/teplicy/">Калькулятор стекла для теплиц</a>
            <a href="/online-raschet/">Онлайн-расчёт</a>
            <a href="/dostavka/">Доставка</a>
            <a href="/kontakty/">Контакты</a>
          </div>
        </div>
        <div>
          <div class="footer__title">Контакты</div>
          <p><a href="tel:<?= $phoneRaw ?>"><?= $phone ?></a></p>
          <p><a href="tel:<?= $phoneSecRaw ?>"><?= $phoneSec ?></a></p>
          <p><a href="mailto:<?= $email ?>"><?= $email ?></a></p>
          <p style="margin-top:.4rem;"><?= $workHours ?></p>
          <p style="margin-top:.7rem;opacity:.6;">
            Полный каталог: <a href="<?= $parentUrl ?>" style="color:var(--accent);"><?= $parentSite ?></a>
          </p>
        </div>
      </div>
      <!-- Реквизиты юр.лица (для Яндекса/Роскомнадзора + доверие) -->
      <div class="footer__legal" itemscope itemtype="https://schema.org/Organization" style="margin-top:1.5rem;padding:1rem 0;border-top:1px solid rgba(255,255,255,.12);font-size:.82rem;line-height:1.55;opacity:.78;">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1rem 2rem;">
          <div>
            <div style="font-weight:600;margin-bottom:.25rem;" itemprop="legalName"><?= $companyLegal ?></div>
<?php if ($companyInn): ?>
            <div>ИНН <span itemprop="taxID"><?= $companyInn ?></span><?= $companyKpp ? ' · КПП ' . $companyKpp : '' ?><?= $companyOgrn ? ' · ОГРН <span itemprop="identifier">' . $companyOgrn . '</span>' : '' ?></div>
<?php endif; ?>
<?php if ($companyLegalAddr): ?>
            <div>Юр. адрес: <span itemprop="address"><?= $companyLegalAddr ?></span></div>
<?php endif; ?>
<?php if ($addressOld): ?>
            <div style="margin-top:.25rem;">Филиал в&nbsp;г.&nbsp;<?= $cityName ?> (онлайн-офис): <?= $addressOld ?></div>
<?php endif; ?>
          </div>
<?php if ($companyAcct): ?>
          <div>
            <div style="font-weight:600;margin-bottom:.25rem;">Банковские реквизиты</div>
            <div>Р/с <?= $companyAcct ?></div>
            <div>в&nbsp;<?= $companyBank ?></div>
            <div>БИК <?= $companyBik ?> · К/с <?= $companyCorr ?></div>
          </div>
<?php endif; ?>
        </div>
      </div>

      <div class="footer__bottom" style="display:flex;flex-wrap:wrap;justify-content:space-between;gap:.5rem 1.5rem;align-items:center;">
        <div>&copy; <?= $year ?> <?= $companyLegal ?>. Все права защищены.</div>
        <div style="display:flex;flex-wrap:wrap;gap:.5rem 1.25rem;font-size:.85rem;">
          <a href="/policy/">Политика обработки ПДн</a>
          <a href="/consent/">Согласие на обработку ПДн</a>
          <a href="/cookie/">Политика cookies</a>
        </div>
      </div>
    </div>
  </footer>

  <!-- Cookie-уведомление (Роскомнадзор + 152-ФЗ) -->
  <div id="cookie-notice" style="display:none;position:fixed;bottom:1rem;left:1rem;right:1rem;max-width:780px;margin:0 auto;padding:1rem 1.25rem;background:#1a1a1a;color:#f0f0f0;border-radius:.6rem;box-shadow:0 6px 24px rgba(0,0,0,.25);z-index:9999;font-size:.92rem;line-height:1.5;">
    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:.75rem 1rem;">
      <div style="flex:1;min-width:240px;">Сайт использует файлы cookies и&nbsp;собирает данные для аналитики (Яндекс&nbsp;Метрика). Продолжая просмотр, вы&nbsp;соглашаетесь с&nbsp;<a href="/cookie/" style="color:#7cb6ff;">политикой&nbsp;cookies</a> и&nbsp;<a href="/policy/" style="color:#7cb6ff;">обработкой&nbsp;ПДн</a>.</div>
      <button type="button" onclick="document.getElementById('cookie-notice').style.display='none';try{localStorage.setItem('cookieAck','1')}catch(e){}" style="padding:.45rem 1rem;background:#0066cc;color:#fff;border:0;border-radius:.4rem;font-weight:600;cursor:pointer;">Принимаю</button>
    </div>
  </div>
  <script>(function(){try{if(!localStorage.getItem('cookieAck'))document.getElementById('cookie-notice').style.display='block';}catch(e){document.getElementById('cookie-notice').style.display='block';}})();</script>
<?php if (!empty($_SESSION['admin_auth'])): ?>
<?php _render_inline_editor(); ?>
<?php endif; ?>

<?php $metrikaId = $c['metrika_id'] ?? ''; if ($metrikaId): ?>
<!-- Yandex.Metrika -->
<script type="text/javascript">(function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};m[i].l=1*new Date();for(var j=0;j<document.scripts.length;j++){if(document.scripts[j].src===r){return;}}k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})(window,document,"script","https://mc.yandex.ru/metrika/tag.js","ym");ym(<?= e($metrikaId) ?>,"init",{clickmap:true,trackLinks:true,accurateTrackBounce:true,webvisor:true});</script>
<noscript><div><img src="https://mc.yandex.ru/watch/<?= e($metrikaId) ?>" style="position:absolute;left:-9999px;" alt=""></div></noscript>
<?php endif; ?>
</body>
</html>
<?php
}

// ── Inline Editor (self-contained, zero dependencies) ────────────────

function _render_inline_editor(): void
{
?>
<div id="cms-bar">
  <button id="cms-edit" type="button">Редактировать</button>
  <button id="cms-save" type="button" class="cms-hidden">Сохранить</button>
  <button id="cms-cancel" type="button" class="cms-hidden">Отмена</button>
  <span id="cms-status"></span>
  <div id="cms-right">
    <a href="/admin/">SEO / Панель</a>
    <a href="/admin/?action=logout">Выйти</a>
  </div>
</div>
<div id="cms-link-popup">
  <label>Текст ссылки</label>
  <input type="text" id="cms-link-text">
  <label>URL (адрес)</label>
  <input type="text" id="cms-link-href" placeholder="https://...">
  <div class="popup-btns">
    <button type="button" id="cms-link-ok">Применить</button>
    <button type="button" id="cms-link-del">Удалить ссылку</button>
    <button type="button" id="cms-link-close">Отмена</button>
  </div>
</div>

<style>
/* ── Admin bar ─────────────────────────── */
body.has-cms-bar { padding-top: 48px; }

#cms-bar {
  position: fixed; top: 0; left: 0; right: 0; z-index: 99999;
  height: 48px; background: #1a1a2e; color: #e0e0e0;
  display: flex; align-items: center; gap: 10px; padding: 0 20px;
  font: 14px/1.2 -apple-system, system-ui, 'Segoe UI', Roboto, sans-serif;
  box-shadow: 0 2px 12px rgba(0,0,0,.4);
  user-select: none;
}

#cms-bar button {
  padding: 7px 18px; border: none; border-radius: 6px;
  font: inherit; font-weight: 600; cursor: pointer; transition: all .15s;
}

#cms-edit { background: #e94560; color: #fff; }
#cms-edit:hover { background: #d63851; }

#cms-save { background: #00b894; color: #fff; }
#cms-save:hover { background: #00a381; }

#cms-cancel { background: #636e72; color: #fff; }
#cms-cancel:hover { background: #555e62; }

#cms-save.cms-hidden, #cms-cancel.cms-hidden { display: none; }

#cms-status { font-size: 13px; color: #aaa; }

#cms-right { margin-left: auto; display: flex; gap: 16px; }
#cms-right a { color: #aaa; text-decoration: none; font-size: 13px; transition: color .15s; }
#cms-right a:hover { color: #fff; }

/* ── Edit mode highlights ───────────────── */
body.cms-editing [data-editable] {
  outline: 2px dashed rgba(233, 69, 96, .35);
  outline-offset: 3px;
  cursor: text;
  transition: outline-color .15s, background .15s;
  border-radius: 3px;
}
body.cms-editing [data-editable]:hover {
  outline-color: rgba(233, 69, 96, .7);
  background: rgba(233, 69, 96, .04);
}
body.cms-editing [data-editable]:focus {
  outline: 2px solid #e94560;
  background: rgba(233, 69, 96, .06);
}

/* ── Changed marker ─────────────────────── */
body.cms-editing [data-editable].cms-changed {
  outline-color: #00b894;
}
body.cms-editing [data-editable].cms-changed:focus {
  outline-color: #00a381;
}

/* ── Link editor popup ─────────────────── */
#cms-link-popup {
  display: none; position: fixed; z-index: 100000;
  background: #1a1a2e; border: 1px solid #3a3a5a; border-radius: 10px;
  padding: 14px 16px; box-shadow: 0 8px 30px rgba(0,0,0,.5);
  font: 13px/1.4 -apple-system, system-ui, sans-serif; color: #e0e0e0;
  min-width: 320px;
}
#cms-link-popup label { display: block; font-size: 11px; color: #888; margin-bottom: 3px; margin-top: 8px; }
#cms-link-popup label:first-child { margin-top: 0; }
#cms-link-popup input {
  width: 100%; padding: 7px 10px; background: #16213e; border: 1px solid #333;
  border-radius: 6px; color: #fff; font: inherit; outline: none;
}
#cms-link-popup input:focus { border-color: #e94560; }
#cms-link-popup .popup-btns { display: flex; gap: 8px; margin-top: 10px; }
#cms-link-popup .popup-btns button {
  padding: 6px 14px; border: none; border-radius: 5px; font: inherit;
  font-weight: 600; cursor: pointer; font-size: 12px;
}
#cms-link-ok { background: #00b894; color: #fff; }
#cms-link-ok:hover { background: #00a381; }
#cms-link-del { background: #e94560; color: #fff; }
#cms-link-del:hover { background: #d63851; }
#cms-link-close { background: #636e72; color: #fff; }
#cms-link-close:hover { background: #555e62; }

/* Highlight links in edit mode */
body.cms-editing a { cursor: pointer !important; outline: 1px dashed rgba(9,132,227,.4); outline-offset: 1px; }
body.cms-editing a:hover { outline-color: rgba(9,132,227,.8); background: rgba(9,132,227,.06); }
</style>

<script>
(function() {
  'use strict';

  var editBtn   = document.getElementById('cms-edit');
  var saveBtn   = document.getElementById('cms-save');
  var cancelBtn = document.getElementById('cms-cancel');
  var status    = document.getElementById('cms-status');

  var editing  = false;
  var changed  = {};
  var originals = {};
  var fields;

  function getFields() {
    return document.querySelectorAll('[data-editable]');
  }

  function enterEditMode() {
    editing = true;
    fields  = getFields();
    changed = {};
    originals = {};

    document.body.classList.add('cms-editing');
    editBtn.classList.add('cms-hidden');
    saveBtn.classList.remove('cms-hidden');
    cancelBtn.classList.remove('cms-hidden');

    for (var i = 0; i < fields.length; i++) {
      var el = fields[i];
      var region = el.getAttribute('data-region');
      originals[region] = el.innerHTML;
      el.contentEditable = 'true';
      el.addEventListener('input', onInput);
      el.addEventListener('keydown', onKeydown);
    }

    status.textContent = 'Кликните на текст для редактирования';
  }

  function exitEditMode(revert) {
    editing = false;
    document.body.classList.remove('cms-editing');
    editBtn.classList.remove('cms-hidden');
    saveBtn.classList.add('cms-hidden');
    cancelBtn.classList.add('cms-hidden');

    for (var i = 0; i < fields.length; i++) {
      var el     = fields[i];
      var region = el.getAttribute('data-region');
      el.contentEditable = 'false';
      el.classList.remove('cms-changed');
      el.removeEventListener('input', onInput);
      el.removeEventListener('keydown', onKeydown);
      if (revert && originals[region]) {
        el.innerHTML = originals[region];
      }
    }

    changed = {};
    status.textContent = '';
  }

  function onInput(e) {
    var el     = e.target.closest('[data-editable]');
    if (!el) return;
    var region = el.getAttribute('data-region');
    changed[region] = el.innerHTML;
    el.classList.add('cms-changed');

    var n = Object.keys(changed).length;
    status.textContent = n + ' ' + pluralize(n);
  }

  function onKeydown(e) {
    // Prevent Enter in single-line fields (h1, h2, h3, div.card__title, etc.)
    if (e.key === 'Enter') {
      var tag = e.target.tagName.toLowerCase();
      if (tag !== 'p' && tag !== 'div') {
        e.preventDefault();
      }
    }
  }

  function pluralize(n) {
    if (n === 1) return 'изменение';
    if (n >= 2 && n <= 4) return 'изменения';
    return 'изменений';
  }

  function save() {
    var keys = Object.keys(changed);
    if (keys.length === 0) {
      status.textContent = 'Нет изменений';
      return;
    }

    saveBtn.disabled = true;
    status.textContent = 'Сохранение...';

    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    var payload = JSON.stringify({
      _csrf:   csrfMeta ? csrfMeta.getAttribute('content') : '',
      regions: changed
    });

    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/_inc/api-save.php');
    xhr.setRequestHeader('Content-Type', 'application/json');

    xhr.onload = function() {
      saveBtn.disabled = false;
      if (xhr.status === 200) {
        try {
          var resp = JSON.parse(xhr.responseText);
          if (resp.ok) {
            status.textContent = 'Сохранено (' + resp.changed + ')';
            // Update originals so Cancel won't revert saved changes
            for (var k in changed) {
              originals[k] = changed[k];
            }
            changed = {};
            // Remove changed markers
            for (var i = 0; i < fields.length; i++) {
              fields[i].classList.remove('cms-changed');
            }
          } else {
            status.textContent = 'Ошибка: ' + (resp.errors || []).join('; ');
          }
        } catch(e) {
          status.textContent = 'Ошибка разбора ответа';
        }
      } else if (xhr.status === 403) {
        status.textContent = 'Сессия истекла — перезайдите';
      } else {
        status.textContent = 'Ошибка сервера (' + xhr.status + ')';
      }
    };

    xhr.onerror = function() {
      saveBtn.disabled = false;
      status.textContent = 'Ошибка сети';
    };

    xhr.send(payload);
  }

  // ── Link editor ──
  var linkPopup = document.getElementById('cms-link-popup');
  var linkText  = document.getElementById('cms-link-text');
  var linkHref  = document.getElementById('cms-link-href');
  var currentLink = null;

  function blockLinks(e) {
    if (!editing) return;
    var a = e.target.closest('a');
    if (!a) return;
    // Don't block cms-bar links
    if (a.closest('#cms-bar')) return;
    e.preventDefault();
    e.stopPropagation();
    // Open link editor
    currentLink = a;
    linkText.value = a.textContent;
    linkHref.value = a.getAttribute('href') || '';
    // Position popup near the link
    var rect = a.getBoundingClientRect();
    linkPopup.style.display = 'block';
    linkPopup.style.left = Math.min(rect.left, window.innerWidth - 340) + 'px';
    linkPopup.style.top = (rect.bottom + 8) + 'px';
    // If popup goes off screen bottom, show above
    if (rect.bottom + 200 > window.innerHeight) {
      linkPopup.style.top = (rect.top - linkPopup.offsetHeight - 8) + 'px';
    }
  }

  function closeLinkPopup() {
    linkPopup.style.display = 'none';
    currentLink = null;
  }

  document.getElementById('cms-link-ok').addEventListener('click', function() {
    if (!currentLink) return;
    currentLink.textContent = linkText.value;
    currentLink.setAttribute('href', linkHref.value);
    // Mark parent editable as changed
    var editable = currentLink.closest('[data-editable]');
    if (editable) {
      var region = editable.getAttribute('data-region');
      changed[region] = editable.innerHTML;
      editable.classList.add('cms-changed');
      var n = Object.keys(changed).length;
      status.textContent = n + ' ' + pluralize(n);
    }
    closeLinkPopup();
  });

  document.getElementById('cms-link-del').addEventListener('click', function() {
    if (!currentLink) return;
    var text = document.createTextNode(currentLink.textContent);
    currentLink.parentNode.replaceChild(text, currentLink);
    // Mark parent editable as changed
    var editable = text.parentElement ? text.parentElement.closest('[data-editable]') : null;
    if (editable) {
      var region = editable.getAttribute('data-region');
      changed[region] = editable.innerHTML;
      editable.classList.add('cms-changed');
    }
    closeLinkPopup();
  });

  document.getElementById('cms-link-close').addEventListener('click', closeLinkPopup);

  // Close popup on Escape
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && linkPopup.style.display === 'block') {
      closeLinkPopup();
    }
  });

  // ── Events ──
  editBtn.addEventListener('click', enterEditMode);
  saveBtn.addEventListener('click', save);
  cancelBtn.addEventListener('click', function() { exitEditMode(true); });

  // Block link clicks in edit mode (capture phase to catch before navigation)
  document.addEventListener('click', blockLinks, true);

  // Warn before leaving with unsaved changes
  window.addEventListener('beforeunload', function(e) {
    if (Object.keys(changed).length > 0) {
      e.preventDefault();
      e.returnValue = '';
    }
  });

})();
</script>
<?php
}
