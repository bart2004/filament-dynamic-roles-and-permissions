<?php

namespace BartStrik\FilamentDynamicRolesAndPermissions\Filament\Resources\Roles;

use BackedEnum;
use BartStrik\FilamentDynamicRolesAndPermissions\Filament\Resources\Roles\Pages\CreateRole;
use BartStrik\FilamentDynamicRolesAndPermissions\Filament\Resources\Roles\Pages\EditRole;
use BartStrik\FilamentDynamicRolesAndPermissions\Filament\Resources\Roles\Pages\ListRoles;
use BartStrik\FilamentDynamicRolesAndPermissions\Filament\Resources\Roles\Schemas\RoleForm;
use BartStrik\FilamentDynamicRolesAndPermissions\Filament\Resources\Roles\Tables\RolesTable;
use BartStrik\FilamentDynamicRolesAndPermissions\Models\Role;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getNavigationGroup(): ?string
    {
        return config('filament-dynamic-roles-and-permissions.navigation.group');
    }

    public static function getNavigationSort(): ?int
    {
        return config('filament-dynamic-roles-and-permissions.navigation.sort');
    }

    public static function form(Schema $schema): Schema
    {
        return RoleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RolesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }
}
