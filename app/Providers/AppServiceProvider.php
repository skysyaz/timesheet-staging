<?php

namespace App\Providers;

use App\Auth\Http\Responses\LoginResponse as AppLoginResponse;
use App\Console\Commands\RouteListCommand;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Illuminate\Foundation\Console\RouteListCommand as FrameworkRouteListCommand;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LoginResponse::class, AppLoginResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->singleton(
            FrameworkRouteListCommand::class,
            fn ($app) => new RouteListCommand($app['router']),
        );
    }
}
