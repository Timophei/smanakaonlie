<?php

namespace Database\Seeders;

use App\Models\Amenity;
use App\Models\Place;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Справочники и четыре объекта жилья с текущего сайта (без цен и телефонов — их там нет).
     */
    public function run(): void
    {
        $amenities = ['Баня', 'Душ', 'Тёплый туалет', 'Кухня', 'Отопление зимой', 'Мангал или барбекю', 'Веранда',
            'Бассейн', 'Парковка', 'Wi‑Fi', 'Ловит сотовая связь', 'Можно с животными', 'Удобно с детьми'];
        foreach ($amenities as $i => $name) {
            Amenity::firstOrCreate(['name' => $name], ['sort' => $i]);
        }

        foreach (['После тропы', 'Для компании', 'С детьми', 'На выходные', 'Рядом с рекой'] as $name) {
            Tag::firstOrCreate(['name' => $name]);
        }

        $hotels = [
            ['Дом-музей «Уютные номера»', 'uslugi/gostinitsy/pervyyotel_xdwf_zcxe', 'room', 'Шаманка, ул. Зелёная, д. 2',
                'Дом-музей, баня и уютные номера.', ['Баня'], null, null],
            ['Гостевой домик «Шаманка»', 'uslugi/gostinitsy/DomikVShamanle', 'house', 'Шаманка, ул. Зелёная, д. 3',
                'Проживание, баня, надувной бассейн, спортивный инвентарь и настольные игры.', ['Баня', 'Бассейн', 'Можно с животными'], null, null],
            ['Домики с видом на утёс', 'uslugi/gostinitsy/pervyyotel_xdwf', 'cabin', 'Шаманка, ул. Солнечная, д. 6',
                'Аккуратные, уютные домики с верандами. Рядом Иркут и пешеходная тропа.', ['Мангал или барбекю', 'Душ', 'Веранда'], null, null],
            ['Гостевой дом «Добрый»', 'uslugi/gostinitsy/domikivSham', 'house', 'пос. Шаманка, Куйтунская ул., 5',
                'Большой двухэтажный дом для больших компаний на правом берегу Иркута.', [], 8, 38],
        ];

        foreach ($hotels as $i => [$title, $path, $type, $address, $short, $has, $min, $max]) {
            $place = Place::firstOrCreate(['path' => $path], [
                'category' => 'hotel', 'title' => $title, 'type' => $type, 'address' => $address,
                'short_description' => $short, 'status' => 'draft', 'sort' => $i,
                'guests_min' => $min, 'guests_max' => $max,
                'bank' => str_contains($short, 'правом берегу') ? 'right' : null,
            ]);
            $place->amenities()->syncWithoutDetaching(Amenity::whereIn('name', $has)->pluck('id'));
        }
    }
}
