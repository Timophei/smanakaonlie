@extends('site.layout', ['active' => 'services'])

@php
    $isHotel = $catalog->category === 'hotel';
    $promos = [
        'hotel' => ['/uslugi/Bani/', 'service-bani.jpg', 'К жилью в придачу', 'После тропы — в баню', 'Бани в Шаманке для отдыха вдвоём, семьёй или компанией.', 'Смотреть бани'],
        'bath' => ['/uslugi/homes/', 'service-homes.jpg', 'Остаться подольше', 'После бани — с ночёвкой', 'Домики и гостевые дома рядом с рекой и тропами.', 'Смотреть жильё'],
        'food' => ['/uslugi/homes/', 'service-homes.jpg', 'Остаться подольше', 'Где переночевать', 'Домики и гостевые дома рядом с рекой и тропами.', 'Смотреть жильё'],
        'river' => ['/uslugi/Bani/', 'service-bani.jpg', 'После реки', 'Согреться в бане', 'Бани в Шаманке для отдыха вдвоём, семьёй или компанией.', 'Смотреть бани'],
    ];
    $promo = $promos[$catalog->category] ?? null;
    $join = [
        'hotel' => 'Сдаёте жильё в Шаманке?',
        'bath' => 'Есть баня в Шаманке?',
        'food' => 'Продаёте свои продукты?',
        'river' => 'Катаете по Иркуту?',
    ][$catalog->category] ?? 'Хотите в каталог?';
@endphp

@section('title', $catalog->seo_title ?: $catalog->h1.' — Шаманка')
@section('description', $catalog->seo_description ?: $catalog->lead)

@section('content')
    <nav class="crumbs" aria-label="Хлебные крошки">
        <a href="/">Главная</a><span>/</span><span>{{ $catalog->h1 }}</span>
    </nav>

    <div class="stack">
        <h1>{{ $catalog->h1 }}</h1>
        @if ($catalog->lead)<p class="lead">{!! nl2br(e($catalog->lead)) !!}</p>@endif
    </div>

    @if ($tags->isNotEmpty())
        <nav class="filters" aria-label="Фильтр">
            <a class="filter" href="{{ $catalog->url() }}" @if(! $tag) aria-current="true" @endif>Все</a>
            @foreach ($tags as $item)
                <a class="filter" href="{{ $catalog->url() }}?tag={{ $item->id }}" @if($tag?->is($item)) aria-current="true" @endif>{{ $item->name }}</a>
            @endforeach
        </nav>
    @endif

    <div class="stack" style="gap: 16px">
        @forelse ($places as $place)
            <article class="card place">
                @if ($place->cover)
                    <img class="place-photo" src="{{ '/storage/'.$place->cover }}" alt="{{ $place->title }}" loading="lazy">
                @else
                    <div class="place-photo photo-empty">Фото скоро появится</div>
                @endif
                <div class="place-info">
                    <h2><a href="{{ route('place', $place->path, false) }}/">{{ $place->title }}</a></h2>
                    @if ($place->address)<div class="small muted">{{ $place->address }}</div>@endif
                    @if ($place->short_description)<p>{{ $place->short_description }}</p>@endif
                    <div class="chips">
                        @if ($place->status !== 'published')<span class="chip chip--draft">Черновик — видите только вы</span>@endif
                        @if ($isHotel && $place->variants->isNotEmpty())<span class="chip">Вариантов: {{ $place->variants->sum('quantity') }}</span>@endif
                        @if ($place->guests_max)<span class="chip">До {{ $place->guests_max }} гостей</span>@endif
                        @foreach ($place->amenities->take(3) as $amenity)<span class="chip">{{ $amenity->name }}</span>@endforeach
                    </div>
                </div>
                <div class="place-side">
                    @include('site.partials.price', ['place' => $place, 'class' => 'price'])
                    <a class="btn" href="{{ route('place', $place->path, false) }}/">Подробнее</a>
                </div>
            </article>

            @if ($promo && ($loop->iteration === 2 || ($loop->last && $loop->count < 2)))
                <a class="promo" href="{{ $promo[0] }}">
                    <img src="/img/{{ $promo[1] }}" alt="" loading="lazy">
                    <span><span class="promo-kicker">{{ $promo[2] }}</span><b>{{ $promo[3] }}</b>{{ $promo[4] }}</span>
                    <span class="more">{{ $promo[5] }} <svg class="icon" aria-hidden="true"><use href="#i-next"/></svg></span>
                </a>
            @endif
        @empty
            <div class="card card-body">По этому фильтру пока ничего нет. <a href="{{ $catalog->url() }}">Показать всё</a></div>
        @endforelse
    </div>

    <section class="card between" style="padding: 20px 24px">
        <div><b style="font-size: 20px">{{ $join }}</b><div class="muted">Добавим вас в каталог — напишите нам.</div></div>
        <a class="more" href="mailto:main@shamanka.online">main@shamanka.online</a>
    </section>
@endsection
