<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resource_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->string('resource_class');
            $table->json('actions')->nullable();
            $table->unique(['role_id', 'resource_class']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_permissions');
    }
};
