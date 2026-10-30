<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskTrackerChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public string $departmentId, public ?string $assigneeId = null) {}

    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel('departments.'.$this->departmentId)];

        if ($this->assigneeId !== null) {
            $channels[] = new PrivateChannel('users.'.$this->assigneeId);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'task.tracker.changed';
    }

    /**
     * @return array{department_id: string}
     */
    public function broadcastWith(): array
    {
        return ['department_id' => $this->departmentId];
    }
}
