<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Чистка перенесённого со старого сайта: дубль «Доброго» скрыт (адрес ведёт на основную страницу),
 * кавычки «ёлочками», латинские буквы-двойники внутри русских слов, эмодзи в кратких описаниях,
 * опечатки в заголовках. Меняется только то, что осталось как при переносе — правки из админки не трогаются.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('places')->where('path', 'uslugi/gostinitsy/pervyyotel_xdwf_zcxe_sirf_ctor')->update(['status' => 'hidden']);
        DB::table('places')->where('path', 'uslugi/gostinitsy/pervyyotel_xdwf_zcxe')
            ->where('short_description', 'Шаманка. Зеленая д.2')->update(['short_description' => null]);

        foreach (DB::table('places')->get(['id', 'title', 'short_description', 'description']) as $place) {
            $changes = array_filter([
                'title' => $this->quotes($this->homoglyphs($place->title)),
                'short_description' => $place->short_description === null ? null : trim($this->withoutEmoji($this->homoglyphs($place->short_description))),
                'description' => $place->description === null ? null : $this->homoglyphs($place->description),
            ], fn ($value, $key) => $value !== null && $value !== $place->{$key}, ARRAY_FILTER_USE_BOTH);
            if ($changes) {
                DB::table('places')->where('id', $place->id)->update($changes);
            }
        }
        foreach (DB::table('articles')->get(['id', 'title']) as $article) {
            DB::table('articles')->where('id', $article->id)->update(['title' => $this->quotes($article->title)]);
        }

        $titles = [
            'places' => [
                'Баня у реки «ШАманка»' => 'Баня у реки «Шаманка»',
                'Продукты и цветы у МаринЫ' => 'Продукты и цветы у Марины',
                'Гостевой дом добрый' => 'Гостевой дом «Добрый»',
            ],
            'articles' => [
                'ЛЕС ЧУДЕС И ПОЛЯНА' => 'Лес чудес и поляна',
                'Как Добраться до Шаманки.' => 'Как добраться до Шаманки',
            ],
        ];
        foreach ($titles as $table => $map) {
            foreach ($map as $old => $new) {
                DB::table($table)->where('title', $old)->update(['title' => $new]);
            }
        }
        DB::table('catalog_pages')->where('h1', 'Жилье')->update(['h1' => 'Жильё']);
        DB::table('catalog_pages')->where('h1', 'Активности на реке,')->update(['h1' => 'Активности на реке']);
    }

    /** "Текст" → «Текст» */
    private function quotes(string $text): string
    {
        return preg_replace('/"([^"]+)"/u', '«$1»', $text);
    }

    /** Латинские a, e, o, p, c, x, y… внутри русских слов (так прячут текст от поиска) → кириллица */
    private function homoglyphs(string $text): string
    {
        $map = ['a' => 'а', 'e' => 'е', 'o' => 'о', 'p' => 'р', 'c' => 'с', 'x' => 'х', 'y' => 'у',
            'A' => 'А', 'B' => 'В', 'C' => 'С', 'E' => 'Е', 'H' => 'Н', 'K' => 'К', 'M' => 'М', 'O' => 'О', 'P' => 'Р', 'T' => 'Т', 'X' => 'Х'];

        return preg_replace_callback('/\p{L}+/u', function (array $match) use ($map): string {
            $word = $match[0];
            if (! preg_match('/\p{Cyrillic}/u', $word) || ! preg_match('/[A-Za-z]/', $word) || preg_match('/[^aeopcxyABCEHKMOPTX\p{Cyrillic}]/u', $word)) {
                return $word;
            }

            return strtr($word, $map);
        }, $text);
    }

    private function withoutEmoji(string $text): string
    {
        return preg_replace('/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{FE0F}\x{200D}]/u', '', $text);
    }

    public function down(): void
    {
        //
    }
};
