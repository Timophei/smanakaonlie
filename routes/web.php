<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\QrCardController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SiteController::class, 'home'])->name('home');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/uslugi/homes', [SiteController::class, 'hotels'])->name('hotels');

// QR-визитки объектов — только для вошедшего админа
Route::middleware('auth')->group(function () {
    Route::get('/admin-print/qr', [QrCardController::class, 'show'])->name('qr.cards');
    Route::get('/admin-print/qr/{place}.svg', [QrCardController::class, 'svg'])->name('qr.svg');
});

Route::post('/a/hit', [AnalyticsController::class, 'hit'])->middleware('throttle:120,1')->name('analytics.hit');

Route::middleware('throttle:30,1')->group(function () {
    Route::post('/kontakt/{place}/telefon', [SiteController::class, 'phone'])->name('place.phone');
    Route::get('/kontakt/{place}/{contact}', [SiteController::class, 'contact'])->name('place.contact');
});

// Страницы объектов живут по своим адресам из админки (старые адреса сайта сохраняются).
// Маршрут стоит последним, чтобы не перехватывать остальные.
Route::get('/{path}', [SiteController::class, 'page'])
    ->where('path', '^(?!admin|livewire|storage|up$)[A-Za-z0-9_\-\/]+$')
    ->name('place');
