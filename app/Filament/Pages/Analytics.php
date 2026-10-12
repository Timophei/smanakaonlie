<?php

namespace App\Filament\Pages;

use App\Models\Article;
use App\Models\CatalogPage;
use App\Models\ContactClick;
use App\Models\OwnerContact;
use App\Models\PageView;
use App\Models\Place;
use Carbon\CarbonImmutable;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Встроенная аналитика: посетители, источники, страницы, метки и нажатия на контакты.
 */
class Analytics extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Аналитика';

    protected static ?string $title = 'Аналитика';

    protected static ?string $slug = 'analitika';

    protected static ?int $navigationSort = -1;

    protected static string $view = 'filament.pages.analytics';

    /**
     * @var array<int, string>
     */
    public const PERIODS = [1 => 'Сегодня', 7 => '7 дней', 30 => '30 дней', 90 => '90 дней', 365 => 'Год'];

    public int $days = 30;

    public function setDays(int $days): void
    {
        $this->days = array_key_exists($days, self::PERIODS) ? $days : 30;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $today = CarbonImmutable::now(config('app.site_timezone'))->startOfDay();
        $from = $today->subDays($this->days - 1);
        $views = fn (): Builder => PageView::query()->where('day', '>=', $from->toDateString());
        $entries = fn (): Builder => $views()->where('is_entry', true);
        $uniqueVisitors = "count(distinct day || '-' || visitor)";

        $daily = $views()->selectRaw("day, count(*) as views, {$uniqueVisitors} as visitors")
            ->groupBy('day')->orderBy('day')->get()->keyBy('day');
        $series = collect(range(0, $this->days - 1))->map(function (int $offset) use ($from, $daily): array {
            $day = $from->addDays($offset)->toDateString();

            return ['day' => $day, 'views' => (int) ($daily[$day]->views ?? 0), 'visitors' => (int) ($daily[$day]->visitors ?? 0)];
        });

        $clicks = ContactClick::query()->where('created_at', '>=', $from->setTimezone('UTC'));

        return [
            'periods' => self::PERIODS,
            'totals' => [
                'Посетители' => (int) $views()->selectRaw("{$uniqueVisitors} as c")->value('c'),
                'Просмотры' => $views()->count(),
                'Заходы на сайт' => $entries()->count(),
                'Нажатия на контакты' => (clone $clicks)->count(),
            ],
            'series' => $series,
            'sources' => $this->rows($entries()->selectRaw("source, count(*) as entries, {$uniqueVisitors} as visitors")->groupBy('source'), 'entries')
                ->map(fn ($row) => [PageView::SOURCES[$row->source] ?? $row->source, $row->entries, $row->visitors]),
            'sourceNames' => $this->rows($entries()->whereNotIn('source', ['direct', 'internal'])
                ->selectRaw("source, source_name, count(*) as entries, {$uniqueVisitors} as visitors")->groupBy('source', 'source_name'), 'entries')
                ->map(fn ($row) => [$row->source_name, PageView::SOURCES[$row->source] ?? $row->source, $row->entries, $row->visitors]),
            'campaigns' => $this->rows($entries()->whereNotNull('utm_source')
                ->selectRaw("utm_source, utm_medium, utm_campaign, count(*) as entries, {$uniqueVisitors} as visitors")
                ->groupBy('utm_source', 'utm_medium', 'utm_campaign'), 'entries')
                ->map(fn ($row) => [$row->utm_source, $row->utm_medium ?: '—', $row->utm_campaign ?: '—', $row->entries, $row->visitors]),
            'pages' => $this->withTitles($this->rows($views()->selectRaw("path, count(*) as views, {$uniqueVisitors} as visitors")->groupBy('path'), 'views', 30))
                ->map(fn ($row) => [$row->title, $row->path, $row->views, $row->visitors]),
            'landings' => $this->withTitles($this->rows($entries()->selectRaw("path, count(*) as entries, {$uniqueVisitors} as visitors")->groupBy('path'), 'entries', 15))
                ->map(fn ($row) => [$row->title, $row->path, $row->entries, $row->visitors]),
            'devices' => $this->rows($views()->selectRaw("device, {$uniqueVisitors} as visitors, count(*) as views")->groupBy('device'), 'visitors')
                ->map(fn ($row) => [PageView::DEVICES[$row->device] ?? $row->device, $row->visitors, $row->views]),
            'contacts' => $clicks->select('place_id', 'kind', DB::raw('count(*) as clicks'))->groupBy('place_id', 'kind')
                ->with('place:id,title')->orderByDesc('clicks')->get()
                ->map(fn (ContactClick $row) => [$row->place?->title ?? '—', $row->kind === 'phone' ? 'Телефон' : (OwnerContact::TYPES[$row->kind] ?? $row->kind), $row->clicks]),
        ];
    }

    /**
     * Самые частые строки отчёта.
     *
     * @return Collection<int, object>
     */
    private function rows(Builder $query, string $orderBy, int $limit = 20): Collection
    {
        return $query->toBase()->orderByDesc($orderBy)->limit($limit)->get();
    }

    /**
     * Подставляет к адресам страниц их заголовки из статей, объектов и разделов.
     *
     * @param  Collection<int, object>  $rows
     * @return Collection<int, object>
     */
    private function withTitles(Collection $rows): Collection
    {
        $paths = $rows->pluck('path')->map(fn (string $path) => trim($path, '/'))->all();
        $titles = Article::whereIn('path', $paths)->pluck('title', 'path')
            ->merge(Place::whereIn('path', $paths)->pluck('title', 'path'))
            ->merge(CatalogPage::whereIn('path', $paths)->pluck('h1', 'path'));

        return $rows->map(function (object $row) use ($titles): object {
            $row->title = $row->path === '/' ? 'Главная' : ($titles[trim($row->path, '/')] ?? $row->path);

            return $row;
        });
    }
}
