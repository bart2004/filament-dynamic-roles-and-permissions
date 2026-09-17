<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('action_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->string('resource_class');
            $table->string('page_name');
            $table->string('action_name');
            $table->string('access');
            $table->timestamps();

            $table->unique(['role_id', 'resource_class', 'page_name', 'action_name'], 'action_perms_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('action_permissions');
    }
};
