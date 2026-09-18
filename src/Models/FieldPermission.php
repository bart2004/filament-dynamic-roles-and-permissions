<?php

namespace BartStrik\FilamentDynamicRolesAndPermissions\Models;

use BartStrik\FilamentDynamicRolesAndPermissions\Enums\AccessLevel;
use BartStrik\FilamentDynamicRolesAndPermissions\Enums\ComponentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FieldPermission extends Model
{
    protected $guarded = ['id'];

    protected $fillable = [
        'role_id',
        'resource_class',
        'component_type',
        'field_name',
        'access_level',
    ];

    protected function casts(): array
    {
        return [
            'access_level' => AccessLevel::class,
            'component_type' => ComponentType::class,
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
