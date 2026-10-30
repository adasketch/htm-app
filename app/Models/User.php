<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuids, Notifiable, SoftDeletes;

    private const ADMIN_RESTRICTED_PRIVILEGES = [
        'tasks.assign',
        'tasks.manage',
        'tasks.update_assigned',
        'tasks.comment',
        'subtasks.manage_assigned',
    ];

    private const ROLE_HIERARCHY = [
        'Associate' => 1,
        'Vendor' => 1,
        'Daily Worker' => 1,
        'Casual' => 1,
        'Department Leader' => 2,
        'Manager' => 3,
        'GM' => 4,
        'IT' => 5,
        'Admin' => 6,
    ];

    protected $fillable = [
        'name',
        'position',
        'email',
        'password',
        'property_id',
        'role', // Contoh: 'Admin', 'Manager', 'Associate'
        'email_verified_at',
        'approval_status',
        'approved_at',
        'approved_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'approved_at' => 'datetime',
        ];
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    // Relasi Many-to-Many ke Departemen
    public function departments()
    {
        return $this->belongsToMany(Department::class)
            ->withPivot('is_default')
            ->withTimestamps();
    }

    // Mengambil departemen utama (default)
    public function defaultDepartment()
    {
        return $this->departments()->wherePivot('is_default', true)->first();
    }

    public function hasPrivilege(string $privilege): bool
    {
        if ($this->role === 'Admin') {
            return ! in_array($privilege, self::ADMIN_RESTRICTED_PRIVILEGES, true);
        }

        return in_array($privilege, $this->privileges(), true);
    }

    public function privileges(): array
    {
        $roleKey = $this->role;

        $privileges = Cache::rememberForever(
            'role_privileges:'.$roleKey,
            fn () => Role::where('key', $roleKey)->first()?->privileges ?? [],
        );

        return $roleKey === 'Admin'
            ? array_values(array_diff($privileges, self::ADMIN_RESTRICTED_PRIVILEGES))
            : $privileges;
    }

    /**
     * @return array<string, int>
     */
    public static function roleHierarchy(): array
    {
        return self::ROLE_HIERARCHY;
    }

    /**
     * @return list<string>
     */
    public static function rolesBelow(self $user): array
    {
        $hierarchy = self::ROLE_HIERARCHY;
        $userRank = $hierarchy[$user->role] ?? 0;
        $roles = array_keys(array_filter($hierarchy, fn (int $rank): bool => $rank < $userRank));
        $roleOrder = array_keys($hierarchy);

        usort($roles, function (string $leftRole, string $rightRole) use ($hierarchy, $roleOrder): int {
            $rankOrder = $hierarchy[$rightRole] <=> $hierarchy[$leftRole];

            return $rankOrder !== 0
                ? $rankOrder
                : array_search($leftRole, $roleOrder, true) <=> array_search($rightRole, $roleOrder, true);
        });

        return $roles;
    }

    public function approvedBy()
    {
        return $this->belongsTo(self::class, 'approved_by');
    }
}
