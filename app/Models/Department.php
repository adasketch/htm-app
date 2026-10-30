<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['property_id', 'name'];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot('is_default')
            ->withTimestamps();
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
}
