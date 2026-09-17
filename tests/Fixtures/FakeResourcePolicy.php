<?php

namespace BartStrik\FilamentRolePermissions\Tests\Fixtures;

use BartStrik\FilamentRolePermissions\Policies\ResourcePermissionPolicy;

class FakeResourcePolicy extends ResourcePermissionPolicy
{
    public const RESOURCE = 'App\\Filament\\Resources\\Posts\\PostResource';

    protected function resourceClass(): string
    {
        return self::RESOURCE;
    }
}
