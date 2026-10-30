<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\TaskAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskAttachment>
 */
class TaskAttachmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task_id' => fake()->uuid(),
            'uploaded_by' => null,
            'file_name' => fake()->word().'.pdf',
            'storage_path' => 'tasks/'.fake()->uuid().'/attachments/'.fake()->uuid().'.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => fake()->numberBetween(1024, 1024 * 1024),
        ];
    }

    public function forTask(Task $task): static
    {
        return $this->state(fn (): array => [
            'task_id' => $task->id,
            'uploaded_by' => $task->created_by,
        ]);
    }
}
