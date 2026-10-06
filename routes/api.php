<?php

declare(strict_types=1);
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\PostController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:api')->group(function (): void {
    Route::get('/posts', [PostController::class, 'index']);
    Route::get('/posts/{post}', [PostController::class, 'show']);
    Route::get('/organizations', [CatalogController::class, 'organizations']);
    Route::get('/categories', [CatalogController::class, 'categories']);
    Route::get('/organizations/{organization}/posts', [PostController::class, 'organization']);
    Route::middleware(['auth:sanctum', 'abilities:posts:write'])->group(function (): void {
        Route::post('/organizations/{organization}/posts', [PostController::class, 'store']);
        Route::put('/organizations/{organization}/posts/{post}', [PostController::class, 'update']);
        Route::delete('/organizations/{organization}/posts/{post}', [PostController::class, 'destroy']);
    });
});
