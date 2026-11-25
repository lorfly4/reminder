<?php

use App\Http\Controllers\Web\AuthPageController;
use App\Http\Controllers\Web\PageController;
use Illuminate\Support\Facades\Route;

Route::get('/manifest.webmanifest', fn () => response()->file(public_path('manifest.webmanifest'), [
    'Content-Type' => 'application/manifest+json',
]));
Route::get('/sw.js', fn () => response()->file(public_path('sw.js'), [
    'Content-Type' => 'application/javascript',
]));
Route::get('/icons/{name}', fn (string $name) => response()->file(public_path('icons/'.$name), [
    'Content-Type' => 'image/png',
]))->where('name', 'icon-(192|512)\\.png');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthPageController::class, 'login'])->name('login');
    Route::post('/login', [AuthPageController::class, 'authenticate'])->name('login.store');
    Route::get('/register', [AuthPageController::class, 'register'])->name('register');
    Route::post('/register', [AuthPageController::class, 'store'])->name('register.store');
    Route::get('/forgot-password', [AuthPageController::class, 'forgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthPageController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthPageController::class, 'resetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthPageController::class, 'updatePassword'])->name('password.update');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthPageController::class, 'logout'])->name('logout');

    Route::get('/', [PageController::class, 'dashboard'])->name('dashboard');
    Route::get('/tasks', [PageController::class, 'tasks'])->name('tasks.index');
    Route::post('/tasks', [PageController::class, 'storeTask'])->name('tasks.store');
    Route::patch('/tasks/{task}/complete', [PageController::class, 'completeTask'])->name('tasks.complete');
    Route::patch('/tasks/{task}/reopen', [PageController::class, 'reopenTask'])->name('tasks.reopen');
    Route::patch('/tasks/{task}/archive', [PageController::class, 'archiveTask'])->name('tasks.archive');
    Route::delete('/tasks/{task}', [PageController::class, 'deleteTask'])->name('tasks.destroy');
    Route::put('/tasks/{task}', [PageController::class, 'updateTask'])->name('tasks.update');

    Route::post('/categories', [PageController::class, 'storeCategory'])->name('categories.store');
    Route::put('/categories/{category}', [PageController::class, 'updateCategory'])->name('categories.update');
    Route::delete('/categories/{category}', [PageController::class, 'deleteCategory'])->name('categories.destroy');

    Route::get('/calendar', [PageController::class, 'calendar'])->name('calendar');
    Route::get('/reminders', [PageController::class, 'reminders'])->name('reminders.index');
    Route::post('/reminders', [PageController::class, 'storeReminder'])->name('reminders.store');
    Route::patch('/reminders/{reminder}/toggle', [PageController::class, 'toggleReminder'])->name('reminders.toggle');
    Route::post('/reminders/{reminder}/snooze', [PageController::class, 'snoozeReminder'])->name('reminders.snooze');
    Route::delete('/reminders/{reminder}', [PageController::class, 'deleteReminder'])->name('reminders.destroy');

    Route::get('/settings', [PageController::class, 'settings'])->name('settings');
    Route::put('/settings', [PageController::class, 'updateSettings'])->name('settings.update');
    Route::get('/settings/export', [PageController::class, 'exportSettings'])->name('settings.export');
    Route::delete('/settings/account', [PageController::class, 'deleteAccount'])->name('settings.account.delete');
});
