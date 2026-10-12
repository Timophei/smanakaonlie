<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script>document.documentElement.classList.add('js')</script>
    <title>@yield('title', 'Шаманка — отдых, маршруты и красивые места у Иркута')</title>
    <meta name="description" content="@yield('description', 'Путеводитель по Шаманке Иркутской области: тропа на утёс, Лес чудес, места для пикника, транспорт, жильё, бани и местные услуги.')">
    <link rel="icon" type="image/png" href="{{ $theme?->iconUrl() ?? '/img/logos/autumn-icon.png' }}">
    <link rel="canonical" href="{{ config('app.canonical_root') }}{{ request()->path() === '/' ? '/' : '/'.request()->path().'/' }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700&display=swap">
    <link rel="stylesheet" href="/css/site.css?v={{ filemtime(public_path('css/site.css')) }}">
    @if ($theme)
        <style>:root { {{ $theme->cssVariables() }} }</style>
    @endif
</head>
<body data-theme="{{ $theme?->slug }}">
@include('site.partials.icons')
<header class="site-header">
    <div class="header">
        @include('site.partials.brand', ['big' => $bigBrand ?? false])
        @include('site.partials.nav')
    </div>
</header>

<div class="wrap">
    @yield('content')
</div>

<footer class="site-footer">
    <div class="footer">
        <div><strong>Шаманка.online</strong>Путеводитель по берегам Иркута</div>
        <nav aria-label="Меню в подвале">
            <a href="/#routes">Маршруты</a>
            <a href="/stati/transport/">Как добраться</a>
            <a href="/#services">Услуги</a>
            <a href="/#contact">Связаться</a>
        </nav>
        <div>Берегите природу. Уносите мусор с собой.</div>
    </div>
</footer>
<script src="/js/map.js?v={{ filemtime(public_path('js/map.js')) }}" defer></script>
<script src="/js/site.js?v={{ filemtime(public_path('js/site.js')) }}" defer></script>
</body>
</html>
