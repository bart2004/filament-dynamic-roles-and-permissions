<?php

namespace BartStrik\FilamentDynamicRolesAndPermissions\Policies;

use BartStrik\FilamentDynamicRolesAndPermissions\Services\PermissionCacheService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * Base policy that authorizes abilities against a role's stored resource
 * permissions. Each concrete policy maps a model to its Filament resource class.
 */
abstract class ResourcePermissionPolicy
{
    /**
     * @return class-string the Filament resource class these permissions are stored against
     */
    abstract protected function resourceClass(): string;

    public function viewAny(Authenticatable $user): bool
    {
        return $this->allows($user, 'viewAny');
    }

    public function view(Authenticatable $user, Model $model): bool
    {
        return $this->allows($user, 'view');
    }

    public function create(Authenticatable $user): bool
    {
        return $this->allows($user, 'create');
    }

    public function update(Authenticatable $user, Model $model): bool
    {
        return $this->allows($user, 'update');
    }

    public function delete(Authenticatable $user, Model $model): bool
    {
        return $this->allows($user, 'delete');
    }

    public function deleteAny(Authenticatable $user): bool
    {
        return $this->allows($user, 'delete');
    }

    public function restore(Authenticatable $user, Model $model): bool
    {
        return $this->allows($user, 'restore');
    }

    public function forceDelete(Authenticatable $user, Model $model): bool
    {
        return $this->allows($user, 'forceDelete');
    }

    protected function allows(Authenticatable $user, string $action): bool
    {
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }

        if (! $user->role_id) {
            return false;
        }

        return app(PermissionCacheService::class)->hasResourceAction(
            $user->role_id,
            $this->resourceClass(),
            $action,
        );
    }
}
