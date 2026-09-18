<?php

namespace BartStrik\FilamentDynamicRolesAndPermissions\Tests;

use BartStrik\FilamentDynamicRolesAndPermissions\Authorization\ResourcePermissionGate;
use BartStrik\FilamentDynamicRolesAndPermissions\Models\ResourcePermission;
use BartStrik\FilamentDynamicRolesAndPermissions\Models\Role;
use BartStrik\FilamentDynamicRolesAndPermissions\Services\PermissionCacheService;
use BartStrik\FilamentDynamicRolesAndPermissions\Tests\Fixtures\FakeResourcePolicy;
use BartStrik\FilamentDynamicRolesAndPermissions\Tests\Fixtures\Post;
use BartStrik\FilamentDynamicRolesAndPermissions\Tests\Fixtures\User;
use Illuminate\Contracts\Auth\Access\Gate;
use PHPUnit\Framework\Attributes\Test;

class ResourcePermissionGateTest extends TestCase
{
    public const RESOURCE = 'App\\Filament\\Resources\\Posts\\PostResource';

    /**
     * A gate whose model map is fixed to Post => PostResource, so the automatic
     * resolution can be exercised without booting a full Filament panel.
     */
    private function gate(): ResourcePermissionGate
    {
        ResourcePermissionGate::flush();

        return new class(app(Gate::class), app(PermissionCacheService::class)) extends ResourcePermissionGate
        {
            protected function modelResourceMap(): array
            {
                return [Post::class => ResourcePermissionGateTest::RESOURCE];
            }
        };
    }

    /** @param array<string, mixed> $attributes */
    private function makeUser(array $attributes): User
    {
        return User::create([
            'name' => 'Test User',
            'email' => 'user'.uniqid().'@example.com',
            'password' => 'password',
            ...$attributes,
        ]);
    }

    private function roleWithActions(array $actions): Role
    {
        $role = Role::create(['name' => 'Editor '.uniqid()]);
        ResourcePermission::create([
            'role_id' => $role->id,
            'resource_class' => self::RESOURCE,
            'actions' => $actions,
        ]);

        return $role;
    }

    #[Test]
    public function it_authorizes_a_granted_ability_for_a_managed_resource(): void
    {
        $role = $this->roleWithActions(['viewAny', 'update']);
        $user = $this->makeUser(['role_id' => $role->id]);

        $this->assertTrue($this->gate()->authorize($user, 'viewAny', Post::class));
        $this->assertTrue($this->gate()->authorize($user, 'update', new Post));
    }

    #[Test]
    public function it_denies_an_ability_the_role_is_not_granted(): void
    {
        $role = $this->roleWithActions(['viewAny']);
        $user = $this->makeUser(['role_id' => $role->id]);

        $this->assertFalse($this->gate()->authorize($user, 'delete', new Post));
    }

    #[Test]
    public function bulk_variants_map_to_the_single_record_action(): void
    {
        $role = $this->roleWithActions(['delete']);
        $user = $this->makeUser(['role_id' => $role->id]);

        $this->assertTrue($this->gate()->authorize($user, 'deleteAny', Post::class));
    }

    #[Test]
    public function it_denies_every_ability_for_a_user_without_a_role(): void
    {
        $user = $this->makeUser(['role_id' => null]);

        $this->assertFalse($this->gate()->authorize($user, 'viewAny', Post::class));
        $this->assertFalse($this->gate()->authorize($user, 'create', Post::class));
    }

    #[Test]
    public function a_super_admin_is_always_authorized(): void
    {
        $user = new class extends User
        {
            public function isSuperAdmin(): bool
            {
                return true;
            }
        };
        $user->role_id = null;

        $this->assertTrue($this->gate()->authorize($user, 'forceDelete', new Post));
    }

    #[Test]
    public function it_defers_for_an_unmanaged_ability(): void
    {
        $user = $this->makeUser(['role_id' => $this->roleWithActions(['viewAny'])->id]);

        $this->assertNull($this->gate()->authorize($user, 'reorder', Post::class));
    }

    #[Test]
    public function it_defers_for_a_model_without_a_resource(): void
    {
        $user = $this->makeUser(['role_id' => $this->roleWithActions(['viewAny'])->id]);

        $this->assertNull($this->gate()->authorize($user, 'viewAny', Role::class));
    }

    #[Test]
    public function it_defers_when_the_model_already_has_a_policy(): void
    {
        app(Gate::class)->policy(Post::class, FakeResourcePolicy::class);

        $user = $this->makeUser(['role_id' => $this->roleWithActions(['viewAny'])->id]);

        $this->assertNull($this->gate()->authorize($user, 'viewAny', Post::class));
    }
}
