<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void {}

    public function boot(): void
    {
        Paginator::defaultView('pagination');
        RateLimiter::for('login', fn (Request $request): array => [Limit::perMinute(5)->by(mb_strtolower($request->string('email')->toString()).'|'.($request->ip() ?? 'unknown')), Limit::perMinute(30)->by($request->ip() ?? 'unknown')]);
        RateLimiter::for('api', function (Request $request): Limit {
            $user = $request->user();

            return Limit::perMinute(120)->by($user instanceof User ? (string) $user->id : ($request->ip() ?? 'unknown'));
        });
    }
}
