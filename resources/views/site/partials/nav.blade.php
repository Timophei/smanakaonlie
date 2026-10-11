<nav class="nav" aria-label="Основное меню">
    <a href="{{ route('home') }}#routes">Маршруты</a>
    <a href="/stati/transport">Как добраться</a>
    <a href="{{ route('hotels') }}" @if(($active ?? '') === 'services') aria-current="page" @endif>Жильё и услуги</a>
    <a class="btn-outline" href="{{ route('home') }}#map"><svg class="icon" aria-hidden="true"><use href="#i-map"/></svg>Открыть карту</a>
</nav>
