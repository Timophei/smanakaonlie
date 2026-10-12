<button type="button" class="nav-toggle" data-nav-toggle aria-expanded="false" aria-controls="site-nav" aria-label="Открыть меню">
    <svg class="nav-toggle-open" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
    <svg class="nav-toggle-close" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
</button>
<nav id="site-nav" class="nav" aria-label="Основное меню">
    <a href="/#routes">Маршруты</a>
    <a href="/stati/transport/">Как добраться</a>
    <a href="{{ route('hotels', absolute: false) }}/" @if(($active ?? '') === 'services') aria-current="page" @endif>Жильё и услуги</a>
</nav>
