#!/usr/bin/env bash
#
# Установка и обновление сайта на сервере. Запускать от root:
#
#   bash install.sh                      # тестовый домен t.shamanka.online
#   DOMAIN=shamanka.online bash install.sh
#
# Повторный запуск обновляет код и базу, ничего не затирая: .env, база и
# загруженные фото остаются на месте. Другие сайты на сервере не трогаются —
# добавляется только один файл nginx для своего домена.
set -euo pipefail

DOMAIN="${DOMAIN:-t.shamanka.online}"
APP_DIR="${APP_DIR:-/var/www/shamanka}"
REPO="${REPO:-https://github.com/Timophei/smanakaonlie.git}"
BRANCH="${BRANCH:-main}"
PHP_SOCK="${PHP_SOCK:-/run/php/php8.3-fpm.sock}"
WEB_USER="${WEB_USER:-www-data}"
SKIP_NGINX="${SKIP_NGINX:-0}"
# Всё, кроме основного домена, закрываем от поисковиков
NOINDEX="${NOINDEX:-$([ "$DOMAIN" = "shamanka.online" ] && echo 0 || echo 1)}"

say() { printf '\n== %s\n' "$*"; }
die() { printf '\nОШИБКА: %s\n' "$*" >&2; exit 1; }

say "Проверяю, что на сервере есть всё нужное"
command -v php >/dev/null || die "не найден php"
command -v git >/dev/null || die "не найден git: apt install git"
command -v composer >/dev/null || die "не найден composer: apt install composer"
missing=()
for ext in intl mbstring xml curl zip gd pdo_sqlite fileinfo; do
    php -m | grep -qix "$ext" || missing+=("$ext")
done
if [ ${#missing[@]} -gt 0 ]; then
    die "в PHP не хватает расширений: ${missing[*]}. Поставьте, например: apt install php8.3-intl php8.3-sqlite3 php8.3-gd php8.3-zip php8.3-xml php8.3-mbstring php8.3-curl && systemctl restart php8.3-fpm"
fi

say "Код: $APP_DIR (ветка $BRANCH)"
if [ -d "$APP_DIR/.git" ]; then
    git -C "$APP_DIR" fetch --quiet origin "$BRANCH"
    git -C "$APP_DIR" checkout --quiet "$BRANCH"
    git -C "$APP_DIR" reset --quiet --hard "origin/$BRANCH"
elif [ -e "$APP_DIR" ] && [ -n "$(ls -A "$APP_DIR")" ]; then
    die "папка $APP_DIR уже занята чем-то другим. Укажите другую: APP_DIR=/var/www/другая bash install.sh"
else
    git clone --quiet --branch "$BRANCH" "$REPO" "$APP_DIR"
fi
cd "$APP_DIR"

say "Зависимости"
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction --quiet

first_install=0
if [ ! -f .env ]; then
    say "Первая установка: создаю .env и базу"
    first_install=1
    cp .env.example .env
    sed -i \
        -e 's|^APP_ENV=.*|APP_ENV=production|' \
        -e 's|^APP_DEBUG=.*|APP_DEBUG=false|' \
        -e "s|^APP_URL=.*|APP_URL=https://$DOMAIN|" \
        -e 's|^APP_NAME=.*|APP_NAME="Шаманка.online"|' \
        -e 's|^LOG_LEVEL=.*|LOG_LEVEL=warning|' .env
    php artisan key:generate --force --no-interaction
fi
[ -f database/database.sqlite ] || touch database/database.sqlite

say "База данных"
php artisan migrate --force --no-interaction
if [ "$first_install" = 1 ]; then
    php artisan db:seed --force --no-interaction
fi

say "Папка с фото и кэш"
php artisan storage:link --force --no-interaction >/dev/null
php artisan optimize --no-interaction >/dev/null
php artisan filament:optimize --no-interaction >/dev/null 2>&1 || true
chown -R "$WEB_USER":"$WEB_USER" storage bootstrap/cache database

if [ "$SKIP_NGINX" != 1 ]; then
    say "nginx: $DOMAIN"
    [ -S "$PHP_SOCK" ] || die "не найден сокет PHP-FPM $PHP_SOCK. Посмотрите ls /run/php/ и укажите: PHP_SOCK=/run/php/... bash install.sh"
    conf="/etc/nginx/sites-available/$DOMAIN"
    if [ -f "$conf" ]; then
        echo "Файл $conf уже есть — не трогаю (там может быть настроен HTTPS)."
    else
        noindex_line=""
        [ "$NOINDEX" = 1 ] && noindex_line='add_header X-Robots-Tag "noindex, nofollow" always;'
        sed -e "s|__DOMAIN__|$DOMAIN|g" -e "s|__APP_DIR__|$APP_DIR|g" -e "s|__PHP_SOCK__|$PHP_SOCK|g" \
            -e "s|__NOINDEX__|$noindex_line|" deploy/nginx.conf.template > "$conf"
        ln -sf "$conf" "/etc/nginx/sites-enabled/$DOMAIN"
        if ! nginx -t 2>/tmp/shamanka-nginx-test.log; then
            rm -f "/etc/nginx/sites-enabled/$DOMAIN" "$conf"
            cat /tmp/shamanka-nginx-test.log >&2
            die "nginx не принял настройку — я её убрал, остальные сайты не затронуты"
        fi
        systemctl reload nginx
    fi
fi

if [ "$(php artisan tinker --execute 'echo App\Models\User::count();' 2>/dev/null | tail -1)" = "0" ]; then
    if [ -t 0 ]; then
        say "Заведите администратора для входа в /admin"
        php artisan make:filament-user
    else
        say "Администратора пока нет. Создайте: cd $APP_DIR && php artisan make:filament-user"
    fi
fi

say "Готово"
echo "Сайт:    http://$DOMAIN/"
echo "Админка: http://$DOMAIN/admin"
echo "Когда DNS для $DOMAIN начнёт указывать на этот сервер, включите HTTPS:"
echo "  certbot --nginx -d $DOMAIN"
