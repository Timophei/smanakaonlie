<x-filament-panels::page>
    <style>
        .an-periods { display: flex; flex-wrap: wrap; gap: 8px; }
        .an-totals { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(160px, 45%), 1fr)); gap: 12px; }
        .an-total { padding: 16px; border-radius: 12px; border: 1px solid rgba(128,128,128,.25); }
        .an-total b { display: block; font-size: 28px; line-height: 1.2; }
        .an-total span { font-size: 13px; opacity: .7; }
        .an-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(380px, 100%), 1fr)); gap: 16px; align-items: start; }
        .an-table { width: 100%; border-collapse: collapse; font-size: 14px; }
        .an-table th { text-align: left; font-weight: 600; opacity: .6; font-size: 12px; padding: 6px 8px; }
        .an-table td { padding: 6px 8px; border-top: 1px solid rgba(128,128,128,.15); vertical-align: top; }
        .an-table .num { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
        .an-bar { height: 4px; border-radius: 2px; background: rgb(var(--primary-500)); margin-top: 4px; }
        .an-path { display: block; font-size: 12px; opacity: .55; word-break: break-all; }
        .an-chart { display: flex; align-items: flex-end; gap: 2px; height: 140px; }
        .an-chart div { flex: 1; min-width: 2px; background: rgb(var(--primary-500)); border-radius: 2px 2px 0 0; position: relative; }
        .an-chart div span { position: absolute; inset: auto 0 0 0; background: rgb(var(--primary-700)); border-radius: 2px 2px 0 0; }
        .an-axis { display: flex; justify-content: space-between; font-size: 12px; opacity: .6; margin-top: 6px; }
        .an-legend { display: flex; gap: 16px; font-size: 12px; opacity: .8; margin-bottom: 8px; }
        .an-legend i { display: inline-block; width: 10px; height: 10px; border-radius: 2px; margin-right: 4px; vertical-align: -1px; }
        .an-empty { opacity: .6; font-size: 14px; }
    </style>

    <div class="an-periods">
        @foreach ($periods as $value => $label)
            <x-filament::button size="sm" :color="$days === $value ? 'primary' : 'gray'" wire:click="setDays({{ $value }})">{{ $label }}</x-filament::button>
        @endforeach
    </div>

    <div class="an-totals">
        @foreach ($totals as $label => $value)
            <div class="an-total"><b>{{ number_format($value, 0, ',', ' ') }}</b><span>{{ $label }}</span></div>
        @endforeach
    </div>

    @if ($days > 1)
        <x-filament::section heading="По дням">
            @php($max = max(1, $series->max('views')))
            <div class="an-legend"><span><i style="background: rgb(var(--primary-500))"></i>Просмотры</span><span><i style="background: rgb(var(--primary-700))"></i>Посетители</span></div>
            <div class="an-chart">
                @foreach ($series as $point)
                    <div style="height: {{ round($point['views'] / $max * 100, 1) }}%" title="{{ \Illuminate\Support\Carbon::parse($point['day'])->format('d.m') }}: посетителей {{ $point['visitors'] }}, просмотров {{ $point['views'] }}">
                        <span style="height: {{ $point['views'] ? round($point['visitors'] / $point['views'] * 100, 1) : 0 }}%"></span>
                    </div>
                @endforeach
            </div>
            <div class="an-axis"><span>{{ \Illuminate\Support\Carbon::parse($series->first()['day'])->format('d.m') }}</span><span>{{ \Illuminate\Support\Carbon::parse($series->last()['day'])->format('d.m') }}</span></div>
        </x-filament::section>
    @endif

    <div class="an-grid">
        @include('filament.pages.analytics-table', ['heading' => 'Источники', 'description' => 'Откуда приходят на сайт', 'columns' => ['Источник', 'Заходы', 'Посетители'], 'rows' => $sources])
        @include('filament.pages.analytics-table', ['heading' => 'Сайты и сервисы', 'description' => 'Поисковики, соцсети, сайты со ссылками на вас', 'columns' => ['Откуда', 'Вид', 'Заходы', 'Посетители'], 'rows' => $sourceNames])
        @include('filament.pages.analytics-table', ['heading' => 'Метки (UTM)', 'description' => 'Ссылки вида ?utm_source=vk&utm_campaign=leto', 'columns' => ['utm_source', 'utm_medium', 'utm_campaign', 'Заходы', 'Посет.'], 'rows' => $campaigns])
        @include('filament.pages.analytics-table', ['heading' => 'Устройства', 'description' => null, 'columns' => ['Устройство', 'Посетители', 'Просмотры'], 'rows' => $devices])
        @include('filament.pages.analytics-table', ['heading' => 'Популярные страницы', 'description' => null, 'columns' => ['Страница', 'Просмотры', 'Посетители'], 'rows' => $pages, 'withPath' => true])
        @include('filament.pages.analytics-table', ['heading' => 'Страницы входа', 'description' => 'С каких страниц начинают знакомство с сайтом', 'columns' => ['Страница', 'Заходы', 'Посетители'], 'rows' => $landings, 'withPath' => true])
        @include('filament.pages.analytics-table', ['heading' => 'Нажатия на контакты', 'description' => '«Показать телефон» и «Написать»', 'columns' => ['Объект', 'Куда', 'Нажатий'], 'rows' => $contacts, 'numbers' => 1])
    </div>

    <p class="an-empty">Считаются посетители shamanka.online, кроме поисковых роботов и вас, пока вы вошли в админку. IP-адреса не сохраняются.</p>
</x-filament-panels::page>
