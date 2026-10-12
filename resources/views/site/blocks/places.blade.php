@php
    $places = \App\Models\Place::where('status', 'published')
        ->when($data['tag_id'] ?? null, fn ($query, $tagId) => $query->whereHas('tags', fn ($tags) => $tags->whereKey($tagId)))
        ->when($data['category'] ?? null, fn ($query, $category) => $query->where('category', $category))
        ->orderByDesc('is_featured')->orderBy('sort')
        ->limit(($data['format'] ?? 'cards') === 'banner' ? 1 : max(1, min(6, (int) ($data['limit'] ?? 3))))
        ->get();
@endphp
@if ($places->isNotEmpty())
    @if (($data['format'] ?? 'cards') === 'banner')
        @php $place = $places->first(); @endphp
        <a class="b b--wide promo" href="/{{ $place->path }}/">
            @if ($place->cover)<img src="/storage/{{ $place->cover }}" alt="" loading="lazy">@endif
            <span><span class="promo-kicker">{{ $data['title'] ?? 'Рядом с маршрутом' }}</span><b>{{ $place->title }}</b>{{ $place->short_description }}</span>
            <span class="more">Подробнее <svg class="icon" aria-hidden="true"><use href="#i-next"/></svg></span>
        </a>
    @else
        <section class="b b--wide places-block">
            @if (! empty($data['title']))<h2>{{ $data['title'] }}</h2>@endif
            <div class="row">
                @foreach ($places as $place)
                    @include('site.partials.place-card', ['place' => $place])
                @endforeach
            </div>
        </section>
    @endif
@endif
