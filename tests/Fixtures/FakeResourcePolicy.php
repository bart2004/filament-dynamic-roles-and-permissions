<?php

namespace BartStrik\FilamentDynamicRolesAndPermissions\Tests\Fixtures;

use BartStrik\FilamentDynamicRolesAndPermissions\Policies\ResourcePermissionPolicy;

class FakeResourcePolicy extends ResourcePermissionPolicy
{
    public const RESOURCE = 'App\\Filament\\Resources\\Posts\\PostResource';

    protected function resourceClass(): string
    {
        return self::RESOURCE;
    }
}
