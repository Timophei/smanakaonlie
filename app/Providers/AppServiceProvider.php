<?php

namespace App\Providers;

use App\Support\ThemeResolver;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer(['site.layout', 'site.home', 'site.partials.map'], function (\Illuminate\View\View $view): void {
            $request = request();
            if (! $request->attributes->has('theme')) {
                $request->attributes->set('theme', app(ThemeResolver::class)->resolve($request->query('tema')));
            }
            $view->with('theme', $request->attributes->get('theme'));
        });
    }
}
