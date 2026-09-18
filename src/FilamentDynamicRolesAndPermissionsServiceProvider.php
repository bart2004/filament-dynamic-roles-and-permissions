<?php

namespace BartStrik\FilamentDynamicRolesAndPermissions;

use BartStrik\FilamentDynamicRolesAndPermissions\Support\DynamicPermissions;
use Illuminate\Support\ServiceProvider;

class FilamentDynamicRolesAndPermissionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/filament-dynamic-roles-and-permissions.php',
            'filament-dynamic-roles-and-permissions',
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        DynamicPermissions::attach();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/filament-dynamic-roles-and-permissions.php' => config_path('filament-dynamic-roles-and-permissions.php'),
            ], 'filament-dynamic-roles-and-permissions-config');
        }
    }
}
