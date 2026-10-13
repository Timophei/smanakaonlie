{{--
    QR-визитки 90×50 мм. Лист A4: 2 × 5 визиток с тонкими линиями реза
    (для одного объекта визитка повторяется 10 раз). ?single=1 — каждая визитка
    отдельной страницей в размер 90×50 мм, удобно отдать в типографию в PDF.
--}}
@php
    $kickers = ['hotel' => 'Жильё в Шаманке', 'bath' => 'Баня в Шаманке', 'food' => 'Деревенские продукты', 'river' => 'Отдых на Иркуте'];
    $printCards = ! $single && $cards->count() === 1 ? collect(array_fill(0, 10, $cards->first())) : $cards;
    $pages = $single ? $printCards->map(fn ($card) => collect([$card])) : $printCards->chunk(10);
    $ids = $cards->pluck('place.id')->implode(',');
@endphp
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>QR-визитки — {{ $cards->count() === 1 ? $cards->first()['place']->title : 'объектов: '.$cards->count() }}</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;700;800&display=swap">
    <style>
        :root { --t-header-bg: #4a3326; --t-logo: #f4e3c3; --t-primary: #9a4a1c; {{ $theme?->cssVariables() }} }
        * { box-sizing: border-box; margin: 0; }
        body { font-family: Manrope, system-ui, sans-serif; background: #e7e2da; color: #1f2933; -webkit-print-color-adjust: exact; print-color-adjust: exact; }

        .toolbar { position: sticky; top: 0; z-index: 2; display: flex; flex-wrap: wrap; gap: 10px 16px; align-items: center; padding: 14px 20px; background: #fff; box-shadow: 0 1px 6px rgba(0, 0, 0, .12); font-size: 14px; }
        .toolbar b { font-size: 16px; margin-right: auto; }
        .toolbar a, .toolbar button { display: inline-flex; align-items: center; padding: 8px 14px; border-radius: 8px; border: 1px solid #d6cfc4; background: #fff; color: #1f2933; font: inherit; font-weight: 700; text-decoration: none; cursor: pointer; }
        .toolbar .is-current { border-color: var(--t-primary); color: var(--t-primary); }
        .toolbar .primary { background: var(--t-primary); border-color: var(--t-primary); color: #fff; }
        .toolbar small { flex-basis: 100%; color: #6b6259; }

        .pages { display: flex; flex-direction: column; align-items: center; gap: 24px; padding: 24px; }
        .page { background: #fff; box-shadow: 0 2px 12px rgba(0, 0, 0, .18); }
        .page--sheet { width: 210mm; height: 297mm; padding: 23.5mm 15mm; display: grid; grid-template-columns: repeat(2, 90mm); grid-auto-rows: 50mm; align-content: start; }
        .page--sheet .card { outline: 0.1mm dashed #b9b2a8; }
        .page--single { width: 90mm; height: 50mm; }

        .card { width: 90mm; height: 50mm; overflow: hidden; display: flex; flex-direction: column; background: #fffdf8; }
        .card-top { height: 12.5mm; flex: none; display: flex; align-items: center; padding: 0 4.5mm; background: var(--t-header-bg); }
        .card-top img { height: 7.4mm; width: auto; display: block; }
        .card-body { flex: 1; display: flex; gap: 4mm; padding: 3mm 4.5mm; align-items: center; }
        .card-text { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 1.1mm; }
        .card-kicker { font-size: 2.3mm; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: var(--t-primary); }
        .card-name { font-weight: 800; line-height: 1.12; color: #2a1d15; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
        .card-hint { font-size: 2.35mm; line-height: 1.3; color: #5c534a; font-weight: 500; }
        .card-site { font-size: 2.6mm; font-weight: 800; color: #2a1d15; }
        .card-qr { position: relative; width: 22mm; height: 22mm; flex: none; }
        .card-qr svg { width: 100%; height: 100%; display: block; }
        .card-qr img { position: absolute; left: 50%; top: 50%; width: 5.4mm; height: 5.4mm; translate: -50% -50%; padding: .6mm; border-radius: 1.6mm; background: var(--t-header-bg); box-shadow: 0 0 0 .7mm #fff; object-fit: contain; }

        @media print {
            body { background: none; }
            .toolbar { display: none; }
            .pages { padding: 0; gap: 0; }
            .page { box-shadow: none; break-after: page; }
            .page:last-child { break-after: auto; }
        }
        @page { size: {{ $single ? '90mm 50mm' : 'A4' }}; margin: 0; }
    </style>
</head>
<body>
    <div class="toolbar">
        <b>QR-визитка{{ $cards->count() > 1 ? 'и: '.$cards->count() : ' — '.$cards->first()['place']->title }}</b>
        <a href="{{ route('qr.cards', ['places' => $ids], false) }}" @class(['is-current' => ! $single])>Лист A4{{ $cards->count() === 1 ? ' (10 штук)' : '' }}</a>
        <a href="{{ route('qr.cards', ['places' => $ids, 'single' => 1], false) }}" @class(['is-current' => $single])>По одной, 90×50 мм</a>
        @if ($cards->count() === 1)
            <a href="{{ route('qr.svg', $cards->first()['place'], false) }}">Скачать только QR (SVG)</a>
        @endif
        <button type="button" class="primary" onclick="window.print()">Печать / PDF</button>
        <small>В окне печати: масштаб 100 %, поля «Нет», колонтитулы выключить. «Сохранить как PDF» — файл для типографии.
            Код и ссылка на визитке ведут сразу на страницу объекта; переходы по визиткам видны в «Аналитике» с меткой qr.</small>
    </div>

    <div class="pages">
        @foreach ($pages as $page)
            <div class="page {{ $single ? 'page--single' : 'page--sheet' }}">
                @foreach ($page as $card)
                    @php($title = $card['place']->title)
                    <div class="card">
                        <div class="card-top"><img src="{{ $theme?->logoUrl() ?? '/img/logos/autumn-logo.png' }}" alt="Открой Шаманку"></div>
                        <div class="card-body">
                            <div class="card-text">
                                <div class="card-kicker">{{ $kickers[$card['place']->category] ?? 'Шаманка' }}</div>
                                <div class="card-name" style="font-size: {{ mb_strlen($title) <= 18 ? '4.6mm' : (mb_strlen($title) <= 30 ? '3.9mm' : '3.3mm') }}">{{ $title }}</div>
                                <div class="card-hint">Фото, цены и контакты — наведите камеру на QR-код</div>
                                <div class="card-site">{{ $card['label'] }}</div>
                            </div>
                            <div class="card-qr">
                                {!! $card['qr'] !!}
                                <img src="{{ $theme?->iconUrl() ?? '/img/logos/autumn-icon.png' }}" alt="">
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
</body>
</html>
