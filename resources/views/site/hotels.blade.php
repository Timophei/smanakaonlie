@extends('site.layout', ['active' => 'services'])

@section('title', 'Жильё в Шаманке — гостевые дома и домики у Иркута')
@section('description', 'Домики, комнаты и места для ночлега в Шаманке рядом с рекой и тропами.')

@section('content')
    <nav class="crumbs" aria-label="Хлебные крошки">
        <a href="/">Главная</a><span>/</span><span>Жильё</span>
    </nav>

    <div class="stack">
        <h1>Жильё в Шаманке</h1>
        <p class="lead">Хочется остаться на нашем спокойном берегу чуть дольше, чем на пару часов? Здесь собраны домики, комнаты и места для ночлега рядом с рекой и тропами.</p>
    </div>

    @if ($tags->isNotEmpty())
        <nav class="filters" aria-label="Фильтр жилья">
            <a class="filter" href="{{ route('hotels', absolute: false) }}/" @if(! $tag) aria-current="true" @endif>Все</a>
            @foreach ($tags as $item)
                <a class="filter" href="{{ route('hotels', ['tag' => $item->id], false) }}" @if($tag?->is($item)) aria-current="true" @endif>{{ $item->name }}</a>
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
                        @if ($place->variants->isNotEmpty())<span class="chip">Вариантов: {{ $place->variants->sum('quantity') }}</span>@endif
                        @if ($place->guests_max)<span class="chip">До {{ $place->guests_max }} гостей</span>@endif
                        @foreach ($place->amenities->take(3) as $amenity)<span class="chip">{{ $amenity->name }}</span>@endforeach
                    </div>
                </div>
                <div class="place-side">
                    @include('site.partials.price', ['place' => $place, 'class' => 'price'])
                    <a class="btn" href="{{ route('place', $place->path, false) }}/">Подробнее</a>
                </div>
            </article>

            @if ($loop->iteration === 2 || ($loop->last && $loop->count < 2))
                <a class="promo" href="/uslugi/Bani/">
                    <img src="/img/service-bani.jpg" alt="" loading="lazy">
                    <span><span class="promo-kicker">К жилью в придачу</span><b>После тропы — в баню</b>Бани в Шаманке для отдыха вдвоём, семьёй или компанией.</span>
                    <span class="more">Смотреть бани <svg class="icon" aria-hidden="true"><use href="#i-next"/></svg></span>
                </a>
            @endif
        @empty
            <div class="card card-body">По этому фильтру пока ничего нет. <a href="{{ route('hotels', absolute: false) }}/">Показать всё жильё</a></div>
        @endforelse
    </div>

    <section class="card between" style="padding: 20px 24px">
        <div><b style="font-size: 20px">Сдаёте жильё в Шаманке?</b><div class="muted">Добавим ваш дом в каталог — напишите нам.</div></div>
        <a class="more" href="mailto:main@shamanka.online">main@shamanka.online</a>
    </section>
@endsection
