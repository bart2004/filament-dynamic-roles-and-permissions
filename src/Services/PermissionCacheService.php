<?php

namespace BartStrik\FilamentDynamicRolesAndPermissions\Services;

use BartStrik\FilamentDynamicRolesAndPermissions\Models\Role;
use Illuminate\Support\Facades\Cache;

class PermissionCacheService
{
    public function getFieldAccessLevel(int $roleId, string $resourceClass, string $componentType, string $fieldName): string
    {
        $permissions = $this->getCachedFieldPermissions($roleId);

        return $permissions[$resourceClass][$componentType][$fieldName] ?? 'mutable';
    }

    public function getActionAccess(int $roleId, string $resourceClass, string $pageName, string $actionName): string
    {
        $permissions = $this->getCachedActionPermissions($roleId);

        return $permissions[$resourceClass][$pageName][$actionName] ?? 'visible';
    }

    public function hasResourceAction(int $roleId, string $resourceClass, string $action): bool
    {
        $permissions = $this->getCachedResourcePermissions($roleId);

        return in_array($action, $permissions[$resourceClass] ?? [], true);
    }

    public function getCachedResourcePermissions(int $roleId): array
    {
        return Cache::rememberForever("role_{$roleId}_resource_permissions", function () use ($roleId) {
            $role = Role::with('resourcePermissions')->find($roleId);
            $map = [];

            if ($role) {
                foreach ($role->resourcePermissions as $permission) {
                    $map[$permission->resource_class] = $permission->actions ?? [];
                }
            }

            return $map;
        });
    }

    public function getCachedFieldPermissions(int $roleId): array
    {
        return Cache::rememberForever("role_{$roleId}_field_permissions", function () use ($roleId) {
            $role = Role::with('fieldPermissions')->find($roleId);
            $map = [];

            if ($role) {
                foreach ($role->fieldPermissions as $permission) {
                    $accessLevel = is_object($permission->access_level) ? $permission->access_level->value : $permission->access_level;
                    $componentType = is_object($permission->component_type) ? $permission->component_type->value : $permission->component_type;
                    $map[$permission->resource_class][$componentType][$permission->field_name] = $accessLevel;
                }
            }

            return $map;
        });
    }

    public function getCachedActionPermissions(int $roleId): array
    {
        return Cache::rememberForever("role_{$roleId}_action_permissions", function () use ($roleId) {
            $role = Role::with('actionPermissions')->find($roleId);
            $map = [];

            if ($role) {
                foreach ($role->actionPermissions as $permission) {
                    $access = is_object($permission->access) ? $permission->access->value : $permission->access;
                    $map[$permission->resource_class][$permission->page_name][$permission->action_name] = $access;
                }
            }

            return $map;
        });
    }

    public function clearRoleCache(int $roleId): void
    {
        Cache::forget("role_{$roleId}_field_permissions");
        Cache::forget("role_{$roleId}_resource_permissions");
        Cache::forget("role_{$roleId}_action_permissions");
    }
}
