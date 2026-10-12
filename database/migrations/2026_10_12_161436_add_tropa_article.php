<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * Первая статья — «Тропа „Шаманка“» по материалам страницы /stati/tropa/ текущего сайта.
     * Заводится черновиком: текст пересказан, его стоит сверить с оригиналом и дополнить фото.
     */
    public function up(): void
    {
        if (DB::table('articles')->where('path', 'stati/tropa')->exists()) {
            return;
        }

        $cover = null;
        if (File::exists(public_path('img/route-tropa.jpg'))) {
            $cover = 'articles/covers/route-tropa.jpg';
            if (! Storage::disk('public')->exists($cover)) {
                Storage::disk('public')->put($cover, File::get(public_path('img/route-tropa.jpg')));
            }
        }

        $id = DB::table('articles')->insertGetId([
            'title' => 'Тропа «Шаманка»',
            'path' => 'stati/tropa',
            'status' => 'draft',
            'lead' => 'Главный маршрут по Шаманскому утёсу — на полдня или на целый день, с пикником по пути. Описываем круг против часовой стрелки: подъём короткий, но крутой.',
            'cover' => $cover,
            'facts' => json_encode([
                ['label' => 'Время', 'value' => 'полдня или целый день'],
                ['label' => 'Направление', 'value' => 'против часовой стрелки'],
                ['label' => 'Подъём', 'value' => 'короткий и крутой'],
            ], JSON_UNESCAPED_UNICODE),
            'content' => json_encode($this->blocks(), JSON_UNESCAPED_UNICODE),
            'seo_title' => 'Тропа «Шаманка» — маршрут на Шаманский утёс',
            'seo_description' => 'Маршрут на Шаманский утёс: от подвесного моста через Лес чудес к слиянию рек, подъём, смотровые площадки, спуск и советы перед походом.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tagId = DB::table('tags')->where('name', 'После тропы')->value('id');
        if ($tagId) {
            DB::table('article_tag')->insert(['article_id' => $id, 'tag_id' => $tagId]);
        }
    }

    public function down(): void
    {
        DB::table('articles')->where('path', 'stati/tropa')->delete();
    }

    /**
     * @return array<int, array{type: string, data: array<string, mixed>}>
     */
    private function blocks(): array
    {
        return [
            ['type' => 'text', 'data' => ['body' => '<p>Это главный маршрут по Шаманскому утёсу. Он рассчитан на полдня или на целый день, если взять с собой перекус и устроить пикник в одном из мест по пути. Описываем круг против часовой стрелки: так подъём выходит коротким, хоть и крутым.</p>']],
            ['type' => 'steps', 'data' => [
                'title' => 'Как идти',
                'items' => [
                    ['title' => 'От моста к Лесу чудес', 'time' => null, 'text' => 'От подвесного моста идите вниз по течению Иркута — по берегу или через улицы села. У паромной пристани есть магазин: это последняя возможность купить воду и перекус.', 'photo' => null],
                    ['title' => 'Лес чудес', 'time' => '20–30 минут', 'text' => 'Прогулка по соснам, берёзам и заливным лугам почти без перепадов высоты.', 'photo' => null],
                    ['title' => 'Поляна у слияния рек', 'time' => null, 'text' => 'Один из лучших видов на утёс и на место, где Шаманка впадает в Иркут. Удобно отдохнуть и сделать фото. Здесь же временный мостик через Шаманку.', 'photo' => null],
                    ['title' => 'Прижим и начало подъёма', 'time' => null, 'text' => 'После перехода через реку тропа идёт вдоль основания утёса. Подъёмов несколько: левые тропы короче и круче, правые длиннее и подходят большинству.', 'photo' => null],
                    ['title' => 'Подъём на вершину', 'time' => '20–30 минут', 'text' => 'Крутой подъём через лес, затем ещё 10–15 минут до верхней части утёса с панорамой на Шаманку и долину Иркута.', 'photo' => null],
                    ['title' => 'Вершина и смотровые площадки', 'time' => null, 'text' => 'Маршрут идёт вдоль края утёса: несколько площадок и выступов с видами, местами оборудованы места для отдыха.', 'photo' => null],
                    ['title' => 'Спуск', 'time' => null, 'text' => 'Большая часть спуска пологая, но ближе к концу есть крутой участок — особенно опасный после дождя. Пригодятся трекинговые палки.', 'photo' => null],
                    ['title' => 'Возвращение', 'time' => null, 'text' => 'От мостика — вдоль реки или по улицам Шаманки обратно к подвесному мосту.', 'photo' => null],
                ],
            ]],
            ['type' => 'callout', 'data' => [
                'kind' => 'warning',
                'title' => 'Перед выходом',
                'body' => '<ul><li><strong>Связь.</strong> На утёсе ловит хорошо, у подножия с дальней стороны есть слепые зоны — скачайте офлайн-карту.</li><li><strong>Клещи.</strong> Не заходите в кусты и высокую траву, особенно весной и в начале лета. Возьмите репеллент.</li><li><strong>Вода.</strong> На хребте чистых источников нет — берите минимум 1,5 литра питьевой воды.</li><li><strong>Безопасность.</strong> Скажите близким, куда идёте и когда планируете вернуться на связь.</li></ul>',
            ]],
            ['type' => 'places', 'data' => ['title' => 'После тропы — в баню', 'tag_id' => null, 'category' => 'bath', 'format' => 'cards', 'limit' => 3]],
        ];
    }
};
