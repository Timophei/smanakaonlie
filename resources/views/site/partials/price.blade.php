@php
    $from = $place->price_from ?: $place->variants->pluck('price')->filter()->min();
@endphp
@if ($from)
    <div>
        <div class="small muted">{{ \App\Models\Place::PRICE_UNITS[$place->price_unit] ?? 'цена' }}</div>
        <div class="{{ $class }}">от {{ number_format($from, 0, ',', ' ') }} ₽</div>
    </div>
@else
    <div class="small muted">Цену уточняйте у хозяина</div>
@endif
