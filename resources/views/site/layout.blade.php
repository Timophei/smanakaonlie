<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script>document.documentElement.classList.add('js')</script>
    @php
        $seo ??= null;
        $root = config('app.canonical_root');
        $canonical = $root.(request()->path() === '/' ? '/' : '/'.request()->path().'/');
        $pageTitle = trim($__env->yieldContent('title')) ?: e($seo?->seo_title ?: 'Шаманка — отдых, маршруты и красивые места у Иркута');
        $pageDescription = trim($__env->yieldContent('description')) ?: e($seo?->seo_description ?: 'Путеводитель по Шаманке Иркутской области: тропа на утёс, Лес чудес, места для пикника, транспорт, жильё, бани и местные услуги.');
        $ogImage = $seo?->og_image ?: ($seoImage ?? null);
        // Общая картинка превью главной — в цветах текущей темы (public/img/og/<тема>.jpg)
        if ($ogImage === 'seo/og-home.jpg' && $theme && is_file(public_path("img/og/{$theme->slug}.jpg"))) {
            $ogImage = "/img/og/{$theme->slug}.jpg";
        }
        $ogImageUrl = match (true) {
            ! $ogImage => $root.'/img/logos/autumn-logo.png',
            str_starts_with($ogImage, 'http') => $ogImage,
            str_starts_with($ogImage, '/') => $root.$ogImage,
            default => $root.'/storage/'.$ogImage,
        };
    @endphp
    <title>{!! $pageTitle !!}</title>
    <meta name="description" content="{!! Str::squish($pageDescription) !!}">
    @if ($seo?->seo_keywords)<meta name="keywords" content="{{ $seo->seo_keywords }}">@endif
    <link rel="icon" type="image/png" href="{{ $theme?->iconUrl() ?? '/img/logos/autumn-icon.png' }}">
    <link rel="canonical" href="{{ $canonical }}">
    <meta property="og:type" content="{{ isset($article) ? 'article' : 'website' }}">
    <meta property="og:site_name" content="Шаманка.online">
    <meta property="og:locale" content="ru_RU">
    <meta property="og:url" content="{{ $canonical }}">
    {{-- Превью в мессенджерах: без переносов строк, они ломают карточку ссылки --}}
    <meta property="og:title" content="{!! $seo?->og_title ? e(Str::squish($seo->og_title)) : $pageTitle !!}">
    <meta property="og:description" content="{!! $seo?->og_description ? e(Str::squish($seo->og_description)) : Str::squish($pageDescription) !!}">
    <meta property="og:image" content="{{ $ogImageUrl }}">
    @php
        // Размер и тип картинки превью — без них некоторые мессенджеры не строят большую карточку
        $ogFile = match (true) {
            ! $ogImage || str_starts_with($ogImage, 'http') => public_path('img/logos/autumn-logo.png'),
            str_starts_with($ogImage, '/') => public_path(ltrim($ogImage, '/')),
            default => storage_path('app/public/'.$ogImage),
        };
        $ogSize = is_file($ogFile) ? @getimagesize($ogFile) : false;
    @endphp
    @if ($ogSize)
        <meta property="og:image:width" content="{{ $ogSize[0] }}">
        <meta property="og:image:height" content="{{ $ogSize[1] }}">
        <meta property="og:image:type" content="{{ $ogSize['mime'] }}">
    @endif
    <meta property="og:image:alt" content="{!! $pageTitle !!}">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700&display=swap">
    <link rel="stylesheet" href="/css/site.css?v={{ filemtime(public_path('css/site.css')) }}">
    @if ($theme)
        <style>:root { {{ $theme->cssVariables() }} }</style>
    @endif
    @include('site.partials.metrika')
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
