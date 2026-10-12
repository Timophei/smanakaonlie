<?php

namespace App\Support;

use App\Models\Theme;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

/**
 * Выбирает тему сайта на текущий момент.
 *
 * Порядок: просмотр по ссылке ?tema=… → тема, включённая вручную → тема, в период которой
 * попадает сегодняшний день (при пересечении побеждает больший приоритет) → первая активная.
 * Если у выбранной темы задан ночной вариант и сейчас ночь по её часам, берётся он.
 * Время считается по часовому поясу сайта (Иркутск).
 */
class ThemeResolver
{
    public function resolve(?string $preview = null, ?Carbon $now = null): ?Theme
    {
        if (! Schema::hasTable('themes')) {
            return null;
        }

        $now ??= Carbon::now(config('app.site_timezone'));

        if ($preview && $theme = Theme::where('slug', $preview)->first()) {
            return $theme;
        }

        $themes = Theme::where('is_active', true)->orderByDesc('priority')->orderBy('sort')->get();

        $theme = $themes->firstWhere('is_forced', true)
            ?? $themes->first(fn (Theme $theme): bool => $theme->coversDate($now))
            ?? $themes->first();

        if ($theme && $theme->isNightAt($now) && $theme->nightTheme) {
            return $theme->nightTheme;
        }

        return $theme;
    }
}
