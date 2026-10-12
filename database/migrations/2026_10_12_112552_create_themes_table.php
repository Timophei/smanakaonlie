<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * Сезонные темы сайта и шесть стартовых тем из спецификации оформления.
     */
    public function up(): void
    {
        Schema::create('themes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->json('tokens');
            $table->string('map_image')->nullable();
            $table->json('map_zones')->nullable();
            $table->string('starts_on', 5)->nullable();
            $table->string('ends_on', 5)->nullable();
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_forced')->default(false);
            $table->foreignId('night_theme_id')->nullable()->constrained('themes')->nullOnDelete();
            $table->string('night_from', 5)->nullable();
            $table->string('night_to', 5)->nullable();
            $table->integer('sort')->default(0);
            $table->timestamps();
        });

        foreach ($this->defaults() as $theme) {
            DB::table('themes')->insert([
                'slug' => $theme['slug'],
                'name' => $theme['name'],
                'tokens' => json_encode($theme['tokens']),
                'map_image' => $this->copyMap($theme['map']),
                'starts_on' => $theme['starts_on'],
                'ends_on' => $theme['ends_on'],
                'priority' => $theme['priority'],
                'sort' => $theme['sort'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $eveningId = DB::table('themes')->where('slug', 'winter-evening')->value('id');
        DB::table('themes')->where('slug', 'winter')->update([
            'night_theme_id' => $eveningId, 'night_from' => '17:00', 'night_to' => '08:00',
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('themes');
    }

    /**
     * Подложка карты кладётся в папку загрузок, чтобы её можно было заменить из админки.
     */
    private function copyMap(string $file): ?string
    {
        $source = public_path('img/maps/'.$file);
        if (! File::exists($source)) {
            return null;
        }

        $path = 'themes/'.$file;
        if (! Storage::disk('public')->exists($path)) {
            Storage::disk('public')->put($path, File::get($source));
        }

        return $path;
    }

    /**
     * @return array<int, array{slug: string, name: string, map: string, starts_on: ?string, ends_on: ?string, priority: int, sort: int, tokens: array<string, string>}>
     */
    private function defaults(): array
    {
        return [
            [
                'slug' => 'summer', 'name' => 'Лето', 'map' => '01-summer.jpg',
                'starts_on' => '06-01', 'ends_on' => '08-31', 'priority' => 10, 'sort' => 0,
                'tokens' => [
                    'page-bg' => '#F3F7EF',
                    'header-bg' => '#183F35',
                    'logo' => '#FFFFFF',
                    'heading' => '#183F35',
                    'text' => '#293B34',
                    'text-muted' => '#58695F',
                    'primary' => '#286447',
                    'on-primary' => '#FFFFFF',
                    'primary-hover' => '#1D5037',
                    'surface' => '#FFFFFF',
                    'border' => '#D7E3D5',
                    'footer-bg' => '#183F35',
                    'link' => '#286447',
                    'icon' => '#183F35',
                    'warning-bg' => '#FFF1D6',
                    'warning-text' => '#654319',
                ],
            ],
            [
                'slug' => 'autumn', 'name' => 'Осень', 'map' => '02-autumn.jpg',
                'starts_on' => '09-01', 'ends_on' => '10-31', 'priority' => 10, 'sort' => 1,
                'tokens' => [
                    'page-bg' => '#FBF4E8',
                    'header-bg' => '#513827',
                    'logo' => '#FFE3AC',
                    'heading' => '#513827',
                    'text' => '#44382F',
                    'text-muted' => '#756454',
                    'primary' => '#98451E',
                    'on-primary' => '#FFFFFF',
                    'primary-hover' => '#7D3617',
                    'surface' => '#FFFCF6',
                    'border' => '#E8D8BF',
                    'footer-bg' => '#513827',
                    'link' => '#98451E',
                    'icon' => '#513827',
                    'warning-bg' => '#FFF1D6',
                    'warning-text' => '#654319',
                ],
            ],
            [
                'slug' => 'winter', 'name' => 'Зима', 'map' => '03-winter.jpg',
                'starts_on' => '11-01', 'ends_on' => '03-31', 'priority' => 10, 'sort' => 2,
                'tokens' => [
                    'page-bg' => '#F6F8F3',
                    'header-bg' => '#214B40',
                    'logo' => '#FFFFFF',
                    'heading' => '#214B40',
                    'text' => '#34443D',
                    'text-muted' => '#66776F',
                    'primary' => '#A54D2C',
                    'on-primary' => '#FFFFFF',
                    'primary-hover' => '#873D22',
                    'surface' => '#FFFFFF',
                    'border' => '#D7E2DA',
                    'footer-bg' => '#214B40',
                    'link' => '#286447',
                    'icon' => '#286447',
                    'warning-bg' => '#FFF1D6',
                    'warning-text' => '#654319',
                    'ice-accent' => '#DDF2F8',
                    'secondary-button' => '#214B40',
                ],
            ],
            [
                'slug' => 'winter-evening', 'name' => 'Зимний вечер', 'map' => '04-winter-evening.jpg',
                'starts_on' => null, 'ends_on' => null, 'priority' => 0, 'sort' => 3,
                'tokens' => [
                    'page-bg' => '#152738',
                    'header-bg' => '#0F1E2D',
                    'logo' => '#F7DFB2',
                    'heading' => '#F1F6FB',
                    'text' => '#DFE8F1',
                    'text-muted' => '#A9BDCF',
                    'primary' => '#F0C780',
                    'on-primary' => '#182B3E',
                    'primary-hover' => '#FFDA99',
                    'surface' => '#20384D',
                    'border' => '#385268',
                    'footer-bg' => '#0F1E2D',
                    'link' => '#F0C780',
                    'icon' => '#F1F6FB',
                    'warning-bg' => '#493C27',
                    'warning-text' => '#FFE2AA',
                ],
            ],
            [
                'slug' => 'new-year', 'name' => 'Новый год', 'map' => '05-new-year.jpg',
                'starts_on' => '12-20', 'ends_on' => '01-10', 'priority' => 20, 'sort' => 4,
                'tokens' => [
                    'page-bg' => '#142E2B',
                    'header-bg' => '#0D211F',
                    'logo' => '#F4D28B',
                    'heading' => '#FFF2D6',
                    'text' => '#E5EEE8',
                    'text-muted' => '#AEC3B9',
                    'primary' => '#F0C777',
                    'on-primary' => '#142E2B',
                    'primary-hover' => '#FFDA91',
                    'surface' => '#203F38',
                    'border' => '#3B594D',
                    'footer-bg' => '#0D211F',
                    'link' => '#F0C777',
                    'icon' => '#FFF2D6',
                    'warning-bg' => '#493C27',
                    'warning-text' => '#FFE2AA',
                    'festive-red' => '#B9433E',
                ],
            ],
            [
                'slug' => 'spring', 'name' => 'Весна', 'map' => '06-spring.jpg',
                'starts_on' => '04-01', 'ends_on' => '05-31', 'priority' => 10, 'sort' => 5,
                'tokens' => [
                    'page-bg' => '#F4F8EE',
                    'header-bg' => '#294C3B',
                    'logo' => '#FFFFFF',
                    'heading' => '#294C3B',
                    'text' => '#324334',
                    'text-muted' => '#61725C',
                    'primary' => '#426D37',
                    'on-primary' => '#FFFFFF',
                    'primary-hover' => '#33562A',
                    'surface' => '#FFFFFF',
                    'border' => '#D9E5CF',
                    'footer-bg' => '#294C3B',
                    'link' => '#426D37',
                    'icon' => '#294C3B',
                    'warning-bg' => '#FFF1D6',
                    'warning-text' => '#654319',
                ],
            ],
        ];
    }
};
