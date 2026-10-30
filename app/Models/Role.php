<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasUuids;

    protected $fillable = ['key', 'name', 'privileges', 'is_system'];

    protected function casts(): array
    {
        return [
            'privileges' => 'array',
            'is_system' => 'boolean',
        ];
    }
}
