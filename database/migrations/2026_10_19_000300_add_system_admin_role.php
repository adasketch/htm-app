<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::table('roles')->where('key', 'Admin')->exists()) {
            DB::table('roles')->insert([
                'id' => (string) Str::uuid(),
                'key' => 'Admin',
                'name' => 'System Administrator',
                'privileges' => json_encode([
                    'properties.view_all', 'properties.manage', 'departments.manage',
                    'users.view', 'users.view_all', 'users.approve', 'users.assign_roles',
                    'users.manage', 'users.manage_departments', 'roles.manage',
                    'tasks.assign', 'tasks.manage', 'tasks.view_all', 'tasks.update_assigned',
                    'tasks.comment', 'subtasks.manage_assigned', 'reports.view',
                ], JSON_THROW_ON_ERROR),
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $gmPrivileges = json_decode(DB::table('roles')->where('key', 'GM')->value('privileges') ?? '[]', true, flags: JSON_THROW_ON_ERROR);
        $gmAllowed = [
            'properties.view_all', 'properties.manage', 'departments.manage',
            'users.view', 'users.view_all', 'users.approve', 'users.manage',
            'users.manage_departments', 'tasks.assign', 'tasks.manage',
            'tasks.view_all', 'tasks.comment', 'reports.view',
        ];
        DB::table('roles')->where('key', 'GM')->update([
            'privileges' => json_encode(array_values(array_intersect($gmPrivileges, $gmAllowed)), JSON_THROW_ON_ERROR),
            'updated_at' => now(),
        ]);

        foreach (['Admin', 'GM', 'Manager', 'Department Leader', 'Associate'] as $roleKey) {
            Cache::forget('role_privileges:'.$roleKey);
        }
    }

    public function down(): void
    {
        DB::table('roles')->where('key', 'Admin')->delete();
        Cache::forget('role_privileges:Admin');
        Cache::forget('role_privileges:GM');
    }
};
