@extends('site.layout')

@section('title', 'Карта Шаманки — утёс, Иркут, паром, мост и места для отдыха')
@section('description', 'Рисованная карта Шаманки: Шаманский утёс, река Шаманка, Иркут, паромная переправа, подвесной мост, жильё и услуги.')

@section('content')
    <nav class="crumbs" aria-label="Хлебные крошки">
        <a href="/">Главная</a><span>/</span><span>Карта</span>
    </nav>
    <div class="stack">
        <h1>Карта Шаманки</h1>
        <p class="lead">Утёс, две реки, паром и мост — нажмите на место, чтобы узнать о нём больше.</p>
    </div>
    @include('site.partials.map')
@endsection
