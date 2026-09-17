<?php

namespace BartStrik\FilamentRolePermissions\Models;

use BartStrik\FilamentRolePermissions\Enums\AccessLevel;
use BartStrik\FilamentRolePermissions\Enums\ComponentType;
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
