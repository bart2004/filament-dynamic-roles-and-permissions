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

### Translations

The Roles resource is translatable, in the same way Filament translates its own texts. The package ships `en` and `nl` and loads them under the `filament-dynamic-roles-and-permissions` namespace, so they follow your application locale (`APP_LOCALE`).

To change a text or add a language, publish the files into your application:

```bash
php artisan vendor:publish --tag=filament-dynamic-roles-and-permissions-translations
```

They end up in `lang/vendor/filament-dynamic-roles-and-permissions/{locale}`. You only need to keep the keys you want to change; everything else falls back to the package.

| File           | Contains                                                              |
|----------------|-----------------------------------------------------------------------|
| `resource.php` | Name of the resource, used in the navigation and as page titles       |
| `table.php`    | Column labels of the roles table                                      |
| `form.php`     | Labels, sections, permission levels, access levels, hints and messages |

The labels of the resources, fields, columns and actions in the permission editor come from your own application, so translate those there.

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

### 3. Resource permissions are enforced automatically

That's it — there's nothing to wire up per resource. Field- and action-level
permissions are enforced at render time, and **resource-level** abilities
(`viewAny`, `view`, `create`, `update`, `delete`, `restore`, `forceDelete`, plus
their bulk `*Any` variants) are authorized automatically for every Filament
resource from the current role's stored permissions.

Under the hood this registers a single `Gate::before` hook that maps each model
back to its Filament resource, so you no longer write a policy class per model.

#### Overriding a single resource

The automatic check steps aside for any model that already has its own policy
(registered explicitly, or discovered by Laravel's `App\Policies\{Model}Policy`
naming convention). So to customise one resource, just write a normal policy —
optionally extending the base policy to keep the role-based checks for the
abilities you don't override:

```php
use BartStrik\FilamentDynamicRolesAndPermissions\Policies\ResourcePermissionPolicy;

class PostPolicy extends ResourcePermissionPolicy
{
    protected function resourceClass(): string
    {
        return \App\Filament\Resources\Posts\PostResource::class;
    }

    // Override just the abilities you need; the rest stay role-driven.
    public function delete($user, $model): bool
    {
        return false;
    }
}
```

To turn the automatic behaviour off entirely and manage every policy yourself,
set `auto_register_policies` to `false` in the config.

## How it works

- **`ResourcePermission`**, **`FieldPermission`** and **`ActionPermission`** store the settings per role.
- **`PermissionCacheService`** caches each role's permissions forever and is cleared whenever a role is saved.
- **`Support\DynamicPermissions`** hooks into Filament's `Field`, `Column` and `Entry` configuration to apply the cached access levels when a component renders.
- **`Authorization\ResourcePermissionGate`** registers a `Gate::before` hook that authorizes the standard resource abilities for every Filament resource, deferring to any policy you've written yourself.

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

    // Authorize resource abilities automatically for every Filament resource.
    // Set to false to write and register every policy yourself.
    'auto_register_policies' => true,
];
```

## Testing

```bash
composer test
```

## License

MIT
