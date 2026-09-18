<?php

namespace BartStrik\FilamentDynamicRolesAndPermissions\Filament\Resources\Roles\Traits;

use BartStrik\FilamentDynamicRolesAndPermissions\Models\ActionPermission;
use BartStrik\FilamentDynamicRolesAndPermissions\Models\FieldPermission;
use BartStrik\FilamentDynamicRolesAndPermissions\Models\ResourcePermission;
use BartStrik\FilamentDynamicRolesAndPermissions\Services\PermissionCacheService;
use Filament\Facades\Filament;

trait SyncsRolePermissions
{
    private array $pendingResourcePermissions = [];

    private array $pendingFieldPermissions = [];

    private array $pendingActionPermissions = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->record->load(['resourcePermissions', 'fieldPermissions', 'actionPermissions']);

        $actionLevels = ['viewAny' => 1, 'view' => 2, 'create' => 3, 'update' => 4, 'delete' => 5, 'restore' => 6, 'forceDelete' => 7];

        foreach ($this->record->resourcePermissions as $permission) {
            $resourceName = class_basename($permission->resource_class);
            $actions = $permission->actions ?? [];
            $level = 0;
            foreach ($actions as $action) {
                $level = max($level, $actionLevels[$action] ?? 0);
            }
            $data['resource_permissions'][$resourceName]['actions'] = $level;
        }

        foreach ($this->record->fieldPermissions as $permission) {
            $resourceName = class_basename($permission->resource_class);
            $componentType = $permission->component_type instanceof \BackedEnum
                ? $permission->component_type->value
                : $permission->component_type;
            $accessLevel = $permission->access_level instanceof \BackedEnum
                ? $permission->access_level->value
                : $permission->access_level;

            $data['field_permissions'][$resourceName][$componentType][$permission->field_name] = $accessLevel;
        }

        foreach ($this->record->actionPermissions as $permission) {
            $resourceName = class_basename($permission->resource_class);
            $access = $permission->access instanceof \BackedEnum
                ? $permission->access->value
                : $permission->access;

            $data['action_permissions'][$resourceName][$permission->page_name][$permission->action_name] = $access;
        }

        return $data;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->pendingResourcePermissions = $data['resource_permissions'] ?? [];
        $this->pendingFieldPermissions = $data['field_permissions'] ?? [];
        $this->pendingActionPermissions = $data['action_permissions'] ?? [];

        unset($data['resource_permissions'], $data['field_permissions'], $data['action_permissions']);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->pendingResourcePermissions = $data['resource_permissions'] ?? [];
        $this->pendingFieldPermissions = $data['field_permissions'] ?? [];
        $this->pendingActionPermissions = $data['action_permissions'] ?? [];

        unset($data['resource_permissions'], $data['field_permissions'], $data['action_permissions']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->syncPermissions();
    }

    protected function afterSave(): void
    {
        $this->syncPermissions();
    }

    protected function syncPermissions(): void
    {
        $role = $this->record;

        ResourcePermission::where('role_id', $role->id)->delete();
        FieldPermission::where('role_id', $role->id)->delete();
        ActionPermission::where('role_id', $role->id)->delete();

        $resourceMap = [];
        foreach (Filament::getResources() as $resourceClass) {
            $resourceMap[class_basename($resourceClass)] = $resourceClass;
        }

        $actionLevels = ['viewAny' => 1, 'view' => 2, 'create' => 3, 'update' => 4, 'delete' => 5, 'restore' => 6, 'forceDelete' => 7];

        $resourcePermissions = [];
        foreach ($this->pendingResourcePermissions as $resourceName => $permData) {
            $level = (int) ($permData['actions'] ?? 0);
            $fqcn = $resourceMap[$resourceName] ?? null;

            if ($level === 0 || ! $fqcn) {
                continue;
            }

            $actions = array_keys(array_filter($actionLevels, fn (int $required) => $level >= $required));

            $resourcePermissions[] = [
                'role_id' => $role->id,
                'resource_class' => $fqcn,
                'actions' => json_encode(array_values($actions)),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (! empty($resourcePermissions)) {
            ResourcePermission::insert($resourcePermissions);
        }

        $fieldPermissions = [];
        foreach ($this->pendingFieldPermissions as $resourceName => $contexts) {
            $fqcn = $resourceMap[$resourceName] ?? null;

            if (! $fqcn) {
                continue;
            }

            foreach ($contexts as $context => $fields) {
                foreach ($fields as $fieldName => $accessLevel) {
                    if (! $accessLevel) {
                        continue;
                    }

                    $fieldPermissions[] = [
                        'role_id' => $role->id,
                        'resource_class' => $fqcn,
                        'component_type' => $context,
                        'field_name' => $fieldName,
                        'access_level' => $accessLevel,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        if (! empty($fieldPermissions)) {
            FieldPermission::insert($fieldPermissions);
        }

        $actionPermissions = [];
        foreach ($this->pendingActionPermissions as $resourceName => $pages) {
            $fqcn = $resourceMap[$resourceName] ?? null;

            if (! $fqcn) {
                continue;
            }

            foreach ($pages as $pageName => $actions) {
                foreach ($actions as $actionName => $access) {
                    if (! $access || $access === 'visible') {
                        continue;
                    }

                    $actionPermissions[] = [
                        'role_id' => $role->id,
                        'resource_class' => $fqcn,
                        'page_name' => $pageName,
                        'action_name' => $actionName,
                        'access' => $access,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        if (! empty($actionPermissions)) {
            ActionPermission::insert($actionPermissions);
        }

        app(PermissionCacheService::class)->clearRoleCache($role->id);
    }
}
