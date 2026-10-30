<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    public function index(Request $request, ?string $propertyId = null)
    {
        $user = $request->user();
        $propertyId ??= $user->property_id;

        abort_unless($user->hasPrivilege('properties.view_all') || $propertyId === $user->property_id, 403);
        abort_unless(Property::whereKey($propertyId)->exists(), 404);

        $departments = Department::where('property_id', $propertyId)
            ->when($user->role === 'Department Leader', fn ($query) => $query->whereIn('id', $user->departments()->select('departments.id')))
            ->orderBy('name')
            ->get();

        return DepartmentResource::collection($departments);
    }

    public function store(Request $request, ?string $propertyId = null)
    {
        $user = $request->user();
        abort_unless($user->hasPrivilege('departments.manage'), 403);

        $propertyId ??= $user->property_id;
        abort_unless($user->hasPrivilege('properties.view_all') || $propertyId === $user->property_id, 403);
        abort_unless(Property::whereKey($propertyId)->exists(), 404);

        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('departments', 'name')->where('property_id', $propertyId),
            ],
        ]);

        $department = Department::create([
            'property_id' => $propertyId,
            'name' => $validated['name'],
        ]);

        return (new DepartmentResource($department))->response()->setStatusCode(201);
    }

    public function destroy(Request $request, string $propertyId, string $departmentId)
    {
        $user = $request->user();
        abort_unless($user->hasPrivilege('departments.manage'), 403);
        abort_unless($user->hasPrivilege('properties.view_all') || $propertyId === $user->property_id, 403);

        $department = Department::where('property_id', $propertyId)
            ->whereKey($departmentId)
            ->firstOrFail();

        if ($department->users()->exists() || $department->tasks()->exists()) {
            return response()->json([
                'message' => 'Departemen masih memiliki pengguna atau tugas. Pindahkan datanya sebelum menghapus departemen.',
            ], 409);
        }

        $department->delete();

        return response()->noContent();
    }
}
