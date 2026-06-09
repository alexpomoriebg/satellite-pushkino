#!/bin/bash
# Локальная проверка PHP-кода перед деплоем
# Запуск: bash scripts/check.sh

RED='\033[0;31m'
GREEN='\033[0;32m'
NC='\033[0m'
FAILED=0

echo "=== PHP синтаксис (PHP 7.4) ==="

# Pull image quietly
docker pull php:7.4-cli -q > /dev/null 2>&1 || true

PHPDIR="$(cd php && pwd -W 2>/dev/null || pwd)"
RESULT=$(MSYS_NO_PATHCONV=1 docker run --rm -v "${PHPDIR}:/app" -w /app php:7.4-cli \
  sh -c 'FOUND=0; for f in $(find . -name "*.php"); do php -l "$f" 2>&1 || FOUND=1; done; exit $FOUND' 2>&1) || true

SYNTAX_ERRORS=$(echo "$RESULT" | grep -v "No syntax errors" | grep -v "^$" || true)

if [ -n "$SYNTAX_ERRORS" ]; then
  echo -e "${RED}ОШИБКИ:${NC}"
  echo "$SYNTAX_ERRORS"
  FAILED=1
else
  echo -e "${GREEN}Синтаксис OK${NC}"
fi

echo ""
echo "=== Опасные файлы ==="
DANGEROUS=$(git diff --cached --name-only 2>/dev/null | grep -iE '\.(env|sql|pem|key)$|debug\.log' || true)
DANGEROUS2=$(git diff --name-only 2>/dev/null | grep -iE '\.(env|sql|pem|key)$|debug\.log' || true)

if [ -n "$DANGEROUS$DANGEROUS2" ]; then
  echo -e "${RED}ОБНАРУЖЕНЫ ОПАСНЫЕ ФАЙЛЫ:${NC}"
  echo "$DANGEROUS$DANGEROUS2"
  FAILED=1
else
  echo -e "${GREEN}Опасных файлов нет${NC}"
fi

echo ""
if [ $FAILED -eq 0 ]; then
  echo -e "${GREEN}✓ Все проверки пройдены${NC}"
else
  echo -e "${RED}✗ Проверки провалились${NC}"
  exit 1
fi
