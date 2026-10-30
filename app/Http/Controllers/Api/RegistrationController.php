<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\RegistrationPendingMail;
use App\Models\Department;
use App\Models\Property;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Throwable;

class RegistrationController extends Controller
{
    public function options()
    {
        $properties = Property::query()
            ->with(['departments' => fn ($query) => $query->orderBy('name')])
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'data' => $properties->map(fn (Property $property) => [
                'id' => $property->id,
                'name' => $property->name,
                'departments' => $property->departments->map(fn (Department $department) => [
                    'id' => $department->id,
                    'name' => $department->name,
                ])->values(),
            ])->values(),
        ]);
    }

    public function store(Request $request)
    {
        if (is_string($request->input('email'))) {
            $request->merge(['email' => mb_strtolower(trim($request->input('email')))]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
            'property_id' => ['required', 'uuid', 'exists:properties,id'],
            'department_id' => [
                'required', 'uuid',
                Rule::exists('departments', 'id')->where('property_id', $request->input('property_id')),
            ],
        ]);

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'position' => $validated['position'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'property_id' => $validated['property_id'],
                'role' => 'Associate',
                'approval_status' => 'pending',
            ]);

            $user->departments()->attach($validated['department_id'], ['is_default' => true]);

            return $user;
        });

        $notificationSent = true;
        try {
            Mail::to($user->email)->send(new RegistrationPendingMail($user));
        } catch (Throwable $exception) {
            $notificationSent = false;
            Log::warning('Registration pending email could not be sent.', [
                'user_id' => $user->id,
                'exception' => $exception::class,
            ]);
        }

        return response()->json([
            'message' => $notificationSent
                ? 'Pendaftaran diterima dan menunggu persetujuan. Pemberitahuan telah dikirim ke email Anda.'
                : 'Pendaftaran diterima dan menunggu persetujuan. Pemberitahuan email belum dapat dikirim; hubungi administrator bila diperlukan.',
            'approval_status' => $user->approval_status,
            'email_notification_sent' => $notificationSent,
        ], 202);
    }
}
