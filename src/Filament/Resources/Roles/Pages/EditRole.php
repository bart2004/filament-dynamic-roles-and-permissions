<?php

namespace BartStrik\FilamentDynamicRolesAndPermissions\Filament\Resources\Roles\Pages;

use BartStrik\FilamentDynamicRolesAndPermissions\Filament\Resources\Roles\RoleResource;
use BartStrik\FilamentDynamicRolesAndPermissions\Filament\Resources\Roles\Traits\SyncsRolePermissions;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRole extends EditRecord
{
    use SyncsRolePermissions;

    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
