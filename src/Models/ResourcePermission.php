<?php

namespace BartStrik\FilamentRolePermissions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResourcePermission extends Model
{
    protected $guarded = ['id'];

    protected $fillable = [
        'role_id',
        'resource_class',
        'actions',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    protected function casts(): array
    {
        return [
            'actions' => 'array',
        ];
    }
}
