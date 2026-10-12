@php $titles = ['tip' => 'Совет', 'warning' => 'Важно', 'info' => 'На заметку']; @endphp
<aside class="b b--text callout callout--{{ $data['kind'] ?? 'tip' }}">
    <b>{{ $data['title'] ?? ($titles[$data['kind'] ?? 'tip'] ?? 'Совет') }}</b>
    <div class="prose">{!! $data['body'] ?? '' !!}</div>
</aside>
