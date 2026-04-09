<?php
declare(strict_types=1);

/**
 * Sections — renders page sections from JSON data.
 * When admin is logged in, adds data-editable attributes for inline editing.
 */

require_once __DIR__ . '/helpers.php';

/** Current page slug, set by the page file before rendering */
function set_page_slug(string $slug): void
{
    $GLOBALS['_cms_page_slug'] = $slug;
}

function _slug(): string
{
    return $GLOBALS['_cms_page_slug'] ?? 'index';
}

function _is_editing(): bool
{
    return !empty($_SESSION['admin_auth']);
}

/** Helper: returns data-editable attribute if admin is logged in */
function _ed(int $si, string $field): string
{
    if (!_is_editing()) return '';
    return ' data-editable data-region="' . e(_slug() . ':' . $si . ':' . $field) . '"';
}

/** Section index counter */
function _si(bool $reset = false): int
{
    static $i = -1;
    if ($reset) { $i = -1; }
    return ++$i;
}

// ── Dispatcher ───────────────────────────────────────────────────────

function render_section(array $section): void
{
    $type = $section['type'] ?? '';
    $fn   = 'render_' . $type;
    $si   = _si();

    if (function_exists($fn)) {
        $fn($section['data'] ?? [], $si);
    }
}

function reset_section_counter(): void
{
    _si(true);
}

// ── 1. Hero ──────────────────────────────────────────────────────────

function render_hero(array $data, int $si = 0): void
{
    $tag      = $data['tag'] ?? '';
    $h1       = t($data['h1'] ?? '');
    $subtitle = t($data['subtitle'] ?? '');
    ?>
<section class="hero">
  <div class="container hero__content">
<?php if ($tag): ?>
    <div class="hero__tag"<?= _ed($si, 'tag') ?>><?= e(t($tag)) ?></div>
<?php endif; ?>
    <h1<?= _ed($si, 'h1') ?>><?= $h1 ?></h1>
    <p class="hero__sub"<?= _ed($si, 'subtitle') ?>><?= e($subtitle) ?></p>
  </div>
</section>
<?php
}

// ── 2. Notice banner ─────────────────────────────────────────────────

function render_notice_banner(array $data, int $si = 0): void
{
    $heading = t($data['heading'] ?? '');
    $text    = t($data['text'] ?? '');
    $buttons = $data['buttons'] ?? [];
    ?>
<section class="feature-banner">
  <div class="container" style="position:relative;">
    <h2<?= _ed($si, 'heading') ?>><?= e($heading) ?></h2>
    <p<?= _ed($si, 'text') ?>><?= e($text) ?></p>
<?php foreach ($buttons as $btn): ?>
    <a href="<?= e(t($btn['href'] ?? '#')) ?>" class="btn btn--<?= e($btn['style'] ?? 'primary') ?>"><?= e(t($btn['label'] ?? '')) ?></a>
<?php endforeach; ?>
  </div>
</section>
<?php
}

// ── 3. Card grid ─────────────────────────────────────────────────────

function render_card_grid(array $data, int $si = 0): void
{
    $heading  = t($data['heading'] ?? '');
    $subtitle = t($data['subtitle'] ?? '');
    $columns  = (int) ($data['columns'] ?? 3);
    $altBg    = !empty($data['alt_bg']);
    $cards    = $data['cards'] ?? [];

    $sectionClass = 'section' . ($altBg ? ' section--alt' : '');
    ?>
<section class="<?= $sectionClass ?>">
  <div class="container">
<?php if ($heading): ?>
    <h2 class="section-title"<?= _ed($si, 'heading') ?>><?= e($heading) ?></h2>
<?php endif; ?>
<?php if ($subtitle): ?>
    <p class="section-subtitle"<?= _ed($si, 'subtitle') ?>><?= e($subtitle) ?></p>
<?php endif; ?>
    <div class="grid grid--<?= $columns ?>">
<?php foreach ($cards as $ci => $card): ?>
      <div class="card">
<?php if (!empty($card['badge'])): ?>
        <div class="card__badge"><?= e(t($card['badge'])) ?></div>
<?php endif; ?>
<?php if (!empty($card['image'])): ?>
        <div class="card__image-placeholder">
          <img src="<?= e($card['image']) ?>" alt="<?= e(t($card['image_alt'] ?? '')) ?>" loading="lazy">
        </div>
<?php endif; ?>
        <div class="card__title"<?= _ed($si, "cards.$ci.title") ?>><?= e(t($card['title'] ?? '')) ?></div>
<?php if (!empty($card['desc'])): ?>
        <div class="card__desc"<?= _ed($si, "cards.$ci.desc") ?>><?= e(t($card['desc'])) ?></div>
<?php endif; ?>
<?php if (isset($card['price'])): ?>
        <div class="card__price"><?= e((string) $card['price']) ?><?php if (!empty($card['price_unit'])): ?> <small><?= e($card['price_unit']) ?></small><?php endif; ?></div>
<?php endif; ?>
<?php if (!empty($card['specs'])): ?>
        <p><?php foreach ($card['specs'] as $spec): ?><?= e(t($spec)) ?><br><?php endforeach; ?></p>
<?php endif; ?>
<?php if (!empty($card['link'])): ?>
        <div class="card__actions">
          <a href="<?= e(t($card['link']['href'] ?? '#')) ?>" class="card__link-parent">
            <strong><?= e(t($card['link']['title'] ?? '')) ?></strong>
<?php if (!empty($card['link']['subtitle'])): ?>
            <span><?= e(t($card['link']['subtitle'])) ?></span>
<?php endif; ?>
            <span class="card__link-arrow">→</span>
          </a>
        </div>
<?php endif; ?>
      </div>
<?php endforeach; ?>
    </div>
  </div>
</section>
<?php
}

// ── 4. Table ─────────────────────────────────────────────────────────

function render_table(array $data, int $si = 0): void
{
    $heading  = t($data['heading'] ?? '');
    $subtitle = t($data['subtitle'] ?? '');
    $columns  = $data['columns'] ?? [];
    $rows     = $data['rows'] ?? [];
    ?>
<section class="section">
  <div class="container">
<?php if ($heading): ?>
    <h2 class="section-title"<?= _ed($si, 'heading') ?>><?= e($heading) ?></h2>
<?php endif; ?>
<?php if ($subtitle): ?>
    <p class="section-subtitle"<?= _ed($si, 'subtitle') ?>><?= e($subtitle) ?></p>
<?php endif; ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
<?php foreach ($columns as $col): ?>
            <th><?= e(t($col)) ?></th>
<?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
<?php foreach ($rows as $row): ?>
          <tr<?php if (!empty($row['highlight'])): ?> style="background:var(--accent-glow)"<?php endif; ?>>
<?php foreach ($row['cells'] ?? [] as $cell): ?>
            <td><?= e(t((string) $cell)) ?></td>
<?php endforeach; ?>
          </tr>
<?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>
<?php
}

// ── 5. FAQ ───────────────────────────────────────────────────────────

function render_faq(array $data, int $si = 0): void
{
    $heading = t($data['heading'] ?? '');
    $items   = $data['items'] ?? [];

    $faqSchema = [
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => [],
    ];
    foreach ($items as $item) {
        $faqSchema['mainEntity'][] = [
            '@type'          => 'Question',
            'name'           => t($item['q'] ?? ''),
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text'  => t($item['a'] ?? ''),
            ],
        ];
    }
    ?>
<section class="section">
  <div class="container">
<?php if ($heading): ?>
    <h2 class="section-title"<?= _ed($si, 'heading') ?>><?= e($heading) ?></h2>
<?php endif; ?>
    <script type="application/ld+json"><?= json_encode($faqSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
    <div class="faq">
<?php foreach ($items as $fi => $item): ?>
    <details>
      <summary<?= _ed($si, "items.$fi.q") ?>><?= e(t($item['q'] ?? '')) ?></summary>
      <div class="faq__answer">
        <p<?= _ed($si, "items.$fi.a") ?>><?= e(t($item['a'] ?? '')) ?></p>
      </div>
    </details>
<?php endforeach; ?>
    </div>
  </div>
</section>
<?php
}

// ── 6. Advantages ────────────────────────────────────────────────────

function render_advantages(array $data, int $si = 0): void
{
    $heading = t($data['heading'] ?? '');
    $items   = $data['items'] ?? [];
    ?>
<section class="section">
  <div class="container">
<?php if ($heading): ?>
    <h2 class="section-title"<?= _ed($si, 'heading') ?>><?= e($heading) ?></h2>
<?php endif; ?>
    <div class="grid grid--<?= count($items) <= 3 ? 3 : 4 ?>">
<?php foreach ($items as $ai => $item): ?>
      <div class="adv">
        <div class="adv__icon"><?= e($item['icon'] ?? '') ?></div>
        <h3<?= _ed($si, "items.$ai.title") ?>><?= e(t($item['title'] ?? '')) ?></h3>
        <p<?= _ed($si, "items.$ai.desc") ?>><?= e(t($item['desc'] ?? '')) ?></p>
      </div>
<?php endforeach; ?>
    </div>
  </div>
</section>
<?php
}

// ── 7. Text block ────────────────────────────────────────────────────

function render_text_block(array $data, int $si = 0): void
{
    $heading    = t($data['heading'] ?? '');
    $altBg      = !empty($data['alt_bg']);
    $paragraphs = $data['paragraphs'] ?? [];
    $link       = $data['link'] ?? null;

    $sectionClass = 'section' . ($altBg ? ' section--alt' : '');
    ?>
<section class="<?= $sectionClass ?>">
  <div class="container">
<?php if ($heading): ?>
    <h2 class="section-title"<?= _ed($si, 'heading') ?>><?= e($heading) ?></h2>
<?php endif; ?>
<?php foreach ($paragraphs as $pi => $p): ?>
    <p<?= _ed($si, "paragraphs.$pi") ?>><?= e(t($p)) ?></p>
<?php endforeach; ?>
<?php if ($link): ?>
    <p style="margin-top:1rem;">
      <a href="<?= e(t($link['href'] ?? '#')) ?>" style="color:var(--accent);"><?= e(t($link['text'] ?? '')) ?></a>
    </p>
<?php endif; ?>
  </div>
</section>
<?php
}

// ── 8. CTA ───────────────────────────────────────────────────────────

function render_cta(array $data, int $si = 0): void
{
    $heading = t($data['heading'] ?? '');
    $text    = t($data['text'] ?? '');
    $label   = t($data['button_label'] ?? '');
    $href    = t($data['button_href'] ?? '#');
    ?>
<section class="cta">
  <div class="container" style="position:relative;">
    <h2<?= _ed($si, 'heading') ?>><?= e($heading) ?></h2>
    <p<?= _ed($si, 'text') ?>><?= e($text) ?></p>
    <a href="<?= e($href) ?>" class="btn btn--primary"><?= e($label) ?></a>
  </div>
</section>
<?php
}

// ── 9. Breadcrumbs ───────────────────────────────────────────────────

function render_breadcrumbs(array $data, int $si = 0): void
{
    $items = $data['items'] ?? [];
    if (empty($items)) return;
    ?>
<nav class="breadcrumbs" aria-label="Хлебные крошки">
  <div class="container">
<?php foreach ($items as $i => $item): ?>
<?php if ($i > 0): ?> <span class="breadcrumbs__sep">/</span> <?php endif; ?>
<?php if (!empty($item['href']) && $i < count($items) - 1): ?>
    <a href="<?= e($item['href']) ?>"><?= e(t($item['label'] ?? '')) ?></a>
<?php else: ?>
    <span><?= e(t($item['label'] ?? '')) ?></span>
<?php endif; ?>
<?php endforeach; ?>
  </div>
</nav>
<?php
}

// ── 10. Contact grid ─────────────────────────────────────────────────

function render_contact_grid(array $data, int $si = 0): void
{
    $columns = $data['columns'] ?? [];
    $c       = city();
    ?>
<section class="section">
  <div class="container">
    <div class="grid grid--2">
<?php foreach ($columns as $col): ?>
      <div>
<?php foreach ($col['cards'] ?? [] as $card): ?>
<?php
        $type = $card['type'] ?? '';
        switch ($type) {
            case 'phones':        _render_contact_phones($c, $card); break;
            case 'email':         _render_contact_email($c, $card); break;
            case 'delivery':      _render_contact_delivery($c, $card); break;
            case 'notice':        _render_contact_notice($c, $card); break;
            case 'business_info': _render_contact_business_info($c, $card); break;
            case 'parent_link':   _render_contact_parent_link($c, $card); break;
        }
?>
<?php endforeach; ?>
      </div>
<?php endforeach; ?>
    </div>
  </div>
</section>
<?php
}

function _render_contact_phones(array $c, array $card): void
{
    $heading = $card['heading'] ?? 'Телефоны';
    ?>
<div class="card" style="padding:1.5rem;margin-bottom:1rem;">
  <div class="card__title"><?= e(t($heading)) ?></div>
  <p style="font-size:1.1rem;font-weight:600;"><a href="tel:<?= e($c['phone_raw'] ?? '') ?>"><?= e($c['phone'] ?? '') ?></a></p>
  <p><a href="tel:<?= e(str_replace([' ', '(', ')', '-'], '', $c['phone_secondary'] ?? '')) ?>"><?= e($c['phone_secondary'] ?? '') ?></a></p>
  <p style="margin-top:.5rem;color:var(--text-light);font-size:.85rem;"><?= e($c['work_hours'] ?? '') ?></p>
</div>
<?php
}

function _render_contact_email(array $c, array $card): void
{
    $heading = $card['heading'] ?? 'Email';
    ?>
<div class="card" style="padding:1.5rem;margin-bottom:1rem;">
  <div class="card__title"><?= e(t($heading)) ?></div>
  <p><a href="mailto:<?= e($c['email'] ?? '') ?>"><?= e($c['email'] ?? '') ?></a></p>
</div>
<?php
}

function _render_contact_delivery(array $c, array $card): void
{
    $heading = $card['heading'] ?? 'Доставка';
    ?>
<div class="card" style="padding:1.5rem;margin-bottom:1rem;">
  <div class="card__title"><?= e(t($heading)) ?></div>
  <p><?= e($c['delivery_note'] ?? '') ?></p>
</div>
<?php
}

function _render_contact_notice(array $c, array $card): void
{
    $heading = $card['heading'] ?? 'Обратите внимание';
    ?>
<div class="card" style="padding:1.5rem;margin-bottom:1rem;border-left:4px solid var(--accent);">
  <div class="card__title"><?= e(t($heading)) ?></div>
  <p><?= e($c['office_notice'] ?? '') ?></p>
</div>
<?php
}

function _render_contact_business_info(array $c, array $card): void
{
    $heading = $card['heading'] ?? 'Как мы работаем';
    ?>
<div class="card card--business-info" style="padding:0;margin-bottom:1rem;border-radius:12px;overflow:hidden;border:1px solid #e0e7ef;">

  <div style="padding:1.5rem 1.5rem 1rem;background:linear-gradient(135deg,#f0f7ff 0%,#e8f4fd 100%);">
    <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.5rem;">
      <span style="font-size:1.5rem;">🏢</span>
      <div class="card__title" style="margin:0;"><?= e(t($heading)) ?></div>
    </div>
    <p style="margin:0;color:var(--text-light);font-size:.92rem;line-height:1.5;">Локальный офис в городе — пережиток прошлого. Мы работаем современно и прозрачно.</p>
  </div>

  <div style="padding:1.25rem 1.5rem;">

    <div style="display:flex;gap:1rem;align-items:flex-start;margin-bottom:1.25rem;padding-bottom:1.25rem;border-bottom:1px solid #f0f0f0;">
      <span style="font-size:1.3rem;flex-shrink:0;margin-top:2px;">📝</span>
      <div>
        <p style="margin:0 0 .25rem;font-weight:600;font-size:.95rem;">Договоры и документы — по ЭДО</p>
        <p style="margin:0;font-size:.88rem;line-height:1.5;color:var(--text-light);">Заключение договоров, подписание накладных и актов — через электронный документооборот. Быстро, юридически значимо, без поездок.</p>
      </div>
    </div>

    <div style="display:flex;gap:1rem;align-items:flex-start;margin-bottom:1.25rem;padding-bottom:1.25rem;border-bottom:1px solid #f0f0f0;">
      <span style="font-size:1.3rem;flex-shrink:0;margin-top:2px;">✉️</span>
      <div>
        <p style="margin:0 0 .25rem;font-weight:600;font-size:.95rem;">Заказы — только письменно</p>
        <p style="margin:0;font-size:.88rem;line-height:1.5;color:var(--text-light);">Принимаем заказы по электронной почте — так точнее и без недопонимания. Напишите размеры и количество на <a href="mailto:<?= e($c['email'] ?? '') ?>" style="color:var(--accent);font-weight:500;"><?= e($c['email'] ?? '') ?></a></p>
      </div>
    </div>

    <div style="display:flex;gap:1rem;align-items:flex-start;margin-bottom:1.25rem;padding-bottom:1.25rem;border-bottom:1px solid #f0f0f0;">
      <span style="font-size:1.3rem;flex-shrink:0;margin-top:2px;">📷</span>
      <div>
        <p style="margin:0 0 .25rem;font-weight:600;font-size:.95rem;">Фото — на сайте, образцы — в мастерской</p>
        <p style="margin:0;font-size:.88rem;line-height:1.5;color:var(--text-light);">Все виды стекла с фото есть в <a href="/steklo/" style="color:var(--accent);font-weight:500;">каталоге</a>. Образцы можно посмотреть и потрогать в мастерской в&nbsp;г.&nbsp;Электросталь.</p>
      </div>
    </div>

    <div style="display:flex;gap:1rem;align-items:flex-start;">
      <span style="font-size:1.3rem;flex-shrink:0;margin-top:2px;">🏭</span>
      <div>
        <p style="margin:0 0 .25rem;font-weight:600;font-size:.95rem;">Где получить товар</p>
        <p style="margin:0;font-size:.88rem;line-height:1.5;color:var(--text-light);">Закалённое, обработанное стекло, триплекс и стеклопакеты — на производстве в&nbsp;г.&nbsp;Москве.<br>Прозрачное стекло 4&nbsp;мм, армированное, узорчатое и зеркала — в&nbsp;г.&nbsp;Электросталь.</p>
      </div>
    </div>

  </div>

  <div style="padding:1rem 1.5rem;background:#f8faf8;border-top:1px solid #e0e7ef;">
    <div style="display:flex;flex-wrap:wrap;gap:.5rem .75rem;font-size:.85rem;color:#2d8a56;">
      <span>✓ Работаем с <?= (int)($c['company_since'] ?? 1998) ?> года</span>
      <span>✓ ЭДО для юрлиц</span>
      <span>✓ Доставка в <?= e($c['city_accusative'] ?? $c['city_name'] ?? '') ?></span>
    </div>
  </div>

</div>
<?php
}

function _render_contact_parent_link(array $c, array $card): void
{
    $heading = $card['heading'] ?? 'Полный каталог и цены';
    ?>
<div class="card" style="padding:1.5rem;margin-bottom:1rem;">
  <div class="card__title"><?= e(t($heading)) ?></div>
  <p><a href="<?= e($c['parent_site_url'] ?? '#') ?>" class="btn btn--outline-primary" style="margin-top:.5rem;"><?= e($c['parent_site'] ?? '') ?> →</a></p>
</div>
<?php
}

// ── 11. Sound calculator ─────────────────────────────────────────────

function render_sound_calculator(array $data, int $si = 0): void
{
    $soundData = json_load(base_path() . '/_data/sound-data.json');
    $noiseSources = $soundData['noise_sources'] ?? [];
    ?>
<section class="section">
  <div class="container">
    <div class="sc" id="sound-calc">

      <div class="sc__step">
        <h3 class="sc__label"><span class="sc__label-num">1</span> Источник шума снаружи</h3>
        <div class="sc__noise-grid" id="noise-grid">
<?php foreach ($noiseSources as $i => $s): ?>
          <button class="sc__noise-btn<?= $i === 7 ? ' active' : '' ?>" data-db="<?= (int)$s['db'] ?>" type="button">
            <span class="sc__noise-db"><?= (int)$s['db'] ?> дБ</span>
            <span class="sc__noise-name"><?= e($s['name'] ?? '') ?></span>
          </button>
<?php endforeach; ?>
        </div>
        <div class="sc__custom-noise">
          <label>Или укажите свой уровень: <input type="range" id="noise-slider" min="20" max="130" value="80" step="1"> <strong id="noise-val">80 дБ</strong></label>
        </div>
      </div>

      <div class="sc__step">
        <h3 class="sc__label"><span class="sc__label-num">2</span> Через что проходит звук?</h3>
        <div class="sc__tabs">
          <button class="sc__tab active" data-tab="walls" type="button">Стены</button>
          <button class="sc__tab" data-tab="glass_single" type="button">Одинарное стекло</button>
          <button class="sc__tab" data-tab="glazing_units" type="button">Стеклопакеты</button>
        </div>
        <div class="sc__materials" id="materials-list"></div>
        <div class="sc__compare" id="compare-area" style="display:none;">
          <h3 class="sc__label" style="margin-top:1rem;">Сравнение (до 3-х)</h3>
          <div id="compare-slots" class="sc__compare-slots"></div>
        </div>
      </div>

      <div class="sc__step">
        <h3 class="sc__label"><span class="sc__label-num">3</span> Результат</h3>
        <div class="sc__result" id="result-area">
          <p class="sc__result-hint">Выберите материал для расчёта</p>
        </div>
      </div>

      <div class="sc__step">
        <h3 class="sc__label">Шкала громкости</h3>
        <div class="sc__scale" id="db-scale"></div>
        <div class="sc__norms">
          <span class="sc__norm sc__norm--night">Норма ночь: ≤30 дБ</span>
          <span class="sc__norm sc__norm--day">Норма день: ≤40 дБ</span>
        </div>
      </div>

    </div>

    <script>
    (function(){
      var soundData = <?= json_encode($soundData['materials'] ?? [], JSON_UNESCAPED_UNICODE) ?>;
      var noiseDB = 80, activeTab = 'walls', selected = [];

      var noiseGrid = document.getElementById('noise-grid');
      var noiseSlider = document.getElementById('noise-slider');
      var noiseVal = document.getElementById('noise-val');
      var materialsList = document.getElementById('materials-list');
      var resultArea = document.getElementById('result-area');
      var compareArea = document.getElementById('compare-area');
      var compareSlots = document.getElementById('compare-slots');
      var dbScale = document.getElementById('db-scale');

      noiseGrid.addEventListener('click', function(e) {
        var btn = e.target.closest('.sc__noise-btn');
        if (!btn) return;
        var all = noiseGrid.querySelectorAll('.sc__noise-btn');
        for (var i = 0; i < all.length; i++) all[i].classList.remove('active');
        btn.classList.add('active');
        noiseDB = parseInt(btn.getAttribute('data-db'));
        noiseSlider.value = noiseDB;
        noiseVal.textContent = noiseDB + ' дБ';
        updateResults();
      });

      noiseSlider.addEventListener('input', function() {
        noiseDB = parseInt(noiseSlider.value);
        noiseVal.textContent = noiseDB + ' дБ';
        var all = noiseGrid.querySelectorAll('.sc__noise-btn');
        for (var i = 0; i < all.length; i++) {
          all[i].classList.toggle('active', parseInt(all[i].getAttribute('data-db')) === noiseDB);
        }
        updateResults();
      });

      var tabs = document.querySelectorAll('.sc__tab');
      for (var t = 0; t < tabs.length; t++) {
        tabs[t].addEventListener('click', function() {
          for (var j = 0; j < tabs.length; j++) tabs[j].classList.remove('active');
          this.classList.add('active');
          activeTab = this.getAttribute('data-tab');
          selected = [];
          renderMaterials();
          updateResults();
        });
      }

      function renderMaterials() {
        var items = soundData[activeTab] || [];
        var html = '';
        for (var i = 0; i < items.length; i++) {
          var m = items[i];
          var isSel = false;
          for (var s = 0; s < selected.length; s++) { if (selected[s].id === m.id) isSel = true; }
          html += '<div class="sc__mat' + (isSel ? ' selected' : '') + '" data-id="' + m.id + '">' +
            '<span class="sc__mat-name">' + m.name + '</span>' +
            '<span class="sc__mat-thick">' + m.thickness + ' мм</span>' +
            '<span class="sc__mat-rw">Rw ' + m.rw + ' дБ</span></div>';
        }
        materialsList.innerHTML = html;

        var els = materialsList.querySelectorAll('.sc__mat');
        for (var e = 0; e < els.length; e++) {
          els[e].addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            var itms = soundData[activeTab] || [];
            var item = null;
            for (var k = 0; k < itms.length; k++) { if (itms[k].id === id) item = itms[k]; }
            var idx = -1;
            for (var k = 0; k < selected.length; k++) { if (selected[k].id === id) idx = k; }
            if (idx >= 0) { selected.splice(idx, 1); }
            else { if (selected.length >= 3) selected.shift(); selected.push(item); }
            renderMaterials();
            updateResults();
          });
        }
      }

      function getVerdict(db) {
        if (db <= 25) return { text: 'Почти не слышно', color: '#27ae60', bg: '#e8f5e9' };
        if (db <= 30) return { text: 'Тихо, комфортно ночью', color: '#2e7d32', bg: '#e8f5e9' };
        if (db <= 40) return { text: 'Слышно, но норма днём', color: '#e65100', bg: '#fff3e0' };
        if (db <= 50) return { text: 'Шумно, мешает отдыху', color: '#e74c3c', bg: '#fde8e8' };
        if (db <= 60) return { text: 'Очень шумно!', color: '#c0392b', bg: '#fde8e8' };
        return { text: 'Невыносимо!', color: '#8e44ad', bg: '#f3e5f5' };
      }

      function getExample(db) {
        if (db <= 15) return 'тише шелеста листвы';
        if (db <= 25) return 'как шёпот';
        if (db <= 35) return 'как тиканье часов';
        if (db <= 45) return 'как тихий офис';
        if (db <= 55) return 'как обычный разговор';
        if (db <= 65) return 'как работающий телевизор';
        if (db <= 75) return 'как пылесос за стеной';
        if (db <= 85) return 'как крик рядом';
        return 'как стройка под окном';
      }

      function updateResults() {
        if (selected.length === 0) {
          resultArea.innerHTML = '<p class="sc__result-hint">Выберите материал для расчёта (можно до 3-х для сравнения)</p>';
          compareArea.style.display = 'none';
          renderScale([]);
          return;
        }

        if (selected.length === 1) {
          var m = selected[0];
          var res = Math.max(0, noiseDB - m.rw);
          var v = getVerdict(res);
          resultArea.innerHTML =
            '<div><strong>' + m.name + '</strong> (Rw = ' + m.rw + ' дБ, толщина ' + m.thickness + ' мм)</div>' +
            '<div>Шум снаружи: <strong>' + noiseDB + ' дБ</strong> → после прохождения:</div>' +
            '<div class="sc__result-main" style="color:' + v.color + '">' + res + ' дБ</div>' +
            '<div class="sc__result-desc">' + v.text + ' — ' + getExample(res) + '</div>' +
            (res > 30 ? '<p style="margin-top:.75rem;font-size:.9rem;">Хотите тише? Рассмотрите стеклопакеты с триплексом — до 45 дБ звукоизоляции. <a href="/steklopakety/">Подробнее</a></p>' : '');
          compareArea.style.display = 'none';
          renderScale([{ name: m.name, db: res }]);
        } else {
          resultArea.innerHTML = '<p style="font-size:.95rem;color:var(--text-light);">Сравнение выбранных материалов ниже</p>';
          compareArea.style.display = 'block';
          var markers = [];
          var h = '';
          for (var i = 0; i < selected.length; i++) {
            var m = selected[i];
            var res = Math.max(0, noiseDB - m.rw);
            var v = getVerdict(res);
            var pct = Math.min(100, (m.rw / 65) * 100);
            markers.push({ name: m.name, db: res });
            h += '<div class="sc__slot"><div class="sc__slot-name">' + m.name + '</div>' +
              '<div class="sc__slot-rw">' + m.thickness + ' мм, Rw = ' + m.rw + ' дБ</div>' +
              '<div class="sc__slot-result" style="color:' + v.color + '">' + res + ' дБ</div>' +
              '<div class="sc__slot-verdict" style="background:' + v.bg + ';color:' + v.color + '">' + v.text + '</div>' +
              '<div class="sc__slot-bar" style="width:' + pct + '%;background:' + v.color + ';"></div></div>';
          }
          compareSlots.innerHTML = h;
          renderScale(markers);
        }
      }

      function renderScale(markers) {
        var maxDB = 130;
        var nPct = (noiseDB / maxDB) * 100;
        var html = '<div class="sc__scale-marker" style="left:' + nPct + '%;background:#e74c3c;"></div>' +
          '<div class="sc__scale-label" style="left:' + nPct + '%;color:#e74c3c;">Снаружи ' + noiseDB + ' дБ</div>';
        var colors = ['#1a5276', '#27ae60', '#8e44ad'];
        for (var i = 0; i < markers.length; i++) {
          var pct = (markers[i].db / maxDB) * 100;
          html += '<div class="sc__scale-marker" style="left:' + pct + '%;background:' + colors[i] + ';"></div>' +
            '<div class="sc__scale-label" style="left:' + pct + '%;color:' + colors[i] + ';top:' + (i % 2 === 0 ? 46 : 58) + 'px;">' + markers[i].db + ' дБ</div>';
        }
        var nightPct = (30 / maxDB) * 100;
        var dayPct = (40 / maxDB) * 100;
        html += '<div style="position:absolute;left:' + nightPct + '%;top:0;width:2px;height:40px;background:#27ae60;opacity:.5;"></div>';
        html += '<div style="position:absolute;left:' + dayPct + '%;top:0;width:2px;height:40px;background:#e67e22;opacity:.5;"></div>';
        dbScale.innerHTML = html;
      }

      renderMaterials();
      updateResults();
    })();
    </script>
  </div>
</section>
<?php
}

// ── 12. Custom HTML ──────────────────────────────────────────────────

function render_custom_html(array $data, int $si = 0): void
{
    echo $data['html'] ?? '';
}

// ── 13. Article body ────────────────────────────────────────────────

function render_article_body(array $data, int $si = 0): void
{
    $h1     = t($data['h1'] ?? '');
    $intro  = t($data['intro'] ?? '');
    $blocks = $data['blocks'] ?? [];
    $cta    = $data['cta'] ?? null;
    ?>
<article class="article">
  <div class="container">
    <h1 class="article__title"<?= _ed($si, 'h1') ?>><?= e($h1) ?></h1>
<?php if ($intro): ?>
    <p class="article__intro"<?= _ed($si, 'intro') ?>><?= e($intro) ?></p>
<?php endif; ?>
    <div class="article__content">
<?php foreach ($blocks as $bi => $block):
    $btype = $block['type'] ?? '';
    switch ($btype):
        case 'heading':
            $lvl = max(2, min(4, (int)($block['level'] ?? 2))); ?>
      <h<?= $lvl ?><?= _ed($si, "blocks.$bi.text") ?>><?= e(t($block['text'] ?? '')) ?></h<?= $lvl ?>>
<?php       break;
        case 'paragraph': ?>
      <p<?= _ed($si, "blocks.$bi.text") ?>><?= nl2br(e(t($block['text'] ?? ''))) ?></p>
<?php       break;
        case 'list':
            $tag = !empty($block['ordered']) ? 'ol' : 'ul'; ?>
      <<?= $tag ?>>
<?php       foreach ($block['items'] ?? [] as $li => $item): ?>
        <li<?= _ed($si, "blocks.$bi.items.$li") ?>><?= e(t($item)) ?></li>
<?php       endforeach; ?>
      </<?= $tag ?>>
<?php       break;
        case 'image': ?>
      <figure class="article__figure">
        <img src="<?= e($block['src'] ?? '') ?>" alt="<?= e(t($block['alt'] ?? '')) ?>" loading="lazy">
<?php       if (!empty($block['caption'])): ?>
        <figcaption><?= e(t($block['caption'])) ?></figcaption>
<?php       endif; ?>
      </figure>
<?php       break;
        case 'table': ?>
      <div class="table-wrap">
        <table>
          <thead><tr>
<?php       foreach ($block['columns'] ?? [] as $col): ?>
            <th><?= e(t($col)) ?></th>
<?php       endforeach; ?>
          </tr></thead>
          <tbody>
<?php       foreach ($block['rows'] ?? [] as $row): ?>
            <tr>
<?php         foreach ($row as $cell): ?>
              <td><?= e(t((string)$cell)) ?></td>
<?php         endforeach; ?>
            </tr>
<?php       endforeach; ?>
          </tbody>
        </table>
      </div>
<?php       break;
    endswitch;
endforeach; ?>
    </div>
<?php if ($cta): ?>
    <div class="article__cta">
      <h2><?= e(t($cta['heading'] ?? '')) ?></h2>
      <p><?= e(t($cta['text'] ?? '')) ?></p>
      <a href="<?= e(t($cta['button_href'] ?? '#')) ?>" class="btn btn--primary"><?= e(t($cta['button_label'] ?? '')) ?></a>
    </div>
<?php endif; ?>
  </div>
</article>
<?php
}
