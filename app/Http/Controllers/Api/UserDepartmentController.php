<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserDepartmentController extends Controller
{
    // Pengguna yang memiliki privilege dapat menambahkan akses departemen.
    public function addAccess(Request $request, $userId)
    {
        $manager = $request->user();
        abort_unless($manager->hasPrivilege('users.manage_departments'), 403);

        $validated = $request->validate([
            'department_id' => ['required', 'uuid', Rule::exists('departments', 'id')],
        ]);

        $target = User::with('departments')->findOrFail($userId);
        abort_unless($target->approval_status === 'approved', 404);
        abort_unless($target->role !== 'Admin' || $manager->role === 'Admin', 404);
        abort_unless($manager->id !== $target->id, 403, 'You cannot grant department access to your own account.');
        abort_unless($manager->hasPrivilege('properties.view_all') || $target->property_id === $manager->property_id, 403);

        abort_unless(in_array($target->role, User::rolesBelow($manager), true), 403);

        if ($manager->role === 'Department Leader') {
            abort_unless($target->departments->pluck('id')->intersect($manager->departments()->pluck('departments.id'))->isNotEmpty(), 403);
            abort_unless($manager->departments()->whereKey($validated['department_id'])->exists(), 403);
        }

        $department = Department::whereKey($validated['department_id'])
            ->where('property_id', $target->property_id)
            ->firstOrFail();

        $added = DB::transaction(function () use ($target, $department): bool {
            $lockedTarget = User::whereKey($target->id)->lockForUpdate()->firstOrFail();

            if ($lockedTarget->departments()->whereKey($department->id)->exists()) {
                return false;
            }

            $hasDefault = $lockedTarget->departments()->wherePivot('is_default', true)->exists();
            $lockedTarget->departments()->attach($department->id, ['is_default' => ! $hasDefault]);

            return true;
        });

        abort_unless($added, 409, 'User already has access to this department.');

        return response()->json([
            'status' => 'success',
            'message' => 'Akses departemen berhasil ditambahkan.',
            'departments' => $target->departments()->pluck('name'),
        ]);
    }
}
