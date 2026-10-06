<?php

declare(strict_types=1);
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\ModerationController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\PostController;
use Illuminate\Support\Facades\Route;

Route::get('/', [FeedController::class, 'index'])->name('feed');
Route::get('/organisationen/{organization}', [FeedController::class, 'organization'])->name('organization.feed');
Route::get('/veranstaltungen', [FeedController::class, 'calendar'])->name('calendar');
Route::get('/feed.xml', [FeedController::class, 'rss'])->name('rss');
Route::post('/beitraege/{post}/melden', [FeedController::class, 'report'])->middleware('throttle:5,1')->name('reports.store');
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::get('/registrieren', [AuthController::class, 'registerForm'])->name('register');
    Route::post('/registrieren', [AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::get('/passwort/vergessen', [AuthController::class, 'forgotForm'])->name('password.request');
    Route::post('/passwort/vergessen', [AuthController::class, 'forgot'])->middleware('throttle:3,1')->name('password.email');
    Route::get('/passwort/{token}', [AuthController::class, 'resetForm'])->name('password.reset');
    Route::post('/passwort', [AuthController::class, 'reset'])->middleware('throttle:5,1')->name('password.update');
});
Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/redaktion', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/redaktion/tokens', [DashboardController::class, 'token'])->middleware('throttle:10,1')->name('tokens.store');
    Route::delete('/redaktion/tokens/{token}', [DashboardController::class, 'revoke'])->name('tokens.destroy');
    Route::get('/redaktion/organisationen/neu', [OrganizationController::class, 'create'])->name('organizations.create');
    Route::post('/redaktion/organisationen', [OrganizationController::class, 'store'])->name('organizations.store');
    Route::get('/redaktion/{organization}/einstellungen', [OrganizationController::class, 'edit'])->name('organizations.edit');
    Route::put('/redaktion/{organization}', [OrganizationController::class, 'update'])->name('organizations.update');
    Route::post('/redaktion/{organization}/mitglieder', [OrganizationController::class, 'member'])->name('members.store');
    Route::delete('/redaktion/{organization}/mitglieder/{user}', [OrganizationController::class, 'removeMember'])->name('members.destroy');
    Route::get('/redaktion/{organization}/beitraege', [PostController::class, 'index'])->name('posts.index');
    Route::get('/redaktion/{organization}/beitraege/neu', [PostController::class, 'create'])->name('posts.create');
    Route::post('/redaktion/{organization}/beitraege', [PostController::class, 'store'])->name('posts.store');
    Route::get('/redaktion/{organization}/beitraege/{post}/bearbeiten', [PostController::class, 'edit'])->name('posts.edit');
    Route::put('/redaktion/{organization}/beitraege/{post}', [PostController::class, 'update'])->name('posts.update');
    Route::delete('/redaktion/{organization}/beitraege/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
    Route::get('/moderation', [ModerationController::class, 'index'])->name('moderation');
    Route::put('/moderation/organisationen/{organization}', [ModerationController::class, 'verify'])->name('moderation.verify');
    Route::put('/moderation/beitraege/{post}', [ModerationController::class, 'hide'])->name('moderation.hide');
    Route::put('/moderation/meldungen/{report}', [ModerationController::class, 'resolve'])->name('moderation.resolve');
});
