@php
    $width = $data['width'] ?? 'text';
    $float = $width === 'text' ? ($data['float'] ?? 'none') : 'none';
@endphp
@if (! empty($data['image']))
    <figure class="b b--{{ $width }} @if($float !== 'none') b--float-{{ $float }} @endif">
        <img src="/storage/{{ $data['image'] }}" alt="{{ $data['alt'] ?? $data['caption'] ?? '' }}" loading="lazy">
        @if (! empty($data['caption']))<figcaption>{{ $data['caption'] }}</figcaption>@endif
    </figure>
@endif
