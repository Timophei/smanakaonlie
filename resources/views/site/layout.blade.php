<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Шаманка — отдых, маршруты и красивые места у Иркута')</title>
    <meta name="description" content="@yield('description', 'Путеводитель по Шаманке Иркутской области: тропа на утёс, Лес чудес, места для пикника, транспорт, жильё, бани и местные услуги.')">
    <link rel="canonical" href="{{ config('app.canonical_root') }}{{ request()->path() === '/' ? '/' : '/'.request()->path().'/' }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Onest:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="/css/site.css?v={{ filemtime(public_path('css/site.css')) }}">
</head>
<body>
@include('site.partials.icons')
<div class="wrap">
    @hasSection('header')
        @yield('header')
    @else
        <header class="header">
            <a class="brand" href="/">
                <img src="/img/logo.jpg" alt="" width="48" height="48">
                Открой Шаманку
            </a>
            @include('site.partials.nav')
        </header>
    @endif

    @yield('content')

    <footer class="footer">
        <div><strong>Шаманка.online</strong>Путеводитель по берегам Иркута</div>
        <nav aria-label="Меню в подвале">
            <a href="/#routes">Маршруты</a>
            <a href="/stati/transport/">Как добраться</a>
            <a href="/#services">Услуги</a>
            <a href="/#contact">Связаться</a>
        </nav>
        <div>Берегите природу. Уносите мусор с собой.</div>
    </footer>
</div>
<script src="/js/site.js?v={{ filemtime(public_path('js/site.js')) }}" defer></script>
</body>
</html>
