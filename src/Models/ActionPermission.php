<?php

namespace BartStrik\FilamentDynamicRolesAndPermissions\Models;

use BartStrik\FilamentDynamicRolesAndPermissions\Enums\ActionAccess;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActionPermission extends Model
{
    protected $guarded = ['id'];

    protected $fillable = [
        'role_id',
        'resource_class',
        'page_name',
        'action_name',
        'access',
    ];

    protected function casts(): array
    {
        return [
            'access' => ActionAccess::class,
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
