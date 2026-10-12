<?php

namespace App\Filament\Pages;

use App\Models\MapZone;
use App\Support\ThemeResolver;
use Filament\Actions\Action;
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
        $this->form->fill(['zones' => MapZone::forMap()]);
    }

    public function form(Form $form): Form
    {
        return $form->statePath('data')->schema([
            ViewField::make('zones')->hiddenLabel()->view('filament.forms.map-zones-editor')->viewData([
                'image' => app(ThemeResolver::class)->resolve()?->mapUrl() ?? config('map.image'),
                'width' => config('map.width'),
                'height' => config('map.height'),
                'nullable' => false,
            ]),
        ]);
    }

    /**
     * @return array<int, Action>
     */
    protected function getFormActions(): array
    {
        return [Action::make('save')->label('Сохранить зоны')->submit('save')];
    }

    public function save(): void
    {
        $submitted = $this->form->getState()['zones'] ?? [];
        $zones = MapZone::sanitize($submitted);
        MapZone::replaceAll($zones);
        $this->form->fill(['zones' => MapZone::forMap()]);

        $skipped = count($submitted) - count($zones);
        Notification::make()->success()
            ->title('Зоны сохранены: '.count($zones))
            ->body($skipped ? "Не сохранено зон без названия или с нехваткой точек: {$skipped}." : 'На сайте уже новые контуры. У тем со своими контурами — их собственные.')
            ->send();
    }
}
