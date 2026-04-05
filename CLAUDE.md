# Satellite CMS — Орехово-Зуево

Flat-file PHP CMS. Продакшен: orehovo-zuevo.steklotrade.com (бренд Стеклотрейд)

## Деплой
Push в `main` → GitHub Actions → проверки → FTP на Timeweb.
Локальная проверка: `bash scripts/check.sh`
Локальный просмотр: `docker run --rm -p 8000:80 -v "$(pwd)/php:/var/www/html" php:7.4-apache`

## PHP 7.4 ОГРАНИЧЕНИЕ
Продакшен на PHP 7.4. ЗАПРЕЩЕНО: match(), именованные аргументы, union types,
str_contains(), str_starts_with(), str_ends_with(), enum, readonly, fibers.

## Структура
- `php/` — весь сайт (деплоится на сервер)
- `php/_inc/` — ядро CMS (helpers.php, layout.php, sections.php)
- `php/_data/` — JSON-контент страниц
- `php/admin/` — админка
