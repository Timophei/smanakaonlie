<button type="button" class="nav-toggle" data-nav-toggle aria-expanded="false" aria-controls="site-nav" aria-label="Открыть меню">
    <span></span><span></span><span></span>
</button>
<nav id="site-nav" class="nav" aria-label="Основное меню">
    <a href="/#routes">Маршруты</a>
    <a href="/stati/transport/">Как добраться</a>
    <a href="{{ route('hotels', absolute: false) }}/" @if(($active ?? '') === 'services') aria-current="page" @endif>Жильё и услуги</a>
    <a class="btn-outline" href="/karta/"><svg class="icon" aria-hidden="true"><use href="#i-map"/></svg>Открыть карту</a>
</nav>
