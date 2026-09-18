# Filament Role Permissions

Role-based access control for [Filament](https://filamentphp.com) with three levels of granularity:

- **Resource level** — which CRUD actions a role may perform per resource (`viewAny`, `view`, `create`, `update`, `delete`, `restore`, `forceDelete`).
- **Field level** — the access level of each form field, table column and infolist entry per role (`Mutable`, `Read Only`, `Fill Only`, `Hidden`), configured independently for the create form, the edit form, the table and the infolist.
- **Action level** — the visibility of header, row and bulk actions per role (`Visible`, `Disabled`, `Hidden`).

Each user is assigned a single role. Permissions are edited through a `Roles` resource that introspects every registered Filament resource, so new resources and fields appear automatically.

## Requirements

- PHP 8.4+
- Laravel 12 or 13
- Filament v5

## Installation

```bash
composer require bart2004/filament-dynamic-roles-and-permissions
```

The package migrations are loaded automatically. Apply them with:

```bash
php artisan migrate
```

They create the `roles`, `resource_permissions`, `field_permissions` and `action_permissions` tables and add a nullable `role_id` column to your `users` table.

### Publishing the migrations (optional)

If you want to customise the schema, publish the migrations into your own `database/migrations` directory:

```bash
php artisan vendor:publish --tag=filament-dynamic-roles-and-permissions-migrations
php artisan migrate
```

Once published, the package stops loading its bundled migrations, so the published copies are the single source of truth (no duplicate runs).

### Publishing the config (optional)

```bash
php artisan vendor:publish --tag=filament-dynamic-roles-and-permissions-config
```

## Setup

### 1. Register the plugin

Add the plugin to your panel provider:

```php
use BartStrik\FilamentDynamicRolesAndPermissions\FilamentDynamicRolesAndPermissionsPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugin(FilamentDynamicRolesAndPermissionsPlugin::make());
}
```

This registers the `Roles` resource in the panel.

### 2. Add the trait to your User model

```php
use BartStrik\FilamentDynamicRolesAndPermissions\Concerns\HasRolePermissions;

class User extends Authenticatable
{
    use HasRolePermissions;
}
```

The trait adds a `role()` relationship and an `isSuperAdmin()` method. Override `isSuperAdmin()` to grant unrestricted access:

```php
public function isSuperAdmin(): bool
{
    return $this->email === 'admin@example.com';
}
```

### 3. Enforce resource permissions with a policy

Field- and action-level permissions are enforced automatically at render time. To enforce **resource-level** permissions, point each model's policy at its Filament resource by extending the base policy:

```php
use BartStrik\FilamentDynamicRolesAndPermissions\Policies\ResourcePermissionPolicy;

class PostPolicy extends ResourcePermissionPolicy
{
    protected function resourceClass(): string
    {
        return \App\Filament\Resources\Posts\PostResource::class;
    }
}
```

## How it works

- **`ResourcePermission`**, **`FieldPermission`** and **`ActionPermission`** store the settings per role.
- **`PermissionCacheService`** caches each role's permissions forever and is cleared whenever a role is saved.
- **`Support\DynamicPermissions`** hooks into Filament's `Field`, `Column` and `Entry` configuration to apply the cached access levels when a component renders.

### Access levels

| Level       | Form field                                    | Table / Infolist |
|-------------|-----------------------------------------------|------------------|
| `Mutable`   | Editable                                       | Visible          |
| `Read Only` | Disabled (visible, not editable)               | Visible          |
| `Fill Only` | Editable on create, disabled on edit           | —                |
| `Hidden`    | Hidden                                          | Hidden           |

## Configuration

```php
// config/filament-dynamic-roles-and-permissions.php
return [
    'navigation' => [
        'group' => null, // Navigation group for the Roles resource
        'sort' => null,  // Navigation sort order
    ],
];
```

## Testing

```bash
composer test
```

## License

MIT
