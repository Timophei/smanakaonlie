@extends('site.layout', ['bigBrand' => true])


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
        <div class="row quick-list">
            @foreach ([
                ['hotel', 'i-house', 'Гостиницы', 'Где остановиться'],
                ['bath', 'i-bath', 'Бани', 'Пар и отдых'],
                ['food', 'i-basket', 'Продукты', 'Деревенские вкусности'],
                ['river', 'i-boat', 'Рыбалка и река', 'Лодки, сплавы, рыбалка'],
            ] as [$category, $icon, $title, $text])
                <a class="card quick" href="{{ \App\Models\CatalogPage::forCategory($category)->url() }}"><span class="round"><svg class="icon" aria-hidden="true"><use href="#{{ $icon }}"/></svg></span><span class="grow"><b>{{ $title }}</b><span class="small muted">{{ $text }}</span></span><svg class="icon" aria-hidden="true"><use href="#i-next"/></svg></a>
            @endforeach
        </div>
        @if ($offers->isNotEmpty())
            <div class="carousel carousel--always carousel--tiles" data-carousel>
                <button type="button" class="carousel-btn carousel-btn--prev" data-carousel-prev aria-label="Предыдущее предложение"><svg class="icon" aria-hidden="true"><use href="#i-back"/></svg></button>
                <div class="row carousel-track" data-carousel-track>
                    @foreach ($offers as $offer)
                        <article class="card tile">
                            <img src="/storage/{{ $offer->cover }}" alt="{{ $offer->title }}" loading="lazy">
                            <div class="card-body">
                                <span class="small muted">{{ \App\Models\Place::CATEGORIES[$offer->category] ?? '' }}</span>
                                <h3>{{ $offer->title }}</h3>
                                @if ($offer->short_description)<p class="muted">{{ \Illuminate\Support\Str::limit($offer->short_description, 110) }}</p>@endif
                                <a class="more" href="/{{ $offer->path }}/">Подробнее <svg class="icon" aria-hidden="true"><use href="#i-out"/></svg></a>
                            </div>
                        </article>
                    @endforeach
                </div>
                <button type="button" class="carousel-btn carousel-btn--next" data-carousel-next aria-label="Следующее предложение"><svg class="icon" aria-hidden="true"><use href="#i-next"/></svg></button>
            </div>
        @endif
    </section>

    @if ($photos->isNotEmpty())
        <section id="photos" class="section">
            <div>
                <h2>Шаманка в фото</h2>
                <p class="lead">Снимки гостей и местных жителей. Пришлите свои — опубликуем с подписью и ссылкой на вас.</p>
            </div>
            <div class="carousel carousel--always" data-carousel data-lightbox="Шаманка в фото">
                <button type="button" class="carousel-btn carousel-btn--prev" data-carousel-prev aria-label="Предыдущее фото"><svg class="icon" aria-hidden="true"><use href="#i-back"/></svg></button>
                <div class="row carousel-track" data-carousel-track>
                    @foreach ($photos as $photo)
                        <figure class="photo-slide" data-lightbox-src="{{ $photo->url() }}" data-lightbox-caption="{{ $photo->alt }}">
                            <img src="{{ $photo->url() }}" alt="{{ $photo->alt }}" loading="lazy">
                            @if ($photo->authorLabel())
                                <figcaption class="photo-credit">Фото:
                                    @if ($photo->author_url)<a href="{{ $photo->author_url }}" target="_blank" rel="nofollow noopener">{{ $photo->authorLabel() }}</a>@else{{ $photo->authorLabel() }}@endif
                                </figcaption>
                            @endif
                        </figure>
                    @endforeach
                </div>
                <button type="button" class="carousel-btn carousel-btn--next" data-carousel-next aria-label="Следующее фото"><svg class="icon" aria-hidden="true"><use href="#i-next"/></svg></button>
            </div>
            <script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@graph' => $photos->map->structuredData()->values()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
        </section>
    @endif

    <section id="contact" class="contact">
        <div class="stack">
            <h2 style="font-size: 34px">Есть что добавить?</h2>
            <p>Расскажите о своём месте, предложите маршрут, пришлите фото или добавьте услуги в каталог.</p>
            <a class="more mail" href="mailto:main@shamanka.online"><svg class="icon" aria-hidden="true"><use href="#i-mail"/></svg>main@shamanka.online</a>
        </div>
    </section>
@endsection
