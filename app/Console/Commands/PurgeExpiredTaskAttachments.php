<?php

namespace App\Console\Commands;

use App\Models\Task;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PurgeExpiredTaskAttachments extends Command
{
    protected $signature = 'tasks:purge-expired-attachments';

    protected $description = 'Delete attachments from completed or expired tasks';

    public function handle(): int
    {
        $now = CarbonImmutable::now('UTC');
        $candidateDate = $now->addDay()->toDateString();
        $deletedAttachments = 0;

        Task::query()
            ->whereHas('attachments')
            ->where(function (Builder $query) use ($candidateDate): void {
                $query->where('status', 'DONE')
                    ->orWhere(function (Builder $query) use ($candidateDate): void {
                        $query->whereNotNull('due_date')
                            ->where('due_date', '<=', $candidateDate);
                    });
            })
            ->with(['property:id,timezone', 'attachments:id,task_id,storage_path'])
            ->chunkById(100, function ($tasks) use ($now, &$deletedAttachments): void {
                foreach ($tasks as $task) {
                    try {
                        $deletedAttachments += DB::transaction(function () use ($now, $task): int {
                            $lockedTask = Task::query()
                                ->with(['property:id,timezone', 'attachments:id,task_id,storage_path'])
                                ->whereKey($task->id)
                                ->lockForUpdate()
                                ->first();

                            if (! $lockedTask || ! $this->hasExpired($lockedTask, $now)) {
                                return 0;
                            }

                            $attachments = $lockedTask->attachments;

                            if ($attachments->isEmpty()) {
                                return 0;
                            }

                            if (! Storage::disk('local')->delete($attachments->pluck('storage_path')->all())) {
                                throw new \RuntimeException("Could not delete attachments for task {$lockedTask->id}.");
                            }

                            return $lockedTask->attachments()->delete();
                        });
                    } catch (Throwable $exception) {
                        report($exception);
                        $this->components->error("Could not purge attachments for task {$task->id}.");
                    }
                }
            });

        $this->components->info("Deleted {$deletedAttachments} expired task attachment(s).");

        return self::SUCCESS;
    }

    private function hasExpired(Task $task, CarbonImmutable $now): bool
    {
        if ($task->status === 'DONE') {
            return true;
        }

        if (! $task->due_date) {
            return false;
        }

        $timezone = $task->property?->timezone ?: config('app.timezone');
        $dueDateEnd = CarbonImmutable::parse($task->due_date->toDateString(), $timezone)
            ->endOfDay();

        return $dueDateEnd->lessThanOrEqualTo($now);
    }
}
