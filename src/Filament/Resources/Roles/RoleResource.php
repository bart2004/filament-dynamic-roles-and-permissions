<?php

namespace BartStrik\FilamentRolePermissions\Filament\Resources\Roles;

use BackedEnum;
use BartStrik\FilamentRolePermissions\Filament\Resources\Roles\Pages\CreateRole;
use BartStrik\FilamentRolePermissions\Filament\Resources\Roles\Pages\EditRole;
use BartStrik\FilamentRolePermissions\Filament\Resources\Roles\Pages\ListRoles;
use BartStrik\FilamentRolePermissions\Filament\Resources\Roles\Schemas\RoleForm;
use BartStrik\FilamentRolePermissions\Filament\Resources\Roles\Tables\RolesTable;
use BartStrik\FilamentRolePermissions\Models\Role;
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
        return config('filament-role-permissions.navigation.group');
    }

    public static function getNavigationSort(): ?int
    {
        return config('filament-role-permissions.navigation.sort');
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
