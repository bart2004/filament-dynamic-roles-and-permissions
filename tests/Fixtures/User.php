<?php

namespace BartStrik\FilamentDynamicRolesAndPermissions\Tests\Fixtures;

use BartStrik\FilamentDynamicRolesAndPermissions\Concerns\HasRolePermissions;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasRolePermissions;

    protected $guarded = [];
}
