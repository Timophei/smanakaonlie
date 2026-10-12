{{-- Интерактивная карта: картинка, поверх неё контуры зон и точки объектов. Логика — в public/js/map.js --}}
@php
    $map = config('map');
    $map['image'] = $theme?->mapUrl() ?? $map['image'];
    $map['zones'] = $theme?->map_zones ?: \App\Models\MapZone::forMap();
    $projection = \App\Support\MapProjection::current();
    // Объекты с точкой на рисунке; при привязке к местности — ещё и те, у кого есть только широта и долгота
    $markers = \App\Models\Place::query()
        ->when(! auth()->check(), fn ($query) => $query->where('status', 'published'))
        ->where('status', '!=', 'hidden')
        ->where(fn ($query) => $query->whereNotNull('map_x')->whereNotNull('map_y')
            ->when($projection, fn ($query) => $query->orWhere(fn ($query) => $query->whereNotNull('lat')->whereNotNull('lng'))))
        ->get(['id', 'title', 'path', 'category', 'short_description', 'map_x', 'map_y', 'lat', 'lng'])
        ->map(function ($marker) use ($projection, $map) {
            if (($marker->map_x === null || $marker->map_y === null) && $projection) {
                [$x, $y] = $projection->project((float) $marker->lat, (float) $marker->lng);
                $marker->map_x = round($x / $map['width'] * 100, 2);
                $marker->map_y = round($y / $map['height'] * 100, 2);
            }

            return $marker;
        })
        ->filter(fn ($marker) => $marker->map_x >= 0 && $marker->map_x <= 100 && $marker->map_y >= 0 && $marker->map_y <= 100)
        ->sortBy('map_y');
@endphp
<div class="imap @if($collapsible ?? false) is-collapsed @endif" data-map @if($collapsible ?? false) data-collapsible @endif
     @if ($projection) data-projection='@json($projection->toArray())' @endif>
    @if ($collapsible ?? false)
        <noscript><style>.imap.is-collapsed .imap-scroll { max-height: none; } .imap.is-collapsed .imap-canvas { margin-top: 0; } .imap-expand { display: none; }</style></noscript>
        <div class="imap-bar">
            <button type="button" class="btn imap-collapse" data-map-collapse hidden>Свернуть карту</button>
        </div>
        <button type="button" class="imap-expand" data-map-expand aria-expanded="false">
            <span class="btn"><svg class="icon" aria-hidden="true"><use href="#i-map"/></svg>Развернуть карту</span>
        </button>
    @endif
    <div class="imap-scroll">
        <div class="imap-canvas" style="aspect-ratio: {{ $map['width'] }} / {{ $map['height'] }}">
            <img src="{{ $map['image'] }}" width="{{ $map['width'] }}" height="{{ $map['height'] }}"
                 alt="Рисованная карта Шаманки: Шаманский утёс, река Шаманка, Иркут, паромная переправа и подвесной мост">
            <svg class="imap-zones" viewBox="0 0 {{ $map['width'] }} {{ $map['height'] }}" role="group" aria-label="Места на карте">
                @foreach ($map['zones'] as $zone)
                    <{{ $zone['shape'] === 'line' ? 'polyline' : 'polygon' }}
                        class="imap-zone imap-zone--{{ $zone['shape'] }}" points="{{ $zone['points'] }}"
                        tabindex="0" role="button" aria-label="{{ $zone['title'] }}"
                        data-title="{{ $zone['title'] }}" data-text="{{ $zone['text'] }}" data-href="{{ $zone['href'] }}" />
                @endforeach
            </svg>
            @foreach ($markers as $marker)
                <button type="button" class="imap-pin imap-pin--{{ $marker->category }}"
                        style="left: {{ $marker->map_x }}%; top: {{ $marker->map_y }}%"
                        aria-label="{{ $marker->title }}"
                        data-title="{{ $marker->title }}" data-text="{{ $marker->short_description }}"
                        data-href="{{ route('place', $marker->path, false) }}/"></button>
            @endforeach
            <div class="imap-me" data-me hidden aria-label="Вы здесь"><span class="imap-me-accuracy" data-me-accuracy></span><span class="imap-me-dot"></span></div>
            <div class="imap-tip" role="status" hidden>
                <b data-tip-title></b>
                <span data-tip-text></span>
                <a class="more" data-tip-link href="#">Подробнее <svg class="icon" aria-hidden="true"><use href="#i-next"/></svg></a>
            </div>
        </div>
    </div>
    <div class="imap-legend small">
        @if ($projection)
            <button type="button" class="btn-outline imap-locate" data-locate><svg class="icon" aria-hidden="true"><use href="#i-pin"/></svg><span data-locate-label>Где я?</span></button>
            <span class="imap-geo-status" data-geo-status role="status" hidden></span>
        @endif
        <span>Наведите или нажмите на место, чтобы узнать о нём.</span>
        @if ($markers->isNotEmpty())<span><i class="imap-dot imap-pin--hotel"></i>Жильё</span>@endif
    </div>
</div>
