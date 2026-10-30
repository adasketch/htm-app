<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->index('created_at', 'tasks_created_at_index');
            $table->index(['property_id', 'department_id', 'created_at'], 'tasks_scope_created_at_index');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->index(['approval_status', 'created_at'], 'users_approval_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex('users_approval_created_at_index');
        });

        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropIndex('tasks_scope_created_at_index');
            $table->dropIndex('tasks_created_at_index');
        });
    }
};
