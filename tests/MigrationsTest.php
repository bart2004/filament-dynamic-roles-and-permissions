<?php

namespace BartStrik\FilamentDynamicRolesAndPermissions\Tests;

use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;

class MigrationsTest extends TestCase
{
    #[Test]
    public function it_creates_the_permission_tables(): void
    {
        $this->assertTrue(Schema::hasTable('roles'));
        $this->assertTrue(Schema::hasTable('resource_permissions'));
        $this->assertTrue(Schema::hasTable('field_permissions'));
        $this->assertTrue(Schema::hasTable('action_permissions'));
    }

    #[Test]
    public function it_adds_a_role_id_column_to_users(): void
    {
        $this->assertTrue(Schema::hasColumn('users', 'role_id'));
    }
}
