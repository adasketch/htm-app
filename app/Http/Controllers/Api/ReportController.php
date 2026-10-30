<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Models\Department;
use App\Models\Task;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function departmentPerformance(Request $request)
    {
        $user = $request->user();
        abort_unless($user->hasPrivilege('reports.view'), 403);

        $validated = $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
            'period' => ['required', 'in:7,30,90'],
            'task_status' => ['sometimes', 'in:all,completed,unfinished'],
        ]);

        $requestedPropertyId = $request->header('X-Property-ID');
        $canViewAllProperties = $user->hasPrivilege('properties.view_all');
        if (! $canViewAllProperties && $requestedPropertyId && $requestedPropertyId !== $user->property_id) {
            abort(403);
        }

        $propertyId = $canViewAllProperties
            ? ($requestedPropertyId ?? $user->property_id)
            : $user->property_id;

        $department = Department::whereKey($validated['department_id'])
            ->where('property_id', $propertyId)
            ->firstOrFail();

        if ($user->role !== 'Admin') {
            abort_unless($user->departments()->whereKey($department->id)->exists(), 404);
        }

        $days = (int) $validated['period'];
        $startDate = now()->subDays($days)->startOfDay();
        $tasks = Task::where('property_id', $propertyId)
            ->where('department_id', $department->id)
            ->where('created_at', '>=', $startDate)
            ->with(['assignee:id,name', 'creator:id,name', 'department'])
            ->get();
        $taskStatusFilter = $validated['task_status'] ?? 'all';
        $filteredTasks = match ($taskStatusFilter) {
            'completed' => $tasks->where('status', 'DONE')->values(),
            'unfinished' => $tasks->where('status', '!=', 'DONE')->values(),
            default => $tasks,
        };

        $totalTasks = $tasks->count();
        $completedTasks = $tasks->where('status', 'DONE')->values();
        $doneCount = $completedTasks->count();
        $pendingTasks = $tasks->whereIn('status', ['NOT_STARTED', 'IN_PROGRESS']);
        $pendingCount = $pendingTasks->count();
        $overdueCount = $pendingTasks->filter(fn (Task $task) => $task->due_date && $task->due_date->lt(now()->startOfDay())
        )->count();

        $onTimeCount = 0;
        $completedWithDueDate = 0;
        $completedWithTimestamp = 0;
        $totalCompletionTime = 0.0;

        foreach ($completedTasks as $task) {
            if ($task->completed_at) {
                $completedWithTimestamp++;
                $totalCompletionTime += $task->completed_at->diffInDays($task->created_at);
            }

            if ($task->due_date && $task->completed_at) {
                $completedWithDueDate++;
                if ($task->completed_at->lessThanOrEqualTo($task->due_date->endOfDay())) {
                    $onTimeCount++;
                }
            }
        }

        $completionRate = $totalTasks > 0 ? ($doneCount / $totalTasks) * 100 : 0;
        $onTimeRate = $completedWithDueDate > 0 ? ($onTimeCount / $completedWithDueDate) * 100 : 0;
        $avgFinishTimeDays = $completedWithTimestamp > 0
            ? ($totalCompletionTime / $completedWithTimestamp)
            : 0;
        $speedScore = match (true) {
            $doneCount === 0 => 0,
            $avgFinishTimeDays <= 1 => 100,
            $avgFinishTimeDays < 14 => 100 - (($avgFinishTimeDays - 1) * (100 / 13)),
            default => 0,
        };

        $performanceScore = ($completionRate * 0.40) + ($onTimeRate * 0.30) + ($speedScore * 0.30);
        $scoreStatus = $performanceScore >= 75 ? 'Good' : ($performanceScore >= 50 ? 'Watch' : 'Needs Action');

        return response()->json([
            'property_id' => $propertyId,
            'department_id' => $department->id,
            'period' => $days.' days',
            'metrics' => [
                'total_tasks' => $totalTasks,
                'done' => $doneCount,
                'pending' => $pendingCount,
                'overdue_now' => $overdueCount,
                'on_time_completed' => $onTimeCount,
                'avg_time_to_finish_days' => round($avgFinishTimeDays, 1),
            ],
            'performance' => [
                'completion_rate' => round($completionRate, 1).'%',
                'on_time_rate' => round($onTimeRate, 1).'%',
                'speed_score' => round($speedScore, 1),
                'overall_score' => round($performanceScore, 1),
                'status' => $scoreStatus,
            ],
            'tasks' => TaskResource::collection($filteredTasks),
            'completed_tasks' => TaskResource::collection($completedTasks),
        ]);
    }
}
