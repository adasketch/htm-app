<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('roles')->where('key', 'IT')->update([
            'key' => 'Department Leader',
            'name' => 'Department Leader',
            'privileges' => json_encode([
                'users.view', 'users.manage', 'tasks.assign', 'tasks.manage',
                'tasks.comment', 'reports.view',
            ], JSON_THROW_ON_ERROR),
            'updated_at' => now(),
        ]);

        DB::table('users')->where('role', 'IT')->update(['role' => 'Department Leader']);

        DB::table('roles')->where('key', 'GM')->update([
            'privileges' => json_encode([
                'properties.view_all', 'properties.manage', 'departments.manage',
                'users.view', 'users.view_all', 'users.approve', 'users.assign_roles',
                'users.manage', 'users.manage_departments', 'roles.manage',
                'tasks.assign', 'tasks.manage', 'tasks.view_all', 'tasks.comment', 'reports.view',
            ], JSON_THROW_ON_ERROR),
            'updated_at' => now(),
        ]);

        $managerPrivileges = json_decode(DB::table('roles')->where('key', 'Manager')->value('privileges') ?? '[]', true, flags: JSON_THROW_ON_ERROR);
        $managerAllowed = [
            'departments.manage', 'users.view', 'users.approve', 'users.manage',
            'users.manage_departments', 'tasks.assign', 'tasks.manage', 'tasks.view_all',
            'tasks.comment', 'reports.view',
        ];
        $managerPrivileges = array_values(array_unique(array_merge(
            array_intersect($managerPrivileges, $managerAllowed),
            ['users.manage', 'tasks.view_all'],
        )));
        DB::table('roles')->where('key', 'Manager')->update([
            'privileges' => json_encode($managerPrivileges, JSON_THROW_ON_ERROR),
            'updated_at' => now(),
        ]);

        $associatePrivileges = json_decode(DB::table('roles')->where('key', 'Associate')->value('privileges') ?? '[]', true, flags: JSON_THROW_ON_ERROR);
        DB::table('roles')->where('key', 'Associate')->update([
            'privileges' => json_encode(array_values(array_intersect($associatePrivileges, [
                'tasks.update_assigned', 'tasks.comment', 'subtasks.manage_assigned',
            ])), JSON_THROW_ON_ERROR),
            'updated_at' => now(),
        ]);

        foreach (['GM', 'Manager', 'Department Leader', 'Associate', 'IT'] as $roleKey) {
            Cache::forget('role_privileges:'.$roleKey);
        }
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'Department Leader')->update(['role' => 'IT']);
        DB::table('roles')->where('key', 'Department Leader')->update([
            'key' => 'IT',
            'name' => 'IT',
            'privileges' => json_encode([
                'properties.view_all', 'users.view', 'users.view_all', 'users.approve', 'roles.manage',
            ], JSON_THROW_ON_ERROR),
            'updated_at' => now(),
        ]);
    }
};
