@extends('site.layout', ['active' => 'services'])

@php
    use App\Models\OwnerContact;
    use App\Models\Place;

    $photo = fn (string $path) => asset('storage/'.$path);
    $gallery = $place->gallery ?? [];
    $facts = array_filter([
        'Тип' => Place::HOTEL_TYPES[$place->type] ?? null,
        'Гостей' => $place->guests_max ? ($place->guests_min && $place->guests_min != $place->guests_max ? "{$place->guests_min}–{$place->guests_max}" : "до {$place->guests_max}") : null,
        'Спален' => $place->bedrooms,
        'Спальных мест' => $place->beds,
        'Заезд' => $place->checkin_time,
        'Выезд' => $place->checkout_time,
        'Берег Иркута' => Place::BANKS[$place->bank] ?? null,
        'До тропы' => $place->distance_trail_m ? $place->distance_trail_m.' м' : null,
        'До реки' => $place->distance_river_m ? $place->distance_river_m.' м' : null,
    ]);
    $months = [1 => 'январь', 'февраль', 'март', 'апрель', 'май', 'июнь', 'июль', 'август', 'сентябрь', 'октябрь', 'ноябрь', 'декабрь'];
@endphp

@section('title', $place->seo_title ?: $place->title.' — жильё в Шаманке')
@section('description', $place->seo_description ?: $place->short_description)

@section('content')
    <nav class="crumbs" aria-label="Хлебные крошки">
        <a href="{{ route('home') }}">Главная</a><span>/</span><a href="{{ route('hotels') }}">Жильё</a><span>/</span><span>{{ $place->title }}</span>
    </nav>

    <div class="stack">
        <h1>{{ $place->title }}</h1>
        <div class="chips" style="align-items: center; gap: 8px 12px">
            @if ($place->address)<span style="display: inline-flex; align-items: center; gap: 6px"><svg class="icon" style="color: var(--accent)" aria-hidden="true"><use href="#i-pin"/></svg>{{ $place->address }}</span>@endif
            @if ($place->status !== 'published')<span class="chip chip--draft">Черновик — видите только вы</span>@endif
            @unless ($place->is_open)<span class="chip chip--draft">Сейчас не принимает гостей</span>@endunless
            @foreach ($place->tags as $tag)<span class="chip">{{ $tag->name }}</span>@endforeach
        </div>
    </div>

    @if ($place->cover || $gallery)
        <div class="shots">
            <img src="{{ $photo($place->cover ?: $gallery[0]) }}" alt="{{ $place->title }}">
            @if (count($gallery) > ($place->cover ? 0 : 1))
                <div class="shots-grid">
                    @foreach (array_slice($gallery, $place->cover ? 0 : 1, 4) as $i => $path)
                        <img src="{{ $photo($path) }}" alt="{{ $place->title }}, фото {{ $i + 2 }}" loading="lazy">
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    <div class="columns">
        <div class="main">
            @if ($place->description || $place->short_description)
                <section class="stack">
                    <h2>Описание</h2>
                    <div class="prose">{!! $place->description ?: e($place->short_description) !!}</div>
                </section>
            @endif

            @if ($facts)
                <section class="stack">
                    <h2>Коротко</h2>
                    <div class="facts">
                        @foreach ($facts as $label => $value)
                            <div class="card fact"><span class="small muted">{{ $label }}</span><b>{{ $value }}</b></div>
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($place->variants->isNotEmpty())
                <section class="stack">
                    <h2>Варианты размещения</h2>
                    <div class="row">
                        @foreach ($place->variants as $variant)
                            <article class="card variant" id="variant-{{ $variant->id }}">
                                @if ($variant->photos)<img src="{{ $photo($variant->photos[0]) }}" alt="{{ $variant->title }}" loading="lazy">@endif
                                <div class="card-body">
                                    <h3>{{ $variant->title }}</h3>
                                    <div class="chips">
                                        @if ($variant->guests)<span class="chip">до {{ $variant->guests }} гостей</span>@endif
                                        @if ($variant->quantity > 1)<span class="chip">таких {{ $variant->quantity }}</span>@endif
                                    </div>
                                    @if ($variant->note)<p class="muted">{{ $variant->note }}</p>@endif
                                    @if ($variant->price)<div class="price">{{ number_format($variant->price, 0, ',', ' ') }} ₽</div>@endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($place->amenities->isNotEmpty())
                <section class="stack">
                    <h2>Что есть</h2>
                    <ul class="ticks">
                        @foreach ($place->amenities as $amenity)
                            <li><svg class="icon" aria-hidden="true"><use href="#i-check"/></svg>{{ $amenity->name }}</li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if (! $place->all_year && $place->season_months)
                <section class="stack">
                    <h2>Сезон</h2>
                    <p class="prose">Работает: {{ collect($place->season_months)->sort()->map(fn ($m) => $months[$m] ?? null)->filter()->implode(', ') }}.</p>
                </section>
            @endif
        </div>

        <aside class="card book">
            @include('site.partials.price', ['place' => $place, 'class' => 'price price--big'])
            @if ($place->price_note)<div class="small muted">{{ $place->price_note }}</div>@endif

            @if ($place->owner?->phone)
                <button type="button" class="btn" data-phone-url="{{ route('place.phone', $place) }}" data-token="{{ csrf_token() }}">
                    <svg class="icon" aria-hidden="true"><use href="#i-phone"/></svg><span data-label>Показать телефон</span>
                </button>
            @endif
            @foreach ($place->owner?->contacts ?? [] as $contact)
                <a class="btn-outline" href="{{ route('place.contact', [$place, $contact]) }}" rel="nofollow noopener" target="_blank">
                    {{ $contact->type === 'site' ? 'Сайт хозяина' : 'Написать в '.(OwnerContact::TYPES[$contact->type] ?? 'мессенджер') }}
                </a>
            @endforeach

            @if ($place->owner)
                <div class="book-note">Хозяин: {{ $place->owner->name }}. Скажите, что нашли дом на Шаманка.online.</div>
            @else
                <div class="book-note">Контакты хозяина скоро появятся. Пока можно написать нам: <a href="mailto:main@shamanka.online">main@shamanka.online</a></div>
            @endif
        </aside>
    </div>

    <a class="more" href="{{ route('hotels') }}"><svg class="icon" aria-hidden="true"><use href="#i-back"/></svg>Всё жильё в Шаманке</a>
@endsection
