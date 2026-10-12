{{-- Компактная карточка объекта для подборок в статьях --}}
<a class="card tile place-card" href="/{{ $place->path }}/">
    @if ($place->cover)
        <img src="/storage/{{ $place->cover }}" alt="{{ $place->title }}" loading="lazy">
    @else
        <span class="photo-empty" style="aspect-ratio: 3 / 2; min-height: 0">Фото скоро появится</span>
    @endif
    <span class="card-body">
        <span class="small muted">{{ \App\Models\Place::CATEGORIES[$place->category] ?? '' }}</span>
        <b style="font-size: 19px">{{ $place->title }}</b>
        @if ($place->short_description)<span class="small muted">{{ $place->short_description }}</span>@endif
        @if ($place->price_from)<span class="price" style="font-size: 18px">от {{ number_format($place->price_from, 0, ',', ' ') }} ₽</span>@endif
    </span>
</a>
