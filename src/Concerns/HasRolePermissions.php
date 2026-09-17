<?php

namespace BartStrik\FilamentRolePermissions\Concerns;

use BartStrik\FilamentRolePermissions\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Add to the application's authenticatable model to give it a role and to
 * define super-admin bypass logic.
 */
trait HasRolePermissions
{
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Whether this model bypasses all role-based permission checks.
     * Override in the model to define super admin logic.
     */
    public function isSuperAdmin(): bool
    {
        return false;
    }
}
