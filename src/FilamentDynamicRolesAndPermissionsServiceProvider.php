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
        DynamicPermissions::attach();

        // Load the bundled migrations automatically for zero-config installs. If the
        // consumer has published their own copies to customise the schema, skip this
        // so the same migrations do not run twice.
        if (! $this->migrationsHaveBeenPublished()) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/filament-dynamic-roles-and-permissions.php' => config_path('filament-dynamic-roles-and-permissions.php'),
            ], 'filament-dynamic-roles-and-permissions-config');

            $this->publishesMigrations([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'filament-dynamic-roles-and-permissions-migrations');
        }
    }

    /**
     * Whether the consumer has already published this package's migrations into
     * their own migrations directory.
     */
    protected function migrationsHaveBeenPublished(): bool
    {
        return ! empty(glob(database_path('migrations/*_create_resource_permissions_table.php')));
    }
}
