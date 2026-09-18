<?php

namespace BartStrik\FilamentDynamicRolesAndPermissions\Filament\Resources\Roles\Pages;

use BartStrik\FilamentDynamicRolesAndPermissions\Filament\Resources\Roles\RoleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRoles extends ListRecords
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
