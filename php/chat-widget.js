/* Свой чат-виджет Стеклотрейд/Дисконт-Стекло. Self-hosted, без зависимостей.
   Встраивается одним тегом: <script src="/chat-widget.js" defer></script>
   Общается с /chat.php (same-origin). Настройка (необязательно): window.STEKLO_CHAT = {name,greeting,accent,title}. */
(function () {
  if (window.__stekloChat) return; window.__stekloChat = true;
  var C = {
    name: 'Консультант',
    title: 'Онлайн-консультант',
    greeting: 'Здравствуйте! Помогу подобрать стекло, посчитать цену и подсказать по заказу. Что вас интересует?',
    accent: '#c1551f',
    endpoint: '/chat.php',
    placeholder: 'Напишите сообщение…'
  };
  if (window.STEKLO_CHAT) for (var k in window.STEKLO_CHAT) C[k] = window.STEKLO_CHAT[k];

  var SID = null;
  try { SID = localStorage.getItem('steklo_sid'); if (!SID) { SID = 's' + Date.now().toString(36) + Math.random().toString(36).slice(2, 8); localStorage.setItem('steklo_sid', SID); } } catch (e) { SID = 's' + Date.now().toString(36); }

  var css = '\
.sc-btn{position:fixed;right:20px;bottom:20px;width:60px;height:60px;border-radius:50%;background:var(--sc-a);color:#fff;border:none;cursor:pointer;box-shadow:0 6px 20px rgba(0,0,0,.25);z-index:2147483000;display:flex;align-items:center;justify-content:center;transition:transform .15s}\
.sc-btn:hover{transform:scale(1.06)}.sc-btn svg{width:28px;height:28px}\
.sc-badge{position:absolute;top:-3px;right:-3px;min-width:18px;height:18px;background:#e33;color:#fff;border-radius:9px;font:700 11px/18px Arial;text-align:center;padding:0 4px;display:none}\
.sc-panel{position:fixed;right:20px;bottom:92px;width:360px;max-width:calc(100vw - 32px);height:540px;max-height:calc(100vh - 120px);background:#fff;border-radius:16px;box-shadow:0 12px 40px rgba(0,0,0,.28);z-index:2147483000;display:none;flex-direction:column;overflow:hidden;font-family:-apple-system,Segoe UI,Roboto,Arial,sans-serif}\
.sc-open .sc-panel{display:flex}\
.sc-hd{background:var(--sc-a);color:#fff;padding:14px 16px;display:flex;align-items:center;gap:10px}\
.sc-hd .sc-av{width:34px;height:34px;border-radius:50%;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;flex:0 0 auto}\
.sc-hd .sc-t{font-weight:700;font-size:15px;line-height:1.1}.sc-hd .sc-s{font-size:12px;opacity:.85;margin-top:2px}\
.sc-x{margin-left:auto;background:none;border:none;color:#fff;cursor:pointer;font-size:22px;line-height:1;opacity:.85;padding:2px 6px}.sc-x:hover{opacity:1}\
.sc-body{flex:1;overflow-y:auto;padding:14px;background:#f6f8fa;display:flex;flex-direction:column;gap:10px}\
.sc-m{max-width:82%;padding:9px 12px;border-radius:14px;font-size:14px;line-height:1.45;white-space:pre-wrap;word-wrap:break-word}\
.sc-bot{align-self:flex-start;background:#fff;color:#1a2530;border:1px solid #e6ebef;border-bottom-left-radius:4px}\
.sc-me{align-self:flex-end;background:var(--sc-a);color:#fff;border-bottom-right-radius:4px}\
.sc-typ{align-self:flex-start;background:#fff;border:1px solid #e6ebef;border-radius:14px;padding:11px 14px;display:flex;gap:4px}\
.sc-typ i{width:7px;height:7px;border-radius:50%;background:#9aa6b2;animation:scb 1s infinite}.sc-typ i:nth-child(2){animation-delay:.2s}.sc-typ i:nth-child(3){animation-delay:.4s}\
@keyframes scb{0%,60%,100%{opacity:.3}30%{opacity:1}}\
.sc-ft{border-top:1px solid #e6ebef;padding:10px;display:flex;gap:8px;align-items:flex-end;background:#fff}\
.sc-ft textarea{flex:1;border:1px solid #d5dde3;border-radius:10px;padding:9px 11px;font:14px/1.4 inherit;resize:none;max-height:96px;outline:none}\
.sc-ft textarea:focus{border-color:var(--sc-a)}\
.sc-send{background:var(--sc-a);color:#fff;border:none;border-radius:10px;width:40px;height:40px;cursor:pointer;flex:0 0 auto;display:flex;align-items:center;justify-content:center}\
.sc-send:disabled{opacity:.5;cursor:default}.sc-send svg{width:20px;height:20px}\
.sc-note{font-size:11px;color:#9aa6b2;text-align:center;padding:4px 10px 8px}\
@media(max-width:480px){.sc-panel{right:0;bottom:0;width:100vw;max-width:100vw;height:100vh;max-height:100vh;border-radius:0}.sc-btn{right:16px;bottom:16px}}';

  var st = document.createElement('style'); st.textContent = ':root{--sc-a:' + C.accent + '}' + css; document.head.appendChild(st);

  var root = document.createElement('div');
  root.innerHTML =
    '<button class="sc-btn" aria-label="Открыть чат"><span class="sc-badge">1</span>' +
    '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 3C6.5 3 2 6.8 2 11.5c0 2.4 1.2 4.6 3.1 6.1L4 21l4-1.6c1.2.4 2.6.6 4 .6 5.5 0 10-3.8 10-8.5S17.5 3 12 3z"/></svg></button>' +
    '<div class="sc-panel" role="dialog" aria-label="Чат с консультантом">' +
      '<div class="sc-hd"><span class="sc-av"><svg viewBox="0 0 24 24" width="20" height="20" fill="#fff"><path d="M12 3C6.5 3 2 6.8 2 11.5c0 2.4 1.2 4.6 3.1 6.1L4 21l4-1.6c1.2.4 2.6.6 4 .6 5.5 0 10-3.8 10-8.5S17.5 3 12 3z"/></svg></span>' +
        '<div><div class="sc-t">' + esc(C.name) + '</div><div class="sc-s">' + esc(C.title) + '</div></div>' +
        '<button class="sc-x" aria-label="Закрыть">×</button></div>' +
      '<div class="sc-body"></div>' +
      '<div class="sc-ft"><textarea rows="1" placeholder="' + esc(C.placeholder) + '"></textarea>' +
        '<button class="sc-send" aria-label="Отправить"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 20.5v-6l8-2.5-8-2.5v-6l19 8.5-19 8.5z"/></svg></button></div>' +
      '<div class="sc-note">Работает на нашем ассистенте • ответы ориентировочные</div>' +
    '</div>';
  document.body.appendChild(root);

  var btn = root.querySelector('.sc-btn'), panel = root.querySelector('.sc-panel'), body = root.querySelector('.sc-body'),
      ta = root.querySelector('textarea'), send = root.querySelector('.sc-send'), badge = root.querySelector('.sc-badge'),
      greeted = false, busy = false;

  function esc(s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }
  function add(role, text) { var d = document.createElement('div'); d.className = 'sc-m ' + (role === 'me' ? 'sc-me' : 'sc-bot'); d.textContent = text; body.appendChild(d); body.scrollTop = body.scrollHeight; return d; }
  function typing(on) { var t = body.querySelector('.sc-typ'); if (on && !t) { t = document.createElement('div'); t.className = 'sc-typ'; t.innerHTML = '<i></i><i></i><i></i>'; body.appendChild(t); body.scrollTop = body.scrollHeight; } if (!on && t) t.remove(); }

  function open() { root.classList.add('sc-open'); badge.style.display = 'none'; if (!greeted) { greeted = true; add('bot', C.greeting); } setTimeout(function () { ta.focus(); }, 50); }
  function close() { root.classList.remove('sc-open'); }
  btn.onclick = function () { root.classList.contains('sc-open') ? close() : open(); };
  root.querySelector('.sc-x').onclick = close;

  ta.addEventListener('input', function () { ta.style.height = 'auto'; ta.style.height = Math.min(ta.scrollHeight, 96) + 'px'; });
  ta.addEventListener('keydown', function (e) { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); submit(); } });
  send.onclick = submit;

  function submit() {
    var msg = ta.value.trim(); if (!msg || busy) return;
    ta.value = ''; ta.style.height = 'auto'; add('me', msg); busy = true; send.disabled = true; typing(true);
    fetch(C.endpoint, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ sid: SID, message: msg }) })
      .then(function (r) { return r.json().catch(function () { return {}; }); })
      .then(function (d) { typing(false); add('bot', (d && d.reply) ? d.reply : 'Извините, не удалось ответить. Позвоните нам или напишите на почту.'); })
      .catch(function () { typing(false); add('bot', 'Нет связи с чатом. Проверьте интернет или позвоните нам.'); })
      .then(function () { busy = false; send.disabled = false; ta.focus(); });
  }
})();
