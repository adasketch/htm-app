<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user->hasPrivilege('users.view'), 403);

        $validated = $request->validate([
            'property_id' => ['sometimes', 'uuid', 'exists:properties,id'],
            'department_id' => ['sometimes', 'uuid', 'exists:departments,id'],
        ]);

        if (isset($validated['property_id'])) {
            abort_unless($user->hasPrivilege('properties.view_all') || $validated['property_id'] === $user->property_id, 403);
        }

        $department = isset($validated['department_id']) ? Department::findOrFail($validated['department_id']) : null;
        if ($department) {
            abort_unless($user->hasPrivilege('properties.view_all') || $department->property_id === $user->property_id, 403);
            if (isset($validated['property_id'])) {
                abort_unless($department->property_id === $validated['property_id'], 422, 'Department does not belong to the selected property.');
            }
            if ($user->role === 'Department Leader') {
                abort_unless($user->departments()->whereKey($department->id)->exists(), 403);
            }
        }

        $users = User::query()
            ->where('approval_status', 'approved')
            ->where(function ($query) use ($user) {
                $query->where('role', '<>', 'Admin');

                if ($user->role === 'Admin') {
                    $query->orWhereKey($user->id);
                }
            })
            ->with(['property', 'departments'])
            ->when(! $user->hasPrivilege('users.view_all'), fn ($query) => $query->where('property_id', $user->property_id))
            ->when(isset($validated['property_id']), fn ($query) => $query->where('property_id', $validated['property_id']))
            ->when($user->role === 'Department Leader', function ($query) use ($user) {
                $query->whereIn('role', ['Associate', 'Vendor', 'Daily Worker', 'Casual'])
                    ->whereHas('departments', fn ($departmentQuery) => $departmentQuery->whereIn('departments.id', $user->departments()->select('departments.id')));
            })
            ->when(isset($validated['department_id']), fn ($query) => $query->whereHas('departments', fn ($departmentQuery) => $departmentQuery->whereKey($validated['department_id'])))
            ->orderBy('name')
            ->paginate(25);

        return UserResource::collection($users);
    }

    public function updateManaged(Request $request, string $id)
    {
        if (is_string($request->input('email'))) {
            $request->merge(['email' => mb_strtolower(trim($request->input('email')))]);
        }

        $manager = $request->user();
        abort_unless($manager->hasPrivilege('users.manage'), 403);

        $target = User::with('departments')->findOrFail($id);
        $this->authorizeManagedTarget($manager, $target);
        $managerRole = $manager->role;

        $managedDepartmentIds = collect();
        if ($managerRole === 'Department Leader') {
            abort_unless(in_array($target->role, User::rolesBelow($manager), true), 403);
            $managedDepartmentIds = $manager->departments()->pluck('departments.id');
            abort_unless($target->departments->pluck('id')->intersect($managedDepartmentIds)->isNotEmpty(), 403);
        }

        $assignableRoles = array_merge(
            $managerRole === 'Admin' ? ['Admin'] : [],
            User::rolesBelow($manager),
        );

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'position' => ['sometimes', 'nullable', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($target->id)],
            'role' => ['sometimes', 'required', Rule::in($assignableRoles)],
            'password' => ['sometimes', 'required', 'string', Password::min(12), 'confirmed'],
            'department_ids' => ['sometimes', 'array', 'min:1'],
            'department_ids.*' => [
                'required', 'uuid', 'distinct',
                Rule::exists('departments', 'id')->where('property_id', $target->property_id),
            ],
            'default_department_id' => ['required_with:department_ids', 'nullable', 'uuid'],
        ]);

        if (isset($validated['department_ids'])) {
            abort_unless(in_array($validated['default_department_id'] ?? null, $validated['department_ids'], true), 422, 'Default department must be included in department access.');
            if ($managerRole === 'Department Leader') {
                abort_unless(collect($validated['department_ids'])->diff($managedDepartmentIds)->isEmpty(), 403, 'You can only assign users to your own departments.');
            }
        }

        abort_unless(! isset($validated['password']) || in_array($manager->role, ['Admin', 'IT'], true), 403);

        DB::transaction(function () use ($target, $validated) {
            $target->fill(collect($validated)->except(['department_ids', 'default_department_id', 'password'])->all());
            if (isset($validated['password'])) {
                $target->password = $validated['password'];
            }
            $target->save();
            if (isset($validated['password'])) {
                $target->tokens()->delete();
            }

            if (isset($validated['department_ids'])) {
                $memberships = collect($validated['department_ids'])->mapWithKeys(fn ($departmentId) => [
                    $departmentId => ['is_default' => $departmentId === $validated['default_department_id']],
                ])->all();
                $target->departments()->sync($memberships);
            }
        });

        return response()->json([
            'message' => isset($validated['password'])
                ? 'Data pengguna dan password berhasil diperbarui. Sesi aktif pengguna telah dikeluarkan.'
                : 'Data pengguna berhasil diperbarui.',
            'data' => new UserResource($target->fresh()->load(['property', 'departments'])),
        ]);
    }

    public function setPassword(Request $request, string $id)
    {
        $manager = $request->user();
        abort_unless(in_array($manager->role, ['Admin', 'IT'], true), 403);
        abort_unless($manager->hasPrivilege('users.manage'), 403);

        $target = User::findOrFail($id);
        $this->authorizeManagedTarget($manager, $target);

        $validated = $request->validate([
            'password' => ['required', 'string', Password::min(12), 'confirmed'],
        ]);

        DB::transaction(function () use ($target, $validated): void {
            $lockedTarget = User::whereKey($target->id)->lockForUpdate()->firstOrFail();
            $lockedTarget->forceFill(['password' => $validated['password']])->save();
            $lockedTarget->tokens()->delete();
        });

        return response()->json(['message' => 'Password pengguna berhasil diperbarui. Sesi aktif pengguna telah dikeluarkan.']);
    }

    public function destroy(Request $request, string $id)
    {
        $manager = $request->user();
        abort_unless(in_array($manager->role, ['Admin', 'IT'], true), 403);
        abort_unless($manager->hasPrivilege('users.manage'), 403);

        $target = User::with('departments')->findOrFail($id);
        $this->authorizeManagedTarget($manager, $target);

        DB::transaction(function () use ($target): void {
            $lockedTarget = User::whereKey($target->id)->lockForUpdate()->firstOrFail();
            $lockedTarget->tokens()->delete();
            $lockedTarget->departments()->detach();
            $lockedTarget->forceFill([
                'name' => 'Deleted user',
                'position' => null,
                'email' => 'deleted+'.Str::uuid().'@deleted.invalid',
                'password' => Str::random(64),
                'property_id' => null,
                'approval_status' => 'rejected',
            ])->save();
            $lockedTarget->delete();
        });

        return response()->noContent();
    }

    public function me(Request $request)
    {
        return new UserResource($request->user()->load(['property', 'departments']));
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['prohibited'],
        ]);

        $user->fill($validated)->save();

        return new UserResource($user->load(['property', 'departments']));
    }

    private function authorizeManagedTarget(User $manager, User $target): void
    {
        abort_unless($target->approval_status === 'approved', 404);
        abort_unless($target->role !== 'Admin', 404);
        abort_unless($manager->id !== $target->id, 403, 'You cannot manage your own account here.');
        abort_unless($manager->hasPrivilege('properties.view_all') || $target->property_id === $manager->property_id, 403);

        abort_unless(in_array($target->role, User::rolesBelow($manager), true), 403, 'You can only manage users below your role.');
    }
}
