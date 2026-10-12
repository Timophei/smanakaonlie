@php
    $isFile = ($data['source'] ?? 'link') === 'file';
    $embed = $isFile ? null : \App\Support\VideoEmbed::url($data['link'] ?? null);
    $autoplay = ! empty($data['autoplay']);
@endphp
@if ($embed || ($isFile && ! empty($data['file'])))
    <figure class="b b--{{ $data['width'] ?? 'wide' }} video-block">
        @if ($embed)
            <div class="video-frame">
                <iframe src="{{ $embed }}" title="{{ $data['caption'] ?? 'Видео' }}" loading="lazy"
                        allow="autoplay; encrypted-media; fullscreen; picture-in-picture" allowfullscreen></iframe>
            </div>
        @else
            <video src="/storage/{{ $data['file'] }}" preload="metadata" playsinline
                   @if (! empty($data['poster'])) poster="/storage/{{ $data['poster'] }}" @endif
                   @if ($data['controls'] ?? true) controls @endif
                   @if ($autoplay) autoplay muted @endif
                   @if (! empty($data['loop'])) loop @endif></video>
        @endif
        @if (! empty($data['caption']))<figcaption>{{ $data['caption'] }}</figcaption>@endif
    </figure>
@endif
