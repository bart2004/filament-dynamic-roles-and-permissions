<?php

namespace BartStrik\FilamentRolePermissions\Tests;

use BartStrik\FilamentRolePermissions\Models\ResourcePermission;
use BartStrik\FilamentRolePermissions\Models\Role;
use BartStrik\FilamentRolePermissions\Tests\Fixtures\FakeResourcePolicy;
use BartStrik\FilamentRolePermissions\Tests\Fixtures\User;
use PHPUnit\Framework\Attributes\Test;

class ResourcePermissionPolicyTest extends TestCase
{
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

    #[Test]
    public function it_allows_an_action_the_role_is_granted(): void
    {
        $role = Role::create(['name' => 'Editor']);
        ResourcePermission::create([
            'role_id' => $role->id,
            'resource_class' => FakeResourcePolicy::RESOURCE,
            'actions' => ['viewAny', 'update'],
        ]);

        $user = $this->makeUser(['role_id' => $role->id]);
        $policy = new FakeResourcePolicy;

        $this->assertTrue($policy->viewAny($user));
        $this->assertFalse($policy->delete($user, $role));
    }

    #[Test]
    public function it_denies_everything_for_a_user_without_a_role(): void
    {
        $user = $this->makeUser(['role_id' => null]);
        $policy = new FakeResourcePolicy;

        $this->assertFalse($policy->viewAny($user));
        $this->assertFalse($policy->create($user));
    }

    #[Test]
    public function a_super_admin_bypasses_every_check(): void
    {
        $user = new class extends User
        {
            public function isSuperAdmin(): bool
            {
                return true;
            }
        };
        $user->role_id = null;

        $policy = new FakeResourcePolicy;

        $this->assertTrue($policy->viewAny($user));
        $this->assertTrue($policy->forceDelete($user, new Role));
    }
}
