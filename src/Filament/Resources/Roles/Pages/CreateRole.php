<?php

namespace BartStrik\FilamentRolePermissions\Filament\Resources\Roles\Pages;

use BartStrik\FilamentRolePermissions\Filament\Resources\Roles\RoleResource;
use BartStrik\FilamentRolePermissions\Filament\Resources\Roles\Traits\SyncsRolePermissions;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    use SyncsRolePermissions;

    protected static string $resource = RoleResource::class;
}
