<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Mail\RegistrationDecisionMail;
use App\Models\Department;
use App\Models\Property;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Throwable;

class ApprovalController extends Controller
{
    public function index(Request $request)
    {
        $approver = $request->user();
        abort_unless($approver->hasPrivilege('users.approve'), 403);

        $users = User::query()
            ->where('approval_status', 'pending')
            ->when(! $approver->hasPrivilege('properties.view_all'), fn ($query) => $query->where('property_id', $approver->property_id))
            ->when($approver->role === 'Department Leader', fn ($query) => $query->whereHas(
                'departments',
                fn ($departmentQuery) => $departmentQuery->whereIn('departments.id', $approver->departments()->select('departments.id')),
            ))
            ->with(['property', 'departments'])
            ->latest()
            ->paginate(25);

        return UserResource::collection($users);
    }

    public function approve(Request $request, string $id)
    {
        $approver = $request->user();
        abort_unless($approver->hasPrivilege('users.approve'), 403);

        $allowedRoles = User::rolesBelow($approver);

        $validated = $request->validate([
            'property_id' => ['required', 'exists:properties,id'],
            'department_id' => ['required', 'exists:departments,id'],
            'role' => ['sometimes', Rule::in($allowedRoles)],
        ]);

        $property = Property::findOrFail($validated['property_id']);
        if (! $approver->hasPrivilege('properties.view_all')) {
            abort_unless($property->id === $approver->property_id, 403);
        }

        $department = Department::whereKey($validated['department_id'])
            ->where('property_id', $property->id)
            ->firstOrFail();

        $approvedUser = DB::transaction(function () use ($id, $validated, $property, $department, $approver) {
            $user = User::whereKey($id)->lockForUpdate()->firstOrFail();
            abort_unless($user->approval_status === 'pending', 409, 'This registration has already been processed.');
            $this->authorizeApprovalScope($approver, $user);
            $this->authorizeApprovalDepartment($approver, $department);

            $user->forceFill([
                'property_id' => $property->id,
                'role' => $validated['role'] ?? 'Associate',
                'approval_status' => 'approved',
                'approved_at' => now(),
                'approved_by' => $approver->id,
            ])->save();

            $user->departments()->sync([$department->id => ['is_default' => true]]);

            return $user->load(['property', 'departments']);
        });
        $notificationSent = $this->sendDecisionNotification($approvedUser, true);

        return response()->json([
            'message' => $notificationSent
                ? 'Pendaftaran disetujui dan pemberitahuan email telah dikirim.'
                : 'Pendaftaran disetujui, tetapi pemberitahuan email belum dapat dikirim.',
            'email_notification_sent' => $notificationSent,
            'data' => new UserResource($approvedUser),
        ]);
    }

    public function reject(Request $request, string $id)
    {
        abort_unless($request->user()->hasPrivilege('users.approve'), 403);

        $approver = $request->user();
        $user = DB::transaction(function () use ($id, $approver) {
            $user = User::whereKey($id)->lockForUpdate()->firstOrFail();
            abort_unless($user->approval_status === 'pending', 409, 'This registration has already been processed.');
            $this->authorizeApprovalScope($approver, $user);

            $user->forceFill(['approval_status' => 'rejected'])->save();

            return $user;
        });
        $notificationSent = $this->sendDecisionNotification($user, false);

        return response()->json([
            'message' => $notificationSent
                ? 'Pendaftaran ditolak dan pemberitahuan email telah dikirim.'
                : 'Pendaftaran ditolak, tetapi pemberitahuan email belum dapat dikirim.',
            'email_notification_sent' => $notificationSent,
        ]);
    }

    private function sendDecisionNotification(User $user, bool $approved): bool
    {
        try {
            Mail::to($user->email)->send(new RegistrationDecisionMail($user, $approved));

            return true;
        } catch (Throwable $exception) {
            Log::warning('Registration decision email could not be sent.', [
                'user_id' => $user->id,
                'approved' => $approved,
                'exception' => $exception::class,
            ]);

            return false;
        }
    }

    private function authorizeApprovalScope(User $approver, User $applicant): void
    {
        abort_unless(
            $approver->hasPrivilege('properties.view_all') || $applicant->property_id === $approver->property_id,
            404,
        );

        if ($approver->role === 'Department Leader') {
            abort_unless(
                $applicant->departments()->whereIn('departments.id', $approver->departments()->select('departments.id'))->exists(),
                404,
            );
        }
    }

    private function authorizeApprovalDepartment(User $approver, Department $department): void
    {
        if ($approver->role === 'Department Leader') {
            abort_unless($approver->departments()->whereKey($department->id)->exists(), 404);
        }
    }
}
