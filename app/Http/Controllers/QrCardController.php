<?php

namespace App\Http\Controllers;

use App\Models\Place;
use App\Support\QrCode;
use App\Support\ThemeResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * QR-визитки объектов для хозяев: страница печати (лист A4 по 10 штук или одна в размер 90×50 мм)
 * и отдельный QR-код в SVG. Код — короткая ссылка /q/{id}, она ведёт на страницу объекта
 * с меткой utm_source=qr, поэтому переходы по визиткам видны в аналитике.
 */
class QrCardController extends Controller
{
    public function show(Request $request): View
    {
        $ids = collect(explode(',', (string) $request->query('places')))->map(fn ($id) => (int) $id)->filter()->unique();
        $places = Place::whereKey($ids)->get()->sortBy(fn (Place $place) => $ids->search($place->id))->values();
        abort_if($places->isEmpty(), 404);

        $cards = $places->map(fn (Place $place) => [
            'place' => $place,
            'url' => self::url($place),
            'label' => self::label($place),
            'qr' => QrCode::svg(self::url($place)),
        ]);

        return view('qr.cards', [
            'cards' => $cards,
            'single' => $request->boolean('single'),
            'theme' => app(ThemeResolver::class)->resolve(),
        ]);
    }

    public function svg(Place $place): Response
    {
        return response(QrCode::svg(self::url($place), 1000), 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => 'attachment; filename="qr-'.Str::slug($place->title).'.svg"',
        ]);
    }

    /**
     * Короткая ссылка для QR и печати: shamanka.online/q/7. Чем короче адрес,
     * тем меньше точек в коде — он мельче и легче считывается.
     */
    public static function url(Place $place): string
    {
        return config('app.canonical_root').'/q/'.$place->id;
    }

    /** Как ссылка выглядит на визитке — без https://. */
    public static function label(Place $place): string
    {
        return Str::after(self::url($place), '://');
    }

    /** Переход по короткой ссылке: сразу на страницу объекта, с меткой для аналитики. */
    public function go(Place $place): RedirectResponse
    {
        // RedirectResponse напрямую: redirect() срезает слэш в конце, а адреса страниц у нас со слэшем
        return new RedirectResponse('/'.trim($place->path, '/').'/?utm_source=qr&utm_medium=card&utm_campaign='.rawurlencode(Str::afterLast($place->path, '/')));
    }
}
