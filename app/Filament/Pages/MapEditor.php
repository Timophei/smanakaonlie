<?php

namespace App\Filament\Pages;

use App\Models\MapAnchor;
use App\Models\MapZone;
use App\Support\MapProjection;
use App\Support\ThemeResolver;
use Filament\Actions\Action;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Общие зоны рисованной карты: рисуются мышью или пальцем поверх картинки.
 *
 * @property Form $form
 */
class MapEditor extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationGroup = 'Оформление';

    protected static ?string $navigationLabel = 'Карта';

    protected static ?string $title = 'Зоны на карте';

    protected static ?string $slug = 'karta';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.map-editor';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(['zones' => MapZone::forMap(), 'anchors' => MapAnchor::forEditor()]);
    }

    public function form(Form $form): Form
    {
        $editorData = [
            'image' => app(ThemeResolver::class)->resolve()?->mapUrl() ?? config('map.image'),
            'width' => config('map.width'),
            'height' => config('map.height'),
        ];

        return $form->statePath('data')->schema([
            Tabs::make()->persistTabInQueryString()->tabs([
                Tabs\Tab::make('Зоны')->schema([
                    ViewField::make('zones')->hiddenLabel()->view('filament.forms.map-zones-editor')
                        ->viewData([...$editorData, 'nullable' => false]),
                ]),
                Tabs\Tab::make('Привязка к местности')->schema([
                    ViewField::make('anchors')->hiddenLabel()->view('filament.forms.map-anchors-editor')->viewData($editorData),
                ]),
            ]),
        ]);
    }

    /**
     * @return array<int, Action>
     */
    protected function getFormActions(): array
    {
        return [Action::make('save')->label('Сохранить')->submit('save')];
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $submitted = $state['zones'] ?? [];
        $zones = MapZone::sanitize($submitted);
        MapZone::replaceAll($zones);
        $anchors = MapAnchor::sanitize($state['anchors'] ?? []);
        MapAnchor::replaceAll($anchors);
        $this->form->fill(['zones' => MapZone::forMap(), 'anchors' => MapAnchor::forEditor()]);

        $skipped = count($submitted) - count($zones);
        $projection = MapProjection::fromAnchors($anchors);
        $worst = $projection ? max($projection->errors($anchors) ?: [0]) : null;

        Notification::make()->success()
            ->title('Сохранено: зон '.count($zones).', точек привязки '.count($anchors))
            ->body(implode(' ', array_filter([
                $skipped ? "Не сохранено зон без названия или с нехваткой точек: {$skipped}." : null,
                match (true) {
                    count($anchors) < 3 => 'Для «Где я?» нужно минимум 3 точки привязки, лучше 4–6 по краям карты.',
                    $projection === null => 'Точки привязки лежат на одной линии — расставьте их по разным краям карты.',
                    default => "Наибольшее расхождение точки с расчётом — {$worst} px.".($worst > 40 ? ' Многовато: проверьте координаты точки с наибольшим расхождением.' : ''),
                },
            ])))
            ->send();
    }
}
