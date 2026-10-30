<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PropertyResource;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PropertyController extends Controller
{
    // Mengambil daftar semua properti hotel
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user->hasPrivilege('properties.view_all') || $user->property_id !== null, 403);

        $properties = Property::withCount('departments')
            ->when(! $user->hasPrivilege('properties.view_all'), fn ($query) => $query->whereKey($user->property_id))
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => PropertyResource::collection($properties),
        ]);
    }

    // Menambahkan properti hotel baru (Khusus Admin / GM)
    public function store(Request $request)
    {
        abort_unless($request->user()->hasPrivilege('properties.manage'), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:properties,name',
            'timezone' => ['nullable', 'timezone'],
            'address' => 'nullable|string',
        ]);

        $property = Property::create([
            'name' => $validated['name'],
            'timezone' => $validated['timezone'] ?? 'Asia/Jakarta',
            'address' => $validated['address'] ?? null,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Properti berhasil ditambahkan.',
            'data' => new PropertyResource($property),
        ], 201);
    }

    public function update(Request $request, string $propertyId)
    {
        abort_unless($request->user()->hasPrivilege('properties.manage'), 403);

        $property = Property::findOrFail($propertyId);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('properties', 'name')->ignore($property->id)],
            'timezone' => ['nullable', 'timezone'],
            'address' => ['nullable', 'string'],
        ]);

        $property->fill($validated)->save();

        return response()->json([
            'message' => 'Properti berhasil diperbarui.',
            'data' => new PropertyResource($property->refresh()->loadCount('departments')),
        ]);
    }

    public function destroy(Request $request, string $propertyId)
    {
        $user = $request->user();
        abort_unless($user->role === 'Admin', 403);

        $property = Property::withCount(['users', 'tasks', 'departments'])->findOrFail($propertyId);
        if ($property->users_count > 0 || $property->tasks_count > 0 || $property->departments_count > 0) {
            return response()->json([
                'message' => 'Properti masih memiliki pengguna, tugas, atau departemen. Hapus atau pindahkan data terkait terlebih dahulu.',
            ], 409);
        }

        $property->delete();

        return response()->noContent();
    }
}
