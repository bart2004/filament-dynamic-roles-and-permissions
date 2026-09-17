<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('field_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->string('resource_class');
            $table->string('component_type');
            $table->string('field_name');
            $table->string('access_level');
            $table->timestamps();

            $table->unique([
                'role_id',
                'resource_class',
                'component_type',
                'field_name',
            ], 'field_permissions_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_permissions');
    }
};
