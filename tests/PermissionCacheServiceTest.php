<?php

namespace BartStrik\FilamentRolePermissions\Tests;

use BartStrik\FilamentRolePermissions\Models\ActionPermission;
use BartStrik\FilamentRolePermissions\Models\FieldPermission;
use BartStrik\FilamentRolePermissions\Models\ResourcePermission;
use BartStrik\FilamentRolePermissions\Models\Role;
use BartStrik\FilamentRolePermissions\Services\PermissionCacheService;
use PHPUnit\Framework\Attributes\Test;

class PermissionCacheServiceTest extends TestCase
{
    private const RESOURCE = 'App\\Filament\\Resources\\Posts\\PostResource';

    private function service(): PermissionCacheService
    {
        return app(PermissionCacheService::class);
    }

    #[Test]
    public function it_reports_whether_a_role_has_a_resource_action(): void
    {
        $role = Role::create(['name' => 'Editor']);

        ResourcePermission::create([
            'role_id' => $role->id,
            'resource_class' => self::RESOURCE,
            'actions' => ['viewAny', 'view', 'update'],
        ]);

        $this->assertTrue($this->service()->hasResourceAction($role->id, self::RESOURCE, 'update'));
        $this->assertFalse($this->service()->hasResourceAction($role->id, self::RESOURCE, 'delete'));
    }

    #[Test]
    public function it_returns_the_stored_field_access_level_and_defaults_to_mutable(): void
    {
        $role = Role::create(['name' => 'Editor']);

        FieldPermission::create([
            'role_id' => $role->id,
            'resource_class' => self::RESOURCE,
            'component_type' => 'edit',
            'field_name' => 'title',
            'access_level' => 'read_only',
        ]);

        $this->assertSame('read_only', $this->service()->getFieldAccessLevel($role->id, self::RESOURCE, 'edit', 'title'));
        $this->assertSame('mutable', $this->service()->getFieldAccessLevel($role->id, self::RESOURCE, 'edit', 'unknown_field'));
    }

    #[Test]
    public function it_returns_the_stored_action_access_and_defaults_to_visible(): void
    {
        $role = Role::create(['name' => 'Editor']);

        ActionPermission::create([
            'role_id' => $role->id,
            'resource_class' => self::RESOURCE,
            'page_name' => 'index',
            'action_name' => 'record_delete',
            'access' => 'hidden',
        ]);

        $this->assertSame('hidden', $this->service()->getActionAccess($role->id, self::RESOURCE, 'index', 'record_delete'));
        $this->assertSame('visible', $this->service()->getActionAccess($role->id, self::RESOURCE, 'index', 'record_edit'));
    }

    #[Test]
    public function clearing_the_cache_reflects_updated_permissions(): void
    {
        $role = Role::create(['name' => 'Editor']);

        ResourcePermission::create([
            'role_id' => $role->id,
            'resource_class' => self::RESOURCE,
            'actions' => ['viewAny'],
        ]);

        $this->assertFalse($this->service()->hasResourceAction($role->id, self::RESOURCE, 'delete'));

        $role->resourcePermissions()->update(['actions' => ['viewAny', 'delete']]);

        // Still cached from the first read.
        $this->assertFalse($this->service()->hasResourceAction($role->id, self::RESOURCE, 'delete'));

        $this->service()->clearRoleCache($role->id);

        $this->assertTrue($this->service()->hasResourceAction($role->id, self::RESOURCE, 'delete'));
    }
}
