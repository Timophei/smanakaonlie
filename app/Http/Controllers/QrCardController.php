<?php

namespace App\Http\Controllers;

use App\Models\Place;
use App\Support\QrCode;
use App\Support\ThemeResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * QR-визитки объектов для хозяев: страница печати (лист A4 по 10 штук или одна в размер 90×50 мм)
 * и отдельный QR-код в SVG. Код ведёт на страницу объекта с меткой utm_source=qr — переходы видны в аналитике.
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

    /** Адрес объекта на основном домене с меткой для аналитики. */
    public static function url(Place $place): string
    {
        return config('app.canonical_root').'/'.trim($place->path, '/').'/?utm_source=qr&utm_medium=card&utm_campaign='.rawurlencode(Str::afterLast($place->path, '/'));
    }
}
