#!/usr/bin/env bash
#
# Наводит порядок в nginx для основного домена после выпуска сертификата:
#   bash /var/www/shamanka/deploy/fix-main-domain.sh
#
# 1. Убирает блоки shamanka.online из настроек других сайтов (они перехватывали домен).
# 2. Пишет свой файл /etc/nginx/sites-available/shamanka.online с HTTPS.
# 3. Проверяет nginx; при ошибке всё возвращается как было.
set -euo pipefail

DOMAIN="${DOMAIN:-shamanka.online}"
APP_DIR="${APP_DIR:-/var/www/shamanka}"
PHP_SOCK="${PHP_SOCK:-/run/php/php8.3-fpm.sock}"
CERT="${CERT:-$DOMAIN}"
conf="/etc/nginx/sites-available/$DOMAIN"
backup="/root/nginx-backup-$(date +%Y%m%d-%H%M%S)"

[ -f "/etc/letsencrypt/live/$CERT/fullchain.pem" ] || { echo "Нет сертификата /etc/letsencrypt/live/$CERT — сначала certbot certonly --nginx -d $DOMAIN -d www.$DOMAIN"; exit 1; }

echo "== Копия настроек nginx: $backup"
mkdir -p "$backup"
cp -a /etc/nginx/sites-available /etc/nginx/sites-enabled "$backup/"

restore() {
    echo "nginx не принял настройку — возвращаю всё как было"
    rm -rf /etc/nginx/sites-available /etc/nginx/sites-enabled
    cp -a "$backup/sites-available" "$backup/sites-enabled" /etc/nginx/
    nginx -t && systemctl reload nginx
    exit 1
}

echo "== Убираю $DOMAIN из настроек других сайтов"
for f in /etc/nginx/sites-enabled/*; do
    real="$(readlink -f "$f")"
    case "$(basename "$real")" in "$DOMAIN"|"t.$DOMAIN") continue ;; esac
    n="$(python3 "$APP_DIR/deploy/strip-server-blocks.py" "$real" "$DOMAIN")" || restore
    [ "$n" != 0 ] && echo "  $real: убрано блоков — $n"
done

echo "== Пишу $conf"
sed -e "s|__DOMAIN__|$DOMAIN|g" -e "s|__CERT__|$CERT|g" -e "s|__APP_DIR__|$APP_DIR|g" -e "s|__PHP_SOCK__|$PHP_SOCK|g" \
    "$APP_DIR/deploy/nginx.ssl.conf.template" > "$conf"
ln -sf "$conf" "/etc/nginx/sites-enabled/$DOMAIN"

nginx -t || restore
systemctl reload nginx

echo "== Проверка"
curl -sS -o /dev/null -w "https://$DOMAIN/ → %{http_code}\n" --resolve "$DOMAIN:443:127.0.0.1" "https://$DOMAIN/" || true
curl -sS -o /dev/null -w "http://$DOMAIN/ → %{http_code} %{redirect_url}\n" --resolve "$DOMAIN:80:127.0.0.1" "http://$DOMAIN/" || true
echo "Готово. Копия старых настроек — в $backup"
