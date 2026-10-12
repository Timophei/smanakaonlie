<?php

use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SiteController::class, 'home'])->name('home');
Route::get('/uslugi/homes', [SiteController::class, 'hotels'])->name('hotels');

Route::middleware('throttle:30,1')->group(function () {
    Route::post('/kontakt/{place}/telefon', [SiteController::class, 'phone'])->name('place.phone');
    Route::get('/kontakt/{place}/{contact}', [SiteController::class, 'contact'])->name('place.contact');
});

// Страницы объектов живут по своим адресам из админки (старые адреса сайта сохраняются).
// Маршрут стоит последним, чтобы не перехватывать остальные.
Route::get('/{path}', [SiteController::class, 'page'])
    ->where('path', '^(?!admin|livewire|storage|up$)[A-Za-z0-9_\-\/]+$')
    ->name('place');
