<?php

namespace BartStrik\FilamentRolePermissions\Tests\Fixtures;

use BartStrik\FilamentRolePermissions\Concerns\HasRolePermissions;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasRolePermissions;

    protected $guarded = [];
}
