<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'net_id',
        'first_name',
        'last_name',
        'name',
        'email',
        'department',
        'title',
        'supervisor_net_id',
        'supervisor_name',
        'is_admin',
        'is_manager',
        'is_supervisor',
        'is_active',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_manager' => 'boolean',
            'is_supervisor' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the user's full name.
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /**
     * Get dynamic roles assigned to this user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')
            ->withPivot(['assigned_by', 'assigned_at'])
            ->withTimestamps();
    }

    /**
     * Get applications this user has access to.
     */
    public function accessibleApplications()
    {
        return Application::whereHas('roles', function ($query) {
            $query->whereIn('roles.id', $this->roles()->pluck('roles.id'));
        })->active();
    }

    /**
     * Get the user's roles as an array (including both old and new system).
     */
    public function getRoles(): array
    {
        $roles = [];

        // Legacy role system
        if ($this->is_admin)
            $roles[] = 'Admin';
        if ($this->is_manager)
            $roles[] = 'Manager';
        if ($this->is_supervisor)
            $roles[] = 'Supervisor';

        // Dynamic role system
        $dynamicRoles = $this->roles()->active()->pluck('display_name')->toArray();
        $roles = array_merge($roles, $dynamicRoles);

        return array_unique($roles);
    }

    /**
     * Check if user has a specific role (supports both old and new system).
     */
    public function hasRole(string $role): bool
    {
        // Check legacy roles
        $legacyCheck = match (strtolower($role)) {
            'admin' => $this->is_admin,
            'manager' => $this->is_manager,
            'supervisor' => $this->is_supervisor,
            default => false,
        };

        if ($legacyCheck) {
            return true;
        }

        // Check dynamic roles
        return $this->roles()->active()
            ->where(function ($query) use ($role) {
                $query->where('name', strtolower($role))
                    ->orWhere('display_name', $role);
            })
            ->exists();
    }

    /**
     * Assign a role to this user.
     */
    public function assignRole($role, $assignedBy = null): void
    {
        $roleModel = $role instanceof Role ? $role : Role::where('name', $role)->first();

        if ($roleModel) {
            $this->roles()->syncWithoutDetaching([
                $roleModel->id => [
                    'assigned_by' => $assignedBy ?? auth()->id(),
                    'assigned_at' => now(),
                ]
            ]);
        }
    }

    /**
     * Remove a role from this user.
     */
    public function removeRole($role): void
    {
        $roleModel = $role instanceof Role ? $role : Role::where('name', $role)->first();

        if ($roleModel) {
            $this->roles()->detach($roleModel->id);
        }
    }

    /**
     * Check if user is a manager (supports both old and new system).
     */
    public function isManager(): bool
    {
        return $this->is_manager || $this->hasRole('manager') || $this->hasRole('library_manager');
    }

    /**
     * Check if user has access to a specific application.
     */
    public function hasAccessToApplication(Application $application): bool
    {
        return $application->hasAccess($this);
    }

    /**
     * Scope to filter active users.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter users by role.
     */
    public function scopeWithRole($query, string $role)
    {
        return match (strtolower($role)) {
            'admin' => $query->where('is_admin', true),
            'manager' => $query->where('is_manager', true),
            'supervisor' => $query->where('is_supervisor', true),
            default => $query,
        };
    }
}
