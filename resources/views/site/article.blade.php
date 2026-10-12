@extends('site.layout')

@section('title', $article->seo_title ?: $article->title.' — Шаманка')
@section('description', $article->seo_description ?: $article->lead)

@section('content')
    <article class="article">
        <nav class="crumbs" aria-label="Хлебные крошки">
            <a href="/">Главная</a><span>/</span><span>{{ $article->title }}</span>
        </nav>

        <header class="article-head">
            @if ($article->status !== 'published')<span class="chip chip--draft">Черновик — видите только вы</span>@endif
            <h1>{{ $article->title }}</h1>
            @if ($article->lead)<p class="lead">{{ $article->lead }}</p>@endif
            @if ($article->facts)
                <dl class="article-facts">
                    @foreach ($article->facts as $fact)
                        <div><dt>{{ $fact['label'] ?? '' }}</dt><dd>{{ $fact['value'] ?? '' }}</dd></div>
                    @endforeach
                </dl>
            @endif
        </header>

        @if ($article->cover)
            <figure class="b b--wide article-cover">
                <img src="/storage/{{ $article->cover }}" alt="{{ $article->title }}">
            </figure>
        @endif

        <div class="article-body">
            @foreach ($article->content ?? [] as $block)
                @includeIf('site.blocks.'.$block['type'], ['data' => $block['data'] ?? []])
            @endforeach
        </div>

        @if ($related->isNotEmpty())
            <section class="b b--wide article-related">
                <h2>Рядом с маршрутом</h2>
                <div class="row">
                    @foreach ($related as $place)
                        @include('site.partials.place-card', ['place' => $place])
                    @endforeach
                </div>
            </section>
        @endif

        <a class="more b" href="/#routes"><svg class="icon" aria-hidden="true"><use href="#i-back"/></svg>Все маршруты</a>
    </article>
@endsection
