@extends('site.layout')

@section('brand')
    <a class="brand brand--big" href="/">
        <span>
            <img class="brand-logo" src="{{ $theme?->logoUrl() ?? '/img/logos/autumn-logo.png' }}" alt="Открой Шаманку" width="864" height="170">
            <small>Тропы, утёс и жизнь на берегу Иркута.</small>
        </span>
    </a>
@endsection

@section('content')
    <div class="row" style="align-items: center">
        <div class="notice">
            <svg class="icon notice-icon" aria-hidden="true"><use href="#i-shield"/></svg>
            <div class="notice-text">
                <b>Особо охраняемая <span class="only-desktop">природная </span>территория</b>
                <div class="small only-desktop">Перед прогулкой ознакомьтесь с правилами посещения.</div>
            </div>
            <a class="btn" href="https://ooptirk.ru/registration/" rel="noopener"><span class="only-desktop">Пройти инструктаж</span><span class="only-mobile">Инструктаж</span> <svg class="icon" aria-hidden="true"><use href="#i-out"/></svg></a>
        </div>
        <div style="flex: 1 1 220px" class="small notice-aside">
            Выберите маршрут для прогулки или останьтесь на выходные.<br>
            <a class="more" href="/stati/transport/">Как добраться <svg class="icon" aria-hidden="true"><use href="#i-out"/></svg></a>
        </div>
    </div>

    <section id="map" class="stack">
        <div class="between">
            <h2 class="map-title">Шаманка как на ладони</h2>
        </div>
        @include('site.partials.map', ['collapsible' => true])
        <div class="row quick-list">
            <a class="card quick" href="/stati/tropa/"><span class="round"><svg class="icon" aria-hidden="true"><use href="#i-hike"/></svg></span><span class="grow"><b>Тропа «Шаманка»</b><span class="small muted">Прогулка к утёсу</span></span><svg class="icon" aria-hidden="true"><use href="#i-next"/></svg></a>
            <a class="card quick" href="/stati/LesChudes/"><span class="round"><svg class="icon" aria-hidden="true"><use href="#i-tree"/></svg></span><span class="grow"><b>Лес чудес</b><span class="small muted">Лес и поляна</span></span><svg class="icon" aria-hidden="true"><use href="#i-next"/></svg></a>
            <a class="card quick" href="/stati/na_piknik/"><span class="round"><svg class="icon" aria-hidden="true"><use href="#i-table"/></svg></span><span class="grow"><b>На пикник</b><span class="small muted">Отдых у реки</span></span><svg class="icon" aria-hidden="true"><use href="#i-next"/></svg></a>
            <a class="card quick" href="/stati/transport/"><span class="round"><svg class="icon" aria-hidden="true"><use href="#i-bus"/></svg></span><span class="grow"><b>Как добраться</b><span class="small muted">Автобус, машина, паром</span></span><svg class="icon" aria-hidden="true"><use href="#i-next"/></svg></a>
        </div>
    </section>

    <section id="routes" class="section">
        <div>
            <h2>Выберите свою прогулку</h2>
            <p class="lead">На утёс, в лес или к реке — найдите свой маршрут.</p>
        </div>
        <div class="carousel" data-carousel>
            <button type="button" class="carousel-btn carousel-btn--prev" data-carousel-prev aria-label="Предыдущий маршрут"><svg class="icon" aria-hidden="true"><use href="#i-back"/></svg></button>
            <div class="row carousel-track" data-carousel-track>
                @foreach ([
                    ['/stati/tropa/', 'route-tropa.jpg', 'Тропа вдоль красного утёса над рекой', 'Тропа «Шаманка»', 'Виды с утёса, лесные тропы и берега Иркута.'],
                    ['/stati/LesChudes/', 'route-les.jpg', 'Сосновый лес с валунами', 'Лес чудес и поляна', 'Лёгкая прогулка через лес к поляне у реки.'],
                    ['/stati/na_piknik/', 'route-piknik.jpg', 'Пикник на берегу реки', 'На пикник', 'Места для спокойного отдыха на берегу и в лесу.'],
                ] as [$href, $img, $alt, $title, $text])
                    <article class="card tile">
                        <img src="/img/{{ $img }}" alt="{{ $alt }}" loading="lazy">
                        <div class="card-body">
                            <h3>{{ $title }}</h3>
                            <p class="muted">{{ $text }}</p>
                            <a class="more" href="{{ $href }}">Подробнее <svg class="icon" aria-hidden="true"><use href="#i-out"/></svg></a>
                        </div>
                    </article>
                @endforeach
            </div>
            <button type="button" class="carousel-btn carousel-btn--next" data-carousel-next aria-label="Следующий маршрут"><svg class="icon" aria-hidden="true"><use href="#i-next"/></svg></button>
        </div>
    </section>

    <section id="services" class="section">
        <div>
            <h2>Останьтесь чуть дольше</h2>
            <p class="lead">Жильё, бани и местные предложения для отдыха в Шаманке.</p>
        </div>
        <div class="row">
            @foreach ([
                [route('hotels', absolute: false).'/', 'service-homes.jpg', 'Деревянный домик в сосновом лесу', 'Жильё', 'Домики и гостевые дома'],
                ['/uslugi/Bani/', 'service-bani.jpg', 'Парная с печью и веником', 'Бани', 'Отдых после прогулки'],
                ['/uslugi/reka/river/', 'service-river.jpg', 'Лодка и удочка на реке на закате', 'Рыбалка и лодки', 'Прогулки по Иркуту'],
                ['/uslugi/river_gulh/', 'service-food.jpg', 'Корзина с хлебом, молоком и овощами', 'Деревенские продукты', 'Местное — с собой и домой'],
            ] as [$href, $img, $alt, $title, $text])
                <a class="card tile tile--wide" style="flex-basis: 220px" href="{{ $href }}">
                    <img src="/img/{{ $img }}" alt="{{ $alt }}" loading="lazy">
                    <span class="card-body"><b style="font-size: 19px">{{ $title }}</b><span class="small muted">{{ $text }}</span></span>
                </a>
            @endforeach
        </div>
    </section>

    <section id="gallery" class="section">
        <h2>Шаманка в деталях</h2>
        <div class="row gallery" style="gap: 12px">
            <img src="/img/gallery-1.jpg" alt="Двое туристов на утёсе над Иркутом" loading="lazy">
            <img src="/img/gallery-2.jpg" alt="Гостья в красном шарфе на фоне утёса зимой" loading="lazy">
            <img src="/img/gallery-3.jpg" alt="Рябина у деревенского дома" loading="lazy">
        </div>
    </section>

    <section id="contact" class="contact">
        <div class="stack">
            <h2 style="font-size: 34px">Есть что добавить?</h2>
            <p>Расскажите о своём месте, предложите маршрут или добавьте услуги в каталог.</p>
            <a class="more mail" href="mailto:main@shamanka.online"><svg class="icon" aria-hidden="true"><use href="#i-mail"/></svg>main@shamanka.online</a>
        </div>
    </section>
@endsection
