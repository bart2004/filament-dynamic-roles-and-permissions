<?php

namespace BartStrik\FilamentDynamicRolesAndPermissions\Filament\Resources\Roles\Pages;

use BartStrik\FilamentDynamicRolesAndPermissions\Filament\Resources\Roles\RoleResource;
use BartStrik\FilamentDynamicRolesAndPermissions\Filament\Resources\Roles\Traits\SyncsRolePermissions;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    use SyncsRolePermissions;

    protected static string $resource = RoleResource::class;
}
