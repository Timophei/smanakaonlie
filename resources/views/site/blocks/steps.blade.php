<section class="b b--text steps">
    @if (! empty($data['title']))<h2>{{ $data['title'] }}</h2>@endif
    <ol class="steps-list">
        @foreach ($data['items'] ?? [] as $item)
            <li>
                <div class="steps-head"><b>{{ $item['title'] ?? '' }}</b>@if (! empty($item['time']))<span class="chip">{{ $item['time'] }}</span>@endif</div>
                @if (! empty($item['text']))<p>{{ $item['text'] }}</p>@endif
                @if (! empty($item['photo']))<img src="/storage/{{ $item['photo'] }}" alt="{{ $item['title'] ?? '' }}" loading="lazy">@endif
            </li>
        @endforeach
    </ol>
</section>
