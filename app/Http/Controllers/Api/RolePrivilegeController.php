<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class RolePrivilegeController extends Controller
{
    private const MANAGER_PRIVILEGES = [
        'departments.manage',
        'users.view',
        'users.approve',
        'users.manage',
        'users.manage_departments',
        'tasks.assign',
        'tasks.manage',
        'tasks.view_all',
        'tasks.comment',
        'reports.view',
    ];

    private const GM_PRIVILEGES = [
        'properties.view_all',
        'properties.manage',
        'departments.manage',
        'users.view',
        'users.view_all',
        'users.approve',
        'users.manage',
        'users.manage_departments',
        'tasks.assign',
        'tasks.manage',
        'tasks.view_all',
        'tasks.comment',
        'reports.view',
    ];

    private const IT_PRIVILEGES = [
        'properties.view_all',
        'properties.manage',
        'departments.manage',
        'users.view',
        'users.view_all',
        'users.approve',
        'users.manage',
        'users.manage_departments',
        'tasks.assign',
        'tasks.manage',
        'tasks.view_all',
        'tasks.update_assigned',
        'tasks.comment',
        'subtasks.manage_assigned',
        'reports.view',
    ];

    private const DEPARTMENT_LEADER_PRIVILEGES = [
        'users.view',
        'users.manage',
        'tasks.assign',
        'tasks.manage',
        'tasks.comment',
        'reports.view',
    ];

    private const ASSOCIATE_PRIVILEGES = [
        'tasks.update_assigned',
        'tasks.comment',
        'subtasks.manage_assigned',
    ];

    private const VENDOR_PRIVILEGES = [
        'tasks.update_assigned',
        'tasks.comment',
        'subtasks.manage_assigned',
    ];

    public function index(Request $request)
    {
        abort_unless($request->user()->hasPrivilege('roles.manage'), 403);

        return response()->json([
            'roles' => Role::whereIn('key', ['Admin', 'IT', 'GM', 'Manager', 'Department Leader', 'Associate', 'Vendor', 'Daily Worker', 'Casual'])
                ->get(['key', 'name', 'privileges', 'is_system'])
                ->sortBy(fn ($role) => array_search($role->key, ['Admin', 'IT', 'GM', 'Manager', 'Department Leader', 'Associate', 'Vendor', 'Daily Worker', 'Casual'], true))
                ->values(),
            'assignable_privileges' => [
                'GM' => self::GM_PRIVILEGES,
                'Manager' => self::MANAGER_PRIVILEGES,
                'IT' => self::IT_PRIVILEGES,
                'Department Leader' => self::DEPARTMENT_LEADER_PRIVILEGES,
                'Associate' => self::ASSOCIATE_PRIVILEGES,
                'Vendor' => self::VENDOR_PRIVILEGES,
                'Daily Worker' => self::ASSOCIATE_PRIVILEGES,
                'Casual' => self::ASSOCIATE_PRIVILEGES,
            ],
        ]);
    }

    public function update(Request $request, string $roleKey)
    {
        abort_unless($request->user()->hasPrivilege('roles.manage'), 403);
        abort_unless($request->user()->role === 'Admin', 403);
        abort_if($roleKey === 'Admin', 403, 'The Admin role cannot be edited.');

        $assignablePrivileges = match ($roleKey) {
            'GM' => self::GM_PRIVILEGES,
            'IT' => self::IT_PRIVILEGES,
            'Manager' => self::MANAGER_PRIVILEGES,
            'Department Leader' => self::DEPARTMENT_LEADER_PRIVILEGES,
            'Associate' => self::ASSOCIATE_PRIVILEGES,
            'Vendor' => self::VENDOR_PRIVILEGES,
            'Daily Worker' => self::ASSOCIATE_PRIVILEGES,
            'Casual' => self::ASSOCIATE_PRIVILEGES,
            default => abort(404),
        };

        $validated = $request->validate([
            'privileges' => ['present', 'array'],
            'privileges.*' => ['string', 'distinct', Rule::in($assignablePrivileges)],
        ]);

        $role = Role::where('key', $roleKey)->firstOrFail();
        $role->update(['privileges' => array_values($validated['privileges'])]);
        Cache::forget('role_privileges:'.$role->key);

        return response()->json(['data' => $role->only(['key', 'name', 'privileges'])]);
    }
}
