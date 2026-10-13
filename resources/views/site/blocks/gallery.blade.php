@php
    $images = $data['images'] ?? [];
    $carousel = ($data['layout'] ?? 'grid') === 'carousel';
@endphp
@if ($images)
    <figure class="b b--{{ $data['width'] ?? 'wide' }} gallery-block" data-lightbox="{{ $data['caption'] ?? 'Фото' }}">
        @if ($carousel)
            <div class="carousel carousel--always" data-carousel>
                <button type="button" class="carousel-btn carousel-btn--prev" data-carousel-prev aria-label="Предыдущее фото"><svg class="icon" aria-hidden="true"><use href="#i-back"/></svg></button>
                <div class="row carousel-track" data-carousel-track>
                    @foreach ($images as $i => $image)
                        <img src="/storage/{{ $image }}" alt="{{ $data['caption'] ?? 'Фото' }} {{ $i + 1 }}" loading="lazy" data-lightbox-src="/storage/{{ $image }}" data-lightbox-caption="{{ $data['caption'] ?? '' }}">
                    @endforeach
                </div>
                <button type="button" class="carousel-btn carousel-btn--next" data-carousel-next aria-label="Следующее фото"><svg class="icon" aria-hidden="true"><use href="#i-next"/></svg></button>
            </div>
        @else
            <div class="gallery-grid">
                @foreach ($images as $i => $image)
                    <img src="/storage/{{ $image }}" alt="{{ $data['caption'] ?? 'Фото' }} {{ $i + 1 }}" loading="lazy" data-lightbox-src="/storage/{{ $image }}" data-lightbox-caption="{{ $data['caption'] ?? '' }}">
                @endforeach
            </div>
        @endif
        @if (! empty($data['caption']))<figcaption>{{ $data['caption'] }}</figcaption>@endif
    </figure>
@endif
