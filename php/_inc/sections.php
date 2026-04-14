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
    // Auto-render feature CTA block right after Hero on every page
    render_feature_cta_block();
}


/**
 * Renders a prominent two-button CTA block: unique feature + glass calculator.
 * Configured via city.json (feature_cta + calculator_cta).
 * Called automatically after Hero section.
 */
function render_feature_cta_block(): void
{
    static $rendered = false;
    if ($rendered) return; // Only once per page
    $rendered = true;

    $c = city();
    $feat = $c['feature_cta'] ?? null;
    $calc = $c['calculator_cta'] ?? null;
    if (!$feat || !$calc) return;

    $featTitle = t($feat['title'] ?? '');
    $featDesc  = t($feat['desc'] ?? '');
    $featHref  = e(t($feat['href'] ?? '#'));
    $calcTitle = t($calc['title'] ?? '');
    $calcDesc  = t($calc['desc'] ?? '');
    $calcHref  = e(t($calc['href'] ?? '#'));
    ?>
<section class="section feature-cta">
  <div class="container">
    <div class="feature-cta__grid">
      <a href="<?= $featHref ?>" class="feature-cta__card feature-cta__card--primary">
        <div class="feature-cta__icon">★</div>
        <div class="feature-cta__content">
          <div class="feature-cta__title"><?= e($featTitle) ?></div>
          <div class="feature-cta__desc"><?= e($featDesc) ?></div>
          <div class="feature-cta__arrow">Открыть →</div>
        </div>
      </a>
      <a href="<?= $calcHref ?>" class="feature-cta__card feature-cta__card--accent" target="_blank" rel="noopener">
        <div class="feature-cta__icon">₽</div>
        <div class="feature-cta__content">
          <div class="feature-cta__title"><?= e($calcTitle) ?></div>
          <div class="feature-cta__desc"><?= e($calcDesc) ?></div>
          <div class="feature-cta__arrow">Рассчитать →</div>
        </div>
      </a>
    </div>
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
            case 'phones':      _render_contact_phones($c, $card); break;
            case 'email':       _render_contact_email($c, $card); break;
            case 'delivery':    _render_contact_delivery($c, $card); break;
            case 'notice':      _render_contact_notice($c, $card); break;
            case 'no_office':   _render_contact_no_office($c, $card); break;
            case 'parent_link': _render_contact_parent_link($c, $card); break;
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

function _render_contact_no_office(array $c, array $card): void
{
    $heading = $card['heading'] ?? 'Офис в городе';
    $city    = $c['city_name'] ?? '';
    $address = $c['address_old'] ?? '';
    $pickupCity = $c['pickup_city'] ?? '';
    $cityPrep = $c['city_prepositional'] ?? $city;
    // If this city IS the pickup city, skip the second pickup line to avoid confusion
    $pickupSameAsCity = $pickupCity && mb_stripos($pickupCity, $city) !== false;
    ?>
<div class="card card--office-info" style="padding:1.5rem;margin-bottom:1rem;border-left:4px solid var(--accent);background:linear-gradient(135deg,#f0f7ff 0%,#fff 100%);">
  <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;">
    <span style="font-size:1.5rem;">📍</span>
    <div class="card__title" style="margin:0;"><?= e(t($heading)) ?></div>
  </div>
<?php if ($address): ?>
  <p style="margin-bottom:.75rem;line-height:1.6;font-weight:600;"><?= e($address) ?></p>
<?php endif; ?>
  <p style="margin-bottom:.35rem;line-height:1.6;">Офис в&nbsp;<?= e($cityPrep) ?> производит только онлайн-консультации по подбору стекла. Выдача товара по указанному адресу не&nbsp;производится.</p>
  <p style="margin-bottom:.75rem;line-height:1.6;font-style:italic;color:var(--text-light);font-size:.93rem;">Приносить сюда дверь для подрезки не&nbsp;нужно&nbsp;— все заказы принимаем онлайн.</p>
  <div style="margin-bottom:1rem;padding:.75rem 1rem;background:#f8f9fb;border-radius:8px;font-size:.9rem;line-height:1.5;">
    <p style="margin:0 0 .35rem;font-weight:600;">🏭 Производство и выдача готовых изделий:</p>
    <p style="margin:0;color:var(--text-light);">• <strong>Москва</strong> — закалённое стекло, триплекс, стеклопакеты</p>
<?php if ($pickupCity && !$pickupSameAsCity): ?>
    <p style="margin:0;color:var(--text-light);">• <strong><?= e($pickupCity) ?></strong> — прозрачное 4&nbsp;мм, армированное, узорчатое, зеркала</p>
<?php elseif ($pickupSameAsCity): ?>
    <p style="margin:0;color:var(--text-light);">• <strong><?= e($pickupCity) ?></strong> (наш цех) — прозрачное 4&nbsp;мм, армированное, узорчатое, зеркала</p>
<?php endif; ?>
  </div>
  <div style="display:flex;flex-wrap:wrap;gap:.5rem .75rem;margin-bottom:1rem;font-size:.9rem;color:var(--text-light);">
    <span>✓ Расчёт бесплатно</span>
    <span>✓ Доставка от 1 дня</span>
    <span>✓ Заказы — по email</span>
  </div>
  <a href="/dostavka/" class="btn btn--primary" style="display:inline-block;">Подробнее о доставке →</a>
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

// ── 12. Shower glass configurator ───────────────────────────────────

function render_shower_calculator(array $data, int $si = 0): void
{
    $sd = json_load(base_path() . '/_data/shower-data.json');
    $c = city();
    ?>
<section class="section">
  <div class="container">
    <div class="sc" id="shower-calc">

      <div class="sc__notice">
        <strong><?= e($c['company_name'] ?? '') ?></strong> — производитель закалённого стекла для душевых. Режем по вашим размерам, закаливаем, сверлим отверстия под фурнитуру. <strong>У вас полная свобода в выборе крепежа и монтажной бригады</strong> — мы не навязываем дополнительных товаров и услуг.
      </div>

      <div class="sc__step">
        <h3>1. Тип душевого ограждения</h3>
        <p class="sc__hint">Выберите конфигурацию — мы подскажем количество и размеры панелей.</p>
        <div class="sc__types" id="sc-types">
<?php foreach ($sd['enclosure_types'] as $i => $t): ?>
          <button class="sc__type-btn<?= ($i === 0 ? ' sc__type-btn--active' : '') ?>" data-idx="<?= $i ?>">
            <strong><?= e($t['name']) ?></strong>
            <span class="sc__type-desc"><?= e($t['desc']) ?></span>
            <span class="sc__type-panels"><?= $t['panels'] ?> <?= $t['panels'] === 1 ? 'панель' : 'панели' ?></span>
          </button>
<?php endforeach; ?>
        </div>
      </div>

      <div class="sc__step">
        <h3>2. Размеры</h3>
        <p class="sc__hint">Введите размеры проёма или панелей в сантиметрах.</p>
        <div class="sc__inputs" id="sc-inputs"></div>
        <p class="sc__type-note" id="sc-type-note" style="display:none;"></p>
      </div>

      <div class="sc__step">
        <h3>3. Тип стекла</h3>
        <p class="sc__hint">Всё стекло — закалённое (ГОСТ 30698-2014). Резка, закалка и полировка кромок включены в цену.</p>
        <div class="sc__glass-cards" id="sc-glass">
<?php foreach ($sd['glass_types'] as $i => $g): ?>
          <button class="sc__glass-btn<?= (!empty($g['popular']) ? ' sc__glass-btn--active' : '') ?>" data-idx="<?= $i ?>">
            <span class="sc__glass-name"><?= e($g['name']) ?></span>
<?php if (!empty($g['popular'])): ?>
            <span class="sc__glass-badge">Стандарт</span>
<?php endif; ?>
            <span class="sc__glass-price"><?= number_format($g['price_m2'], 0, '', ' ') ?> р/м²</span>
            <span class="sc__glass-desc"><?= e($g['desc'] ?? '') ?></span>
          </button>
<?php endforeach; ?>
        </div>
      </div>

      <div class="sc__step">
        <h3>4. Отверстия и вырезы</h3>
        <p class="sc__hint">Отверстия под петли, ручки, коннекторы. Делаются ДО закалки — после невозможно.</p>
        <div class="sc__row">
          <div class="sc__field">
            <label>Отверстия, шт.</label>
            <input type="number" id="sc-holes" value="0" min="0" max="20" step="1">
            <span class="sc__field-hint"><?= $sd['processing']['hole_price'] ?> р/шт.</span>
          </div>
          <div class="sc__field">
            <label>Вырезы, шт.</label>
            <input type="number" id="sc-cutouts" value="0" min="0" max="10" step="1">
            <span class="sc__field-hint"><?= $sd['processing']['cutout_price'] ?> р/шт.</span>
          </div>
        </div>
      </div>

      <div style="text-align:center;margin-top:1.5rem;">
        <button class="sc__calc-btn" id="sc-calculate">Рассчитать</button>
      </div>

      <div class="sc__result" id="sc-result" style="display:none;">
        <h3>Результат расчёта</h3>
        <div class="sc__summary" id="sc-summary"></div>
        <div class="sc__panels-table" id="sc-panels"></div>
        <div class="sc__price-block" id="sc-price"></div>
        <div class="sc__warnings" id="sc-warnings"></div>
        <div class="sc__prod-note">
          <strong>Мы изготовим</strong> закалённое стекло по вашим размерам за 5–7 рабочих дней. Полировка кромок включена. Доставка по <?= e($c['city_dative'] ?? $c['city_name']) ?> и всей Московской области.
        </div>
      </div>

      <div class="sc__ref">
        <p>Стекло для душевых ограждений — только закалённое (<?= e(implode(', ', $sd['gosts'])) ?>). После закалки стекло нельзя резать или сверлить.</p>
      </div>
    </div>

    <style>
    .sc { max-width: 860px; margin: 0 auto; }
    .sc__notice { padding: .75rem 1rem; background: #eef7ff; border: 1px solid #b3d9ff; border-radius: 8px; font-size: .9rem; margin-bottom: 2rem; line-height: 1.5; }
    .sc__step { margin-bottom: 2rem; }
    .sc__step h3 { font-size: 1.15rem; color: var(--primary, #1a1a2e); margin-bottom: .5rem; }
    .sc__hint { font-size: .85rem; color: var(--text-muted, #888); margin-bottom: .75rem; }
    .sc__types { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: .5rem; }
    .sc__type-btn { padding: .75rem 1rem; border: 2px solid var(--border, #e0e0e0); border-radius: 8px; background: #fff; cursor: pointer; text-align: left; transition: all .2s; }
    .sc__type-btn:hover { border-color: var(--accent, #27ae60); }
    .sc__type-btn--active { border-color: var(--accent, #27ae60); background: rgba(39,174,96,.08); }
    .sc__type-desc { display: block; font-size: .8rem; color: var(--text-muted, #888); margin-top: .2rem; }
    .sc__type-panels { display: inline-block; margin-top: .3rem; font-size: .75rem; background: var(--bg-card, #f0f0f0); padding: .1rem .4rem; border-radius: 4px; }
    .sc__type-note { margin-top: .5rem; padding: .5rem .75rem; background: rgba(39,174,96,.06); border-radius: 6px; font-size: .85rem; color: var(--text-light, #555); }
    .sc__inputs { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 1rem; }
    .sc__row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
    .sc__field label { display: block; font-size: .85rem; color: var(--primary, #1a1a2e); margin-bottom: .3rem; font-weight: 500; }
    .sc__field input { width: 100%; padding: .6rem; border: 2px solid var(--border, #e0e0e0); border-radius: 6px; font-size: 1rem; box-sizing: border-box; }
    .sc__field input:focus { border-color: var(--accent, #27ae60); outline: none; }
    .sc__field-hint { display: block; font-size: .75rem; color: var(--text-muted, #999); margin-top: .25rem; }
    .sc__glass-cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: .5rem; }
    .sc__glass-btn { padding: .6rem .8rem; border: 2px solid var(--border, #e0e0e0); border-radius: 8px; background: #fff; cursor: pointer; text-align: left; transition: all .2s; position: relative; }
    .sc__glass-btn:hover { border-color: var(--accent, #27ae60); }
    .sc__glass-btn--active { border-color: var(--accent, #27ae60); background: rgba(39,174,96,.08); }
    .sc__glass-name { display: block; font-weight: 600; font-size: .9rem; }
    .sc__glass-badge { display: inline-block; background: var(--accent, #27ae60); color: #fff; font-size: .65rem; font-weight: 700; padding: .1rem .4rem; border-radius: 3px; margin-top: .2rem; }
    .sc__glass-price { display: block; font-size: .85rem; color: var(--accent, #e94560); font-weight: 600; margin-top: .2rem; }
    .sc__glass-desc { display: block; font-size: .75rem; color: var(--text-muted, #888); margin-top: .15rem; }
    .sc__calc-btn { padding: .75rem 2.5rem; background: var(--accent, #27ae60); color: #fff; border: none; border-radius: 8px; font-size: 1.1rem; font-weight: 600; cursor: pointer; transition: background .2s; }
    .sc__calc-btn:hover { background: #219a52; }
    .sc__result { margin-top: 2rem; }
    .sc__result h3 { font-size: 1.15rem; margin-bottom: 1rem; }
    .sc__summary { padding: 1rem; background: var(--bg-card, #f8f9fa); border-radius: 8px; margin-bottom: 1rem; border-left: 4px solid var(--accent, #27ae60); }
    .sc__summary p { margin: .25rem 0; font-size: .95rem; }
    .sc__panels-table { margin-bottom: 1rem; }
    .sc__panels-table table { width: 100%; border-collapse: collapse; font-size: .9rem; }
    .sc__panels-table th { background: var(--primary, #1a1a2e); color: #fff; padding: .5rem; text-align: left; }
    .sc__panels-table td { padding: .5rem; border-bottom: 1px solid var(--border, #e0e0e0); }
    .sc__price-block { padding: 1rem; background: #f0faf4; border: 1px solid #b2dfdb; border-radius: 8px; margin-bottom: 1rem; }
    .sc__price-line { display: flex; justify-content: space-between; font-size: .95rem; margin: .2rem 0; }
    .sc__price-total { font-size: 1.2rem; font-weight: 700; color: var(--accent, #e94560); border-top: 2px solid var(--border, #e0e0e0); padding-top: .5rem; margin-top: .5rem; }
    .sc__warnings { margin-bottom: 1rem; }
    .sc__warning { padding: .4rem .7rem; background: #fffbe6; border-radius: 6px; font-size: .85rem; margin-bottom: .3rem; }
    .sc__prod-note { padding: 1rem; background: #f0faf4; border: 1px solid #b2dfdb; border-radius: 8px; font-size: .9rem; line-height: 1.5; }
    .sc__ref { margin-top: 1.5rem; font-size: .8rem; color: var(--text-muted, #999); }
    @media (max-width: 600px) {
      .sc__types { grid-template-columns: 1fr; }
      .sc__glass-cards { grid-template-columns: 1fr 1fr; }
      .sc__row { grid-template-columns: 1fr; }
      .sc__inputs { grid-template-columns: 1fr; }
    }
    </style>

    <script>
    (function() {
      var types = <?= json_encode($sd['enclosure_types'], JSON_UNESCAPED_UNICODE) ?>;
      var glasses = <?= json_encode($sd['glass_types'], JSON_UNESCAPED_UNICODE) ?>;
      var processing = <?= json_encode($sd['processing'], JSON_UNESCAPED_UNICODE) ?>;
      var thicknessRules = <?= json_encode($sd['thickness_rules'], JSON_UNESCAPED_UNICODE) ?>;

      var selType = 0;
      var selGlass = (function() { for (var i = 0; i < glasses.length; i++) { if (glasses[i].popular) return i; } return 1; })();

      function setupBtns(containerId, activeClass, cb) {
        var el = document.getElementById(containerId);
        if (!el) return;
        el.addEventListener('click', function(e) {
          var btn = e.target.closest('button');
          if (!btn || !btn.hasAttribute('data-idx')) return;
          var all = el.querySelectorAll('button');
          for (var i = 0; i < all.length; i++) all[i].classList.remove(activeClass);
          btn.classList.add(activeClass);
          cb(parseInt(btn.getAttribute('data-idx')));
        });
      }

      function renderInputs() {
        var t = types[selType];
        var container = document.getElementById('sc-inputs');
        var html = '';
        for (var i = 0; i < t.inputs.length; i++) {
          var inp = t.inputs[i];
          html += '<div class="sc__field"><label>' + inp.label + '</label>';
          html += '<input type="number" id="sc-inp-' + inp.id + '" value="' + inp['default'] + '" min="' + inp.min + '" max="' + inp.max + '" step="1">';
          html += '</div>';
        }
        container.innerHTML = html;

        var noteEl = document.getElementById('sc-type-note');
        if (t.note) {
          noteEl.textContent = t.note;
          noteEl.style.display = 'block';
        } else {
          noteEl.style.display = 'none';
        }
      }

      setupBtns('sc-types', 'sc__type-btn--active', function(i) { selType = i; renderInputs(); });
      setupBtns('sc-glass', 'sc__glass-btn--active', function(i) { selGlass = i; });
      renderInputs();

      document.getElementById('sc-calculate').addEventListener('click', calculate);

      function getVal(id, fallback) {
        var el = document.getElementById('sc-inp-' + id);
        return el ? (parseFloat(el.value) || fallback) : fallback;
      }

      function calcPanels() {
        var t = types[selType];
        var h, panels = [];
        switch (t.id) {
          case 'walkin':
            panels.push({name: 'Перегородка', w: getVal('width', 90), h: getVal('height', 200)});
            break;
          case 'corner_square':
            h = getVal('height', 200);
            var sz = getVal('size', 90);
            panels.push({name: 'Боковая панель', w: sz, h: h});
            panels.push({name: 'Дверная панель', w: sz, h: h});
            break;
          case 'corner_rect':
            h = getVal('height', 200);
            panels.push({name: 'Панель 1', w: getVal('width1', 80), h: h});
            panels.push({name: 'Панель 2', w: getVal('width2', 100), h: h});
            break;
          case 'frontal_2':
            h = getVal('height', 200);
            var op = getVal('opening', 100);
            var dw = getVal('door_width', 70);
            panels.push({name: 'Дверь', w: dw, h: h});
            panels.push({name: 'Неподвижная секция', w: Math.max(op - dw, 25), h: h});
            break;
          case 'frontal_3':
            h = getVal('height', 200);
            var op3 = getVal('opening', 150);
            var dw3 = getVal('door_width', 70);
            var side = Math.round((op3 - dw3) / 2);
            panels.push({name: 'Левая секция', w: side, h: h});
            panels.push({name: 'Дверь', w: dw3, h: h});
            panels.push({name: 'Правая секция', w: side, h: h});
            break;
          case 'bathtub':
            panels.push({name: 'Шторка', w: getVal('width', 80), h: getVal('height', 140)});
            break;
        }
        return panels;
      }

      function recommendThickness(area) {
        for (var i = 0; i < thicknessRules.length; i++) {
          if (area <= thicknessRules[i].max_area) return thicknessRules[i].recommended;
        }
        return 10;
      }

      function calculate() {
        var t = types[selType];
        var g = glasses[selGlass];
        var panels = calcPanels();
        var totalArea = 0;
        for (var i = 0; i < panels.length; i++) {
          panels[i].area = (panels[i].w * panels[i].h) / 10000;
          totalArea += panels[i].area;
        }

        var maxPanelArea = 0;
        for (var j = 0; j < panels.length; j++) {
          if (panels[j].area > maxPanelArea) maxPanelArea = panels[j].area;
        }
        var recThickness = recommendThickness(maxPanelArea);
        if (t.wide_threshold) {
          for (var k = 0; k < panels.length; k++) {
            if (panels[k].w > t.wide_threshold) recThickness = Math.max(recThickness, t.wide_thickness || 10);
          }
        }
        if (t.default_thickness === 6 && g.thickness >= 6) recThickness = 6;

        var holes = parseInt(document.getElementById('sc-holes').value) || 0;
        var cutouts = parseInt(document.getElementById('sc-cutouts').value) || 0;

        var glassPrice = Math.round(totalArea * g.price_m2);
        var holesPrice = holes * processing.hole_price;
        var cutoutsPrice = cutouts * processing.cutout_price;
        var totalPrice = glassPrice + holesPrice + cutoutsPrice;

        // Summary
        var summaryHtml = '<p><strong>Тип:</strong> ' + t.name + '</p>' +
          '<p><strong>Панелей:</strong> ' + panels.length + ' шт.</p>' +
          '<p><strong>Общая площадь:</strong> ' + totalArea.toFixed(2) + ' м&sup2;</p>' +
          '<p><strong>Стекло:</strong> ' + g.name + '</p>';
        if (g.thickness < recThickness) {
          summaryHtml += '<p style="color:#e67e22;"><strong>Рекомендация:</strong> для ваших размеров лучше взять ' + recThickness + ' мм</p>';
        }
        document.getElementById('sc-summary').innerHTML = summaryHtml;

        // Panels table
        var tableHtml = '<table><thead><tr><th>Панель</th><th>Ширина</th><th>Высота</th><th>Площадь</th></tr></thead><tbody>';
        for (var p = 0; p < panels.length; p++) {
          tableHtml += '<tr><td>' + panels[p].name + '</td><td>' + panels[p].w + ' см</td><td>' + panels[p].h + ' см</td><td>' + panels[p].area.toFixed(2) + ' м&sup2;</td></tr>';
        }
        tableHtml += '</tbody></table>';
        document.getElementById('sc-panels').innerHTML = tableHtml;

        // Price
        var priceHtml = '<div class="sc__price-line"><span>Стекло (' + totalArea.toFixed(2) + ' м&sup2; &times; ' + g.price_m2.toLocaleString('ru-RU') + ' р)</span><span>' + glassPrice.toLocaleString('ru-RU') + ' р</span></div>';
        if (holesPrice > 0) priceHtml += '<div class="sc__price-line"><span>Отверстия (' + holes + ' шт. &times; ' + processing.hole_price + ' р)</span><span>' + holesPrice.toLocaleString('ru-RU') + ' р</span></div>';
        if (cutoutsPrice > 0) priceHtml += '<div class="sc__price-line"><span>Вырезы (' + cutouts + ' шт. &times; ' + processing.cutout_price + ' р)</span><span>' + cutoutsPrice.toLocaleString('ru-RU') + ' р</span></div>';
        priceHtml += '<div class="sc__price-line">включено: резка, закалка, полировка кромок</div>';
        priceHtml += '<div class="sc__price-line sc__price-total"><span>Итого (ориентировочно)</span><span>' + totalPrice.toLocaleString('ru-RU') + ' р</span></div>';
        document.getElementById('sc-price').innerHTML = priceHtml;

        // Warnings
        var warnsHtml = '<div class="sc__warning">Все отверстия и вырезы делаются ДО закалки. Укажите точные позиции при заказе.</div>';
        warnsHtml += '<div class="sc__warning">Рекомендуем: сначала купите фурнитуру, замерьте позиции крепления, потом заказывайте стекло.</div>';
        warnsHtml += '<div class="sc__warning">Крепёж и фурнитуру (петли, ручки, коннекторы) вы выбираете самостоятельно — полная свобода выбора.</div>';
        document.getElementById('sc-warnings').innerHTML = warnsHtml;

        document.getElementById('sc-result').style.display = 'block';
        document.getElementById('sc-result').scrollIntoView({behavior: 'smooth', block: 'start'});
      }
    })();
    </script>
  </div>
</section>
<?php
}

// ── 13. Custom HTML ──────────────────────────────────────────────────

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
