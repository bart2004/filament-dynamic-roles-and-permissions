<?php

namespace BartStrik\FilamentRolePermissions;

use BartStrik\FilamentRolePermissions\Support\DynamicPermissions;
use Illuminate\Support\ServiceProvider;

class FilamentRolePermissionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/filament-role-permissions.php',
            'filament-role-permissions',
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        DynamicPermissions::attach();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/filament-role-permissions.php' => config_path('filament-role-permissions.php'),
            ], 'filament-role-permissions-config');
        }
    }
}
