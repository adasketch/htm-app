<?php

namespace App\Http\Controllers\Api;

use App\Events\TaskTrackerChanged;
use App\Http\Controllers\Controller;
use App\Http\Resources\TaskAttachmentResource;
use App\Http\Resources\TaskResource;
use App\Models\Department;
use App\Models\Role;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class TaskController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();
        $this->authorizeTaskRead($user);
        $visibleTasks = $this->visibleTasks($user);
        $validated = $request->validate([
            'period' => ['sometimes', 'in:day,week,month,year'],
            'department_id' => ['sometimes', 'nullable', 'uuid', 'exists:departments,id'],
            'timezone' => ['sometimes', 'timezone'],
        ]);
        $timezone = $validated['timezone'] ?? config('app.timezone');
        $now = CarbonImmutable::now($timezone);
        $period = $validated['period'] ?? 'day';
        $department = null;
        if (! empty($validated['department_id'])) {
            $user = $request->user();
            $department = Department::findOrFail($validated['department_id']);
            abort_unless($user->hasPrivilege('properties.view_all') || $department->property_id === $user->property_id, 403);
            abort_unless($user->role === 'Admin' || $user->departments()->whereKey($department->id)->exists(), 403);
        }
        $visibleTasks->when($department, fn ($query) => $query->where('department_id', $department->id));
        $periodConfig = match ($period) {
            'week' => ['count' => 12, 'start' => $now->startOfWeek()->subWeeks(11), 'step' => 'week'],
            'month' => ['count' => 12, 'start' => $now->startOfMonth()->subMonths(11), 'step' => 'month'],
            'year' => ['count' => 5, 'start' => $now->startOfYear()->subYears(4), 'step' => 'year'],
            default => ['count' => 14, 'start' => $now->startOfDay()->subDays(13), 'step' => 'day'],
        };
        $end = $now->endOfDay();
        $driver = DB::connection()->getDriverName();
        $offsetMinutes = $now->utcOffset();
        $absoluteOffset = abs($offsetMinutes);
        $localSqlShift = $offsetMinutes >= 0
            ? "DATE_ADD(created_at, INTERVAL {$absoluteOffset} MINUTE)"
            : "DATE_SUB(created_at, INTERVAL {$absoluteOffset} MINUTE)";
        $sqliteShift = ($offsetMinutes >= 0 ? '+' : '-').$absoluteOffset.' minutes';
        $localSqlDate = "DATE({$localSqlShift})";
        $sqliteLocalDate = "date(created_at, '{$sqliteShift}')";
        $bucketExpression = match ([$driver, $period]) {
            ['mysql', 'day'] => $localSqlDate,
            ['mysql', 'week'] => "DATE_FORMAT(DATE_SUB({$localSqlDate}, INTERVAL WEEKDAY({$localSqlDate}) DAY), '%Y-%m-%d')",
            ['mysql', 'month'] => "DATE_FORMAT({$localSqlShift}, '%Y-%m')",
            ['mysql', 'year'] => "YEAR({$localSqlShift})",
            ['sqlite', 'day'] => "strftime('%Y-%m-%d', {$sqliteLocalDate})",
            ['sqlite', 'week'] => "date({$sqliteLocalDate}, '-' || ((CAST(strftime('%w', {$sqliteLocalDate}) AS INTEGER) + 6) % 7) || ' days')",
            ['sqlite', 'month'] => "strftime('%Y-%m', datetime(created_at, '{$sqliteShift}'))",
            ['sqlite', 'year'] => "strftime('%Y', datetime(created_at, '{$sqliteShift}'))",
            default => 'DATE(created_at)',
        };
        $seriesStart = $periodConfig['start'];
        $series = (clone $visibleTasks)
            ->whereBetween('created_at', [$seriesStart->setTimezone('UTC'), $end->setTimezone('UTC')])
            ->selectRaw("{$bucketExpression} as bucket, COUNT(*) as total")
            ->groupByRaw($bucketExpression)
            ->pluck('total', 'bucket');

        $summary = (clone $visibleTasks)
            ->selectRaw(
                'SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) AS created_today,
                SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) AS created_week,
                SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) AS created_month,
                SUM(CASE WHEN status = ? AND assigned_to IS NULL THEN 1 ELSE 0 END) AS status_created,
                SUM(CASE WHEN status = ? OR (status = ? AND assigned_to IS NOT NULL) THEN 1 ELSE 0 END) AS status_assigned,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS status_done',
                [
                    $now->startOfDay()->setTimezone('UTC'),
                    $now->startOfWeek()->setTimezone('UTC'),
                    $now->startOfMonth()->setTimezone('UTC'),
                    'NOT_STARTED',
                    'IN_PROGRESS',
                    'NOT_STARTED',
                    'DONE',
                ],
            )
            ->first();

        $chart = [];
        $cursor = $seriesStart;
        for ($index = 0; $index < $periodConfig['count']; $index++) {
            $key = match ($period) {
                'week' => $cursor->format('Y-m-d'),
                'month' => $cursor->format('Y-m'),
                'year' => $cursor->format('Y'),
                default => $cursor->format('Y-m-d'),
            };
            $label = $period === 'week' ? $cursor->format('o-\\WW') : $key;
            $chart[] = ['label' => $label, 'total' => (int) ($series[$key] ?? 0)];
            $cursor = $cursor->add(1, $periodConfig['step']);
        }

        $tasks = (clone $visibleTasks)
            ->with(['assignee', 'department', 'subtasks'])
            ->latest()
            ->limit(50)
            ->get();

        return response()->json([
            'timezone' => $timezone,
            'period_counts' => [
                'day' => (int) ($summary->created_today ?? 0),
                'week' => (int) ($summary->created_week ?? 0),
                'month' => (int) ($summary->created_month ?? 0),
            ],
            'statuses' => [
                'created' => (int) ($summary->status_created ?? 0),
                'assigned' => (int) ($summary->status_assigned ?? 0),
                'done' => (int) ($summary->status_done ?? 0),
            ],
            'chart' => ['period' => $period, 'data' => $chart],
            'tasks' => TaskResource::collection($tasks),
        ]);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $this->authorizeTaskRead($user);
        $validated = $request->validate([
            'department_id' => ['sometimes', 'nullable', 'uuid', 'exists:departments,id'],
            'status' => ['sometimes', 'nullable', 'in:NOT_STARTED,IN_PROGRESS,DONE,OVERDUE'],
        ]);

        $tasks = $this->visibleTasks($user)
            ->when(! empty($validated['department_id']), function ($query) use ($validated, $user) {
                $department = Department::findOrFail($validated['department_id']);
                abort_unless($user->hasPrivilege('properties.view_all') || $department->property_id === $user->property_id, 403);
                abort_unless($user->role === 'Admin' || $user->departments()->whereKey($department->id)->exists(), 403);

                $query->where('department_id', $department->id);
            })
            ->when(
                ! empty($validated['status']) && $validated['status'] !== 'OVERDUE',
                fn ($query) => $query->where('status', $validated['status']),
            )
            ->when(
                ($validated['status'] ?? null) === 'OVERDUE',
                fn ($query) => $query->where('status', '!=', 'DONE')
                    ->whereNotNull('due_date')
                    ->whereDate('due_date', '<', today()->toDateString()),
            )
            ->with(['assignee', 'creator', 'department', 'subtasks'])
            ->withCount([
                'checklistItems',
                'checklistItems as completed_checklist_items_count' => fn ($query) => $query->where('is_completed', true),
                'subtasks',
                'subtasks as completed_subtasks_count' => fn ($query) => $query->where('is_completed', true),
            ])
            ->latest()
            ->paginate(25);

        return TaskResource::collection($tasks);
    }

    public function show(Request $request, string $id)
    {
        $user = $request->user();
        $this->authorizeTaskRead($user);

        $task = $this->visibleTasks($user)
            ->with(['property', 'department', 'creator', 'assignee', 'subtasks.creator', 'checklistItems', 'comments.user', 'attachments.uploader'])
            ->findOrFail($id);

        return new TaskResource($task);
    }

    public function assignees(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->hasPrivilege('tasks.assign'), 403);

        $validated = $request->validate([
            'department_id' => ['required', 'uuid', 'exists:departments,id'],
        ]);
        $department = Department::findOrFail($validated['department_id']);
        $canAssignAcrossProperties = $user->hasPrivilege('properties.view_all');
        abort_unless($canAssignAcrossProperties || $department->property_id === $user->property_id, 403);
        abort_unless($user->role === 'Admin' || $user->departments()->whereKey($department->id)->exists(), 403);

        $canAssignAnyRole = $user->role === 'GM';
        $lowerRoles = $canAssignAnyRole
            ? array_values(array_filter(array_keys(User::roleHierarchy()), fn (string $role): bool => $role !== 'Admin'))
            : User::rolesBelow($user);
        $taskReadablePrivileges = ['tasks.view_all', 'tasks.manage', 'tasks.assign', 'tasks.update_assigned'];
        $roles = Role::whereIn('key', $lowerRoles)
            ->get(['key', 'name', 'privileges'])
            ->filter(fn (Role $role): bool => count(array_intersect($role->privileges ?? [], $taskReadablePrivileges)) > 0)
            ->sortBy(fn (Role $role): int => array_search($role->key, $lowerRoles, true))
            ->values();
        $roleKeys = $roles->pluck('key')->all();
        $assigneesQuery = User::query()
            ->where('approval_status', 'approved')
            ->whereIn('role', $roleKeys)
            ->whereKeyNot($user->id)
            ->with('property:id,name')
            ->orderBy('role')
            ->orderBy('name');

        $assigneesQuery->whereHas('departments', fn ($query) => $query->whereKey($department->id));
        $assigneesQuery->where('property_id', $department->property_id);

        $assignees = $assigneesQuery->get(['id', 'name', 'role', 'property_id']);

        return response()->json([
            'data' => $assignees->map(fn (User $assignee): array => [
                'id' => $assignee->id,
                'name' => $assignee->name,
                'role' => $assignee->role,
                'property_name' => $assignee->property?->name,
            ])->all(),
        ]);
    }

    public function storeAttachments(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $this->authorizeTaskRead($user);
        $task = $this->visibleTasks($user)->findOrFail($id);
        abort_unless($this->canUploadTaskAttachments($user, $task), 403);

        return $this->persistTaskAttachments($request, $user, $task);
    }

    private function persistTaskAttachments(Request $request, User $user, Task $task, ?array $validatedFiles = null): JsonResponse
    {
        $uploadedFiles = $validatedFiles ?? $request->validate([
            'attachments' => ['required', 'array', 'min:1', 'max:5'],
            'attachments.*' => [
                'required',
                File::types(['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'csv'])
                    ->max('10mb'),
                'extensions:jpg,jpeg,png,gif,bmp,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,csv',
            ],
        ])['attachments'];
        abort_if($task->attachments()->count() + count($uploadedFiles) > 20, 422, 'A task can have up to 20 attachments.');

        $storedPaths = [];
        try {
            $attachments = DB::transaction(function () use ($task, $user, $uploadedFiles, &$storedPaths) {
                return collect($uploadedFiles)->map(function ($file) use ($task, $user, &$storedPaths): TaskAttachment {
                    $path = $file->store("tasks/{$task->id}/attachments", 'local');
                    abort_if($path === false, 500, 'The attachment could not be stored.');
                    $storedPaths[] = $path;

                    $fileName = basename(str_replace('\\', '/', $file->getClientOriginalName()));
                    $fileName = mb_substr(preg_replace('/[\x00-\x1F\x7F]/u', '_', $fileName) ?: 'attachment', 0, 255);

                    return $task->attachments()->create([
                        'uploaded_by' => $user->id,
                        'file_name' => $fileName,
                        'storage_path' => $path,
                        'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                        'size_bytes' => $file->getSize(),
                    ]);
                });
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($storedPaths);
            throw $exception;
        }

        return TaskAttachmentResource::collection($attachments)->response()->setStatusCode(201);
    }

    public function downloadAttachment(Request $request, string $id, string $attachmentId): StreamedResponse
    {
        $user = $request->user();
        $this->authorizeTaskRead($user);
        $task = $this->visibleTasks($user)->findOrFail($id);
        $attachment = $task->attachments()->findOrFail($attachmentId);
        $disk = Storage::disk('local');
        abort_unless($disk->exists($attachment->storage_path), 404);

        $downloadName = Str::ascii($attachment->file_name);
        $downloadName = $downloadName !== '' ? $downloadName : 'task-attachment';

        return $disk->download($attachment->storage_path, $downloadName, [
            'Content-Type' => $attachment->mime_type,
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        abort_unless($user->hasPrivilege('tasks.assign'), 403);

        $validated = $request->validate([
            'property_id' => ['nullable', 'exists:properties,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'task_type' => ['sometimes', 'string', 'in:description,checklist'],
            'checklist_items' => ['prohibited_unless:task_type,checklist', 'required_if:task_type,checklist', 'array', 'min:1', 'max:50'],
            'checklist_items.*' => ['required', 'string', 'max:255'],
            'priority' => ['required', 'in:LOW,MEDIUM,HIGH'],
            'due_date' => ['nullable', 'date', 'after_or_equal:today'],
            'department_id' => ['required', 'exists:departments,id'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'attachments' => ['sometimes', 'array', 'max:5'],
            'attachments.*' => [
                'required',
                File::types(['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'csv'])
                    ->max('10mb'),
                'extensions:jpg,jpeg,png,gif,bmp,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,csv',
            ],
        ]);

        $canViewAllProperties = $user->hasPrivilege('properties.view_all');
        if ($canViewAllProperties && $user->property_id === null) {
            abort_unless(isset($validated['property_id']), 422, 'A property must be selected when creating a task.');
        }

        if (! $canViewAllProperties && isset($validated['property_id'])) {
            abort_unless($validated['property_id'] === $user->property_id, 403);
        }
        $propertyId = $canViewAllProperties
            ? ($validated['property_id'] ?? $user->property_id)
            : $user->property_id;

        $department = Department::whereKey($validated['department_id'])
            ->where('property_id', $propertyId)
            ->firstOrFail();

        abort_unless($user->role === 'Admin' || $user->departments()->whereKey($department->id)->exists(), 403);

        $canAssignAnyRole = $user->role === 'GM';
        if (! empty($validated['assigned_to'])) {
            $assigneeQuery = User::whereKey($validated['assigned_to'])
                ->where('approval_status', 'approved')
                ->where('property_id', $propertyId);

            $assigneeQuery->whereHas('departments', fn ($query) => $query->whereKey($department->id));

            $assignee = $assigneeQuery->firstOrFail();
            abort_unless($assignee->id !== $user->id, 403, 'You cannot assign a task to yourself.');

            abort_unless(
                $canAssignAnyRole || in_array($assignee->role, User::rolesBelow($user), true),
                403,
                'You can only assign tasks to users below your role.',
            );
            abort_unless(
                $assignee->hasPrivilege('tasks.update_assigned')
                    || $assignee->hasPrivilege('tasks.manage')
                    || $assignee->hasPrivilege('tasks.assign')
                    || $assignee->hasPrivilege('tasks.view_all'),
                403,
                'The selected role cannot access assigned tasks.',
            );
        }

        $taskType = $validated['task_type'] ?? 'description';
        $task = DB::transaction(function () use ($validated, $taskType, $propertyId, $department, $user): Task {
            $task = Task::create([
                'property_id' => $propertyId,
                'department_id' => $department->id,
                'created_by' => $user->id,
                'assigned_to' => $validated['assigned_to'] ?? null,
                'title' => $validated['title'],
                'description' => $taskType === 'description' ? ($validated['description'] ?? null) : null,
                'task_type' => $taskType,
                'priority' => $validated['priority'],
                'status' => 'NOT_STARTED',
                'progress_percentage' => 0,
                'due_date' => $validated['due_date'] ?? null,
            ]);

            if ($taskType === 'checklist') {
                $checklistItems = array_map(
                    fn (string $title, int $position): array => [
                        'title' => trim($title),
                        'position' => $position,
                        'is_completed' => false,
                    ],
                    $validated['checklist_items'],
                    array_keys($validated['checklist_items']),
                );

                $task->checklistItems()->createMany($checklistItems);
            }

            return $task;
        });

        if (! empty($validated['attachments'])) {
            $this->persistTaskAttachments($request, $user, $task, $validated['attachments']);
        }

        try {
            TaskTrackerChanged::dispatch($task->department_id, $task->assigned_to);
        } catch (Throwable $exception) {
            report($exception);
        }

        return (new TaskResource($task->load(['department', 'assignee', 'creator', 'checklistItems', 'attachments'])))
            ->response()->setStatusCode(201);
    }

    public function updateProgress(Request $request, string $id)
    {
        $task = $this->visibleTasks($request->user())->findOrFail($id);
        $user = $request->user();
        abort_unless(
            $task->assigned_to === $user->id
                && $task->created_by !== $user->id
                && $user->hasPrivilege('tasks.update_assigned'),
            403,
        );
        abort_unless($task->task_type !== 'checklist', 422, 'Checklist task progress is calculated from its completed items.');

        $validated = $request->validate([
            'progress_percentage' => ['required', 'integer', 'min:0', 'max:100'],
        ]);
        $task->progress_percentage = $validated['progress_percentage'];
        $task->status = match (true) {
            $task->progress_percentage === 100 => $task->status === 'DONE' ? 'DONE' : 'IN_PROGRESS',
            $task->progress_percentage > 0 => 'IN_PROGRESS',
            default => 'NOT_STARTED',
        };
        $task->completed_at = $task->status === 'DONE'
            ? ($task->completed_at ?? now())
            : null;
        $task->save();

        try {
            TaskTrackerChanged::dispatch($task->department_id, $task->assigned_to);
        } catch (Throwable $exception) {
            report($exception);
        }

        return response()->json(['message' => 'Progress updated.', 'data' => new TaskResource($task)]);
    }

    public function complete(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $task = $this->visibleTasks($user)->findOrFail($id);
        abort_unless(
            $task->assigned_to === $user->id
                && $task->created_by !== $user->id
                && $user->hasPrivilege('tasks.update_assigned'),
            403,
        );
        abort_unless((int) $task->progress_percentage === 100, 422, 'A task must be at 100% progress before it can be completed.');
        if ($task->task_type === 'checklist') {
            abort_unless($task->checklistItems()->exists(), 422, 'A checklist task must contain at least one checklist item.');
            abort_unless(
                ! $task->checklistItems()->where('is_completed', false)->exists(),
                422,
                'Complete every task checklist item before marking the task as completed.',
            );
        }
        abort_unless(
            ! $task->subtasks()->where('is_completed', false)->exists(),
            422,
            'Complete every subtask before marking the task as completed.',
        );

        $task->status = 'DONE';
        $task->completed_at = $task->completed_at ?? now();
        $task->save();

        try {
            $this->purgeTaskAttachments($task);
        } catch (Throwable $exception) {
            report($exception);
        }

        try {
            TaskTrackerChanged::dispatch($task->department_id, $task->assigned_to);
        } catch (Throwable $exception) {
            report($exception);
        }

        return response()->json([
            'message' => 'Task marked as completed.',
            'data' => new TaskResource($task->load(['property', 'department', 'creator', 'assignee', 'checklistItems'])),
        ]);
    }

    public function extendDueDate(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->hasPrivilege('tasks.assign'), 403);

        $task = DB::transaction(function () use ($request, $user, $id): Task {
            $task = $this->visibleTasks($user)
                ->lockForUpdate()
                ->findOrFail($id);

            abort_unless($task->status !== 'DONE', 422, 'Completed tasks cannot have their due date extended.');
            abort_unless($task->due_date !== null, 422, 'This task does not have a due date to extend.');

            $timezone = $task->property()->value('timezone') ?: config('app.timezone');
            $today = CarbonImmutable::now($timezone)->toDateString();

            $validated = $request->validate([
                'due_date' => [
                    'required',
                    'date_format:Y-m-d',
                    'after_or_equal:'.$today,
                    'after:'.$task->due_date->toDateString(),
                ],
            ]);

            $task->due_date = $validated['due_date'];
            $task->save();

            return $task;
        });

        try {
            TaskTrackerChanged::dispatch($task->department_id, $task->assigned_to);
        } catch (Throwable $exception) {
            report($exception);
        }

        return response()->json([
            'message' => 'Task due date extended.',
            'data' => new TaskResource($task->load(['property', 'department', 'creator', 'assignee'])),
        ]);
    }

    public function destroy(Request $request, string $id)
    {
        $user = $request->user();
        $task = $this->visibleTasks($user)->findOrFail($id);
        abort_unless(
            $user->hasPrivilege('tasks.manage')
                || ($task->created_by === $user->id && $user->hasPrivilege('tasks.assign')),
            403,
        );

        $attachmentPaths = $task->attachments()->pluck('storage_path')->all();
        $task->delete();
        Storage::disk('local')->delete($attachmentPaths);

        return response()->noContent();
    }

    public function addSubtask(Request $request, string $id)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $task = $this->visibleTasks($request->user())->findOrFail($id);
        abort_unless(
            $task->assigned_to === $request->user()->id
                && $task->created_by !== $request->user()->id
                && $request->user()->hasPrivilege('subtasks.manage_assigned'),
            403,
        );

        $subtask = $task->subtasks()->create([
            'created_by' => $request->user()->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
        ]);

        return response()->json(['message' => 'Subtask added.', 'data' => $subtask], 201);
    }

    public function toggleSubtask(Request $request, string $id, string $subtaskId)
    {
        $task = $this->visibleTasks($request->user())->findOrFail($id);
        abort_unless(
            $task->assigned_to === $request->user()->id
                && $task->created_by !== $request->user()->id
                && $request->user()->hasPrivilege('subtasks.manage_assigned'),
            403,
        );

        $subtask = $task->subtasks()->findOrFail($subtaskId);
        $subtask->is_completed = ! $subtask->is_completed;
        $subtask->save();

        return response()->json(['data' => $subtask]);
    }

    public function toggleChecklistItem(Request $request, string $id, string $itemId): JsonResponse
    {
        $user = $request->user();
        $task = $this->visibleTasks($user)->findOrFail($id);
        abort_unless($task->task_type === 'checklist', 409, 'This task does not use a checklist.');
        abort_unless(
            $task->assigned_to === $user->id
                && $task->created_by !== $user->id
                && $user->hasPrivilege('tasks.update_assigned'),
            403,
        );

        $checklistItem = $task->checklistItems()->findOrFail($itemId);
        $checklistItem->is_completed = ! $checklistItem->is_completed;
        $checklistItem->save();

        $this->syncChecklistProgress($task);

        try {
            TaskTrackerChanged::dispatch($task->department_id, $task->assigned_to);
        } catch (Throwable $exception) {
            report($exception);
        }

        $task->load(['property', 'department', 'creator', 'assignee', 'checklistItems']);

        return response()->json(['data' => new TaskResource($task)]);
    }

    private function syncChecklistProgress(Task $task): void
    {
        $totalItems = $task->checklistItems()->reorder()->count();
        $completedItems = $task->checklistItems()->reorder()->where('is_completed', true)->count();
        $task->progress_percentage = $totalItems > 0
            ? (int) round(($completedItems / $totalItems) * 100)
            : 0;
        $task->status = match (true) {
            $task->progress_percentage === 100 => $task->status === 'DONE' ? 'DONE' : 'IN_PROGRESS',
            $task->progress_percentage > 0 => 'IN_PROGRESS',
            default => 'NOT_STARTED',
        };
        $task->completed_at = $task->status === 'DONE'
            ? ($task->completed_at ?? now())
            : null;
        $task->save();
    }

    public function destroySubtask(Request $request, string $id, string $subtaskId): Response
    {
        $user = $request->user();
        $task = $this->visibleTasks($user)->findOrFail($id);
        abort_unless(
            $task->assigned_to === $user->id
                && $task->created_by !== $user->id
                && $user->hasPrivilege('subtasks.manage_assigned'),
            403,
        );

        $task->subtasks()->findOrFail($subtaskId)->delete();

        return response()->noContent();
    }

    public function addComment(Request $request, string $id)
    {
        $validated = $request->validate(['comment' => ['required', 'string', 'max:10000']]);
        $user = $request->user();
        $this->authorizeTaskRead($user);
        $task = $this->visibleTasks($user)->findOrFail($id);
        abort_unless($this->canCommentOnTask($user, $task), 403);

        $comment = $task->comments()->create([
            'user_id' => $request->user()->id,
            'comment' => $validated['comment'],
        ]);

        return response()->json(['data' => $comment->load('user:id,name')], 201);
    }

    public function getComments(Request $request, string $id)
    {
        $user = $request->user();
        $this->authorizeTaskRead($user);
        $task = $this->visibleTasks($user)->findOrFail($id);
        abort_unless($this->canCommentOnTask($user, $task), 403);
        $comments = $task->comments()->with('user:id,name')->oldest()->paginate(50);

        return response()->json(['data' => $comments]);
    }

    public function destroyComment(Request $request, string $id, string $commentId): Response
    {
        $user = $request->user();
        $this->authorizeTaskRead($user);
        $task = $this->visibleTasks($user)->findOrFail($id);
        abort_unless($this->canCommentOnTask($user, $task), 403);

        $comment = $task->comments()->findOrFail($commentId);
        abort_unless($comment->user_id === $user->id, 403);
        $comment->delete();

        return response()->noContent();
    }

    private function canCommentOnTask(User $user, Task $task): bool
    {
        return $user->hasPrivilege('tasks.comment')
            && ($task->created_by === $user->id || $task->assigned_to === $user->id);
    }

    private function canUploadTaskAttachments(User $user, Task $task): bool
    {
        return $task->created_by === $user->id;
    }

    private function purgeTaskAttachments(Task $task): int
    {
        $attachments = $task->attachments()->get(['id', 'storage_path']);

        if ($attachments->isEmpty()) {
            return 0;
        }

        if (! Storage::disk('local')->delete($attachments->pluck('storage_path')->all())) {
            throw new \RuntimeException('Task attachments could not be deleted from storage.');
        }

        return $task->attachments()->whereKey($attachments->modelKeys())->delete();
    }

    private function visibleTasks(User $user): Builder
    {
        $tasks = Task::query();

        if ($user->role !== 'Admin') {
            $tasks
                ->whereIn('department_id', $user->departments()->select('departments.id'))
                ->whereHas('department', fn ($departmentQuery) => $departmentQuery->whereColumn('departments.property_id', 'tasks.property_id'));
        }

        return $tasks;
    }

    private function authorizeTaskRead(User $user): void
    {
        abort_unless(
            $user->hasPrivilege('tasks.view_all')
                || $user->hasPrivilege('tasks.manage')
                || $user->hasPrivilege('tasks.assign')
                || $user->hasPrivilege('tasks.update_assigned'),
            403,
        );
    }
}
