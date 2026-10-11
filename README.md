# Шаманка.online

Сайт-путеводитель по Шаманке на берегу Иркута: маршруты, жильё, бани, продукты, река.
Laravel + админка Filament.

## Что уже есть

- Админка по адресу `/admin`: гостиницы, хозяева, справочники удобств и меток.
- Модель данных: хозяин → объекты → варианты размещения (домики турбазы, номера).
- Учёт нажатий на телефон и мессенджеры (таблица `contact_clicks`) — пока только хранение.
- Картинки хранятся файлами в `storage/app/public/places/…`, в базе лежат только пути.

- Сайт: главная `/`, список жилья `/uslugi/homes`, страница объекта по адресу из админки.
- Кнопка «Показать телефон» и ссылки в мессенджеры считают нажатия.
- Стили в `public/css/site.css`, скрипт в `public/js/site.js`, картинки оформления в `public/img/`.

Вошедший в админку видит на сайте и черновики, гости — только опубликованное.

## Запуск

```sh
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed      # справочники и четыре объекта жилья с текущего сайта
php artisan storage:link        # открыть папку с картинками для сайта
php artisan make:filament-user  # завести администратора
php artisan serve
```

По умолчанию база — SQLite в `database/database.sqlite`. Для MySQL или PostgreSQL поменяйте `DB_*` в `.env`.

## Установка на сервер

На сервере от root:

```sh
curl -fsSL https://raw.githubusercontent.com/Timophei/smanakaonlie/main/deploy/install.sh -o /root/shamanka-install.sh
bash /root/shamanka-install.sh
```

Скрипт ставит сайт в `/var/www/shamanka` для домена `t.shamanka.online` и добавляет для него отдельный файл nginx. Повторный запуск обновляет сайт; `.env`, база и загруженные фото сохраняются. Тестовый домен закрыт от поисковиков. Для основного домена: `DOMAIN=shamanka.online bash /root/shamanka-install.sh`.
