<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
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
        //apenas admins
        Gate::define('admin-actions', function (User $user) {
            return ($user->type ?? 'P') === 'A';
        });
        //apenas jogadores
        Gate::define('player-actions', function (User $user) {
            return ($user->type ?? 'P') === 'P';
        });
    }
}
