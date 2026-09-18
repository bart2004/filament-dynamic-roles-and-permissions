<?php

namespace BartStrik\FilamentDynamicRolesAndPermissions;

use BartStrik\FilamentDynamicRolesAndPermissions\Filament\Resources\Roles\RoleResource;
use Filament\Contracts\Plugin;
use Filament\Panel;

class FilamentDynamicRolesAndPermissionsPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'filament-dynamic-roles-and-permissions';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            RoleResource::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
