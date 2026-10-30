<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key', 50)->unique();
            $table->string('name', 100);
            $table->json('privileges');
            $table->boolean('is_system')->default(true);
            $table->timestamps();
        });

        $now = now();
        $roles = [
            'GM' => [
                'name' => 'General Manager',
                'privileges' => ['properties.view_all', 'properties.manage', 'departments.manage', 'users.view', 'users.view_all', 'users.approve', 'users.assign_roles', 'users.manage_departments', 'roles.manage', 'tasks.assign', 'tasks.manage', 'tasks.view_all', 'tasks.comment', 'reports.view'],
            ],
            'Manager' => [
                'name' => 'Manager',
                'privileges' => ['departments.manage', 'users.view', 'users.approve', 'users.manage_departments', 'tasks.assign', 'tasks.manage', 'tasks.comment', 'reports.view'],
            ],
            'IT' => [
                'name' => 'IT',
                'privileges' => ['properties.view_all', 'users.view', 'users.view_all', 'users.approve', 'roles.manage'],
            ],
            'Associate' => [
                'name' => 'Associate',
                'privileges' => ['tasks.update_assigned', 'tasks.comment', 'subtasks.manage_assigned'],
            ],
        ];

        foreach ($roles as $key => $role) {
            DB::table('roles')->insert([
                'id' => (string) Str::uuid(),
                'key' => $key,
                'name' => $role['name'],
                'privileges' => json_encode($role['privileges'], JSON_THROW_ON_ERROR),
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('users')->where('role', 'Admin')->update(['role' => 'GM']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('approval_status', 20)->default('approved')->index();
            $table->timestamp('approved_at')->nullable();
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'GM')->update(['role' => 'Admin']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropIndex(['approval_status']);
            $table->dropColumn(['approval_status', 'approved_at', 'approved_by']);
        });

        Schema::dropIfExists('roles');
    }
};
