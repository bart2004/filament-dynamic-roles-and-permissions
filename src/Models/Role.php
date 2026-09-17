<?php

namespace BartStrik\FilamentRolePermissions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $guarded = ['id'];

    protected $fillable = [
        'name',
    ];

    public function resourcePermissions(): HasMany
    {
        return $this->hasMany(ResourcePermission::class);
    }

    public function fieldPermissions(): HasMany
    {
        return $this->hasMany(FieldPermission::class);
    }

    public function actionPermissions(): HasMany
    {
        return $this->hasMany(ActionPermission::class);
    }
}
